<?php

use Bbatsche\Entrust\Entrust;
use Illuminate\Support\Facades\Facade;
use Mockery as m;

class EntrustTest extends PHPUnit_Framework_TestCase
{
    use Codeception\Specify;

    protected $nullFilterTest;
    protected $abortFilterTest;
    protected $customResponseFilterTest;

    protected $expectedResponse;

    protected $entrust;

    public function setUp()
    {
        $this->nullFilterTest = function($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            $this->assertNull($filterClosure());

            return true;
        };

        $this->abortFilterTest = function($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            try {
                $filterClosure();
            } catch (Exception $e) {
                $this->assertSame('abort', $e->getMessage());

                return true;
            }

            // If we've made it this far, no exception was thrown and something went wrong
            return false;
        };

        $this->customResponseFilterTest = function($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            $result = $filterClosure();

            $this->assertSame($this->expectedResponse, $result);

            return true;
        };
    }
    
    public function tearDown()
    {
        m::close();
    }

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $entrust = m::mock('Bbatsche\Entrust\Entrust[user]', [$app]);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $entrust->shouldReceive('user')
            ->andReturn($user)
            ->twice()->ordered();

        $entrust->shouldReceive('user')
            ->andReturn(false)
            ->once()->ordered();

        $user->shouldReceive('hasRole')
            ->with('UserRole', false)
            ->andReturn(true)
            ->once();

        $user->shouldReceive('hasRole')
            ->with('NonUserRole', false)
            ->andReturn(false)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($entrust->hasRole('UserRole'));
        $this->assertFalse($entrust->hasRole('NonUserRole'));
        $this->assertFalse($entrust->hasRole('AnyRole'));
    }

    public function testCan()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $entrust = m::mock('Bbatsche\Entrust\Entrust[user]', [$app]);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $entrust->shouldReceive('user')
            ->andReturn($user)
            ->twice()->ordered();

        $entrust->shouldReceive('user')
            ->andReturn(false)
            ->once()->ordered();

        $user->shouldReceive('can')
            ->with('user_can', false)
            ->andReturn(true)
            ->once();

        $user->shouldReceive('can')
            ->with('user_cannot', false)
            ->andReturn(false)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($entrust->can('user_can'));
        $this->assertFalse($entrust->can('user_cannot'));
        $this->assertFalse($entrust->can('any_permission'));
    }

    public function testShouldGetUser()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->auth = m::mock('Auth');
        $entrust = new Entrust($app);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->auth->shouldReceive('user')
            ->andReturn($user)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($user, $entrust->user());
    }

    public function testRouteNeedsRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = new Entrust($app);

        $route = 'route';
        $oneRole = 'RoleA';
        $manyRole = ['RoleA', 'RoleB', 'RoleC'];

        $oneRoleFilterName  = $this->makeFilterName($route, [$oneRole]);
        $manyRoleFilterName = $this->makeFilterName($route, $manyRole);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($oneRoleFilterName, $manyRoleFilterName), m::type('Closure'))
            ->twice()->ordered();

        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($oneRoleFilterName, $manyRoleFilterName))
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $entrust->routeNeedsRole($route, $oneRole);
        $entrust->routeNeedsRole($route, $manyRole);
    }

    public function testFilterGeneratedByRouteNeedsRole()
    {
        // Set & Check Mock objects after each specify
        $this->beforeSpecify(function() {
            $app           = m::mock('Illuminate\Foundation\Application');
            $app->router   = m::mock('Route');
            $this->entrust = m::mock('Bbatsche\Entrust\Entrust[hasRole]', [$app]);
        });
        
        $this->afterSpecify(function() {
            m::close();
        });

        // Static values
        $route      = 'route';
        $roleName   = 'UserRole';
        $filterName = $this->makeFilterName($route, [$roleName]);
        
        // Test spec and execution
        $this->specify('return no value if user has role', function() use (
            $route, $roleName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->nullFilterTest))->once();
            
            $this->entrust->shouldReceive('hasRole')->with($roleName, m::any(true, false))->andReturn(true)->once();
            $this->entrust->routeNeedsRole($route, $roleName);
        });
        
        $this->specify('abort 403 if user does not have role', function() use (
            $route, $roleName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->abortFilterTest))->once();
            
            $this->entrust->app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();
            
            $this->entrust->shouldReceive('hasRole')->with($roleName, m::any(true, false))->andReturn(false)->once();
            $this->entrust->routeNeedsRole($route, $roleName);
        });

        $this->specify('return callback if user does not have role', function() use (
            $route, $roleName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->customResponseFilterTest))->once();
            
            $this->expectedResponse = new stdClass();

            $this->entrust->shouldReceive('hasRole')->with($roleName, m::any(true, false))->andReturn(false)->once();
            $this->entrust->routeNeedsRole($route, $roleName, $this->expectedResponse);
        });
    }

    public function testRouteNeedsPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = new Entrust($app);

        $route = 'route';
        $onePerm = 'can_a';
        $manyPerm = ['can_a', 'can_b', 'can_c'];

        $onePermFilterName = $this->makeFilterName($route, [$onePerm]);
        $manyPermFilterName = $this->makeFilterName($route, $manyPerm);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($onePermFilterName, $manyPermFilterName), m::type('Closure'))
            ->twice()->ordered();

        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($onePermFilterName, $manyPermFilterName))
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $entrust->routeNeedsPermission($route, $onePerm);
        $entrust->routeNeedsPermission($route, $manyPerm);
    }

    public function testFilterGeneratedByRouteNeedsPermission()
    {
        // Set & Check Mock objects after each specify
        $this->beforeSpecify(function() {
            $app           = m::mock('Illuminate\Foundation\Application');
            $app->router   = m::mock('Route');
            $this->entrust = m::mock('Bbatsche\Entrust\Entrust[can]', [$app]);
        });
        
        $this->afterSpecify(function() {
            m::close();
        });

        // Static values
        $route      = 'route';
        $permName   = 'user-permission';
        $filterName = $this->makeFilterName($route, [$permName]);

        // Test spec and execution
        $this->specify('return no value if user has permission', function() use (
            $route, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('filter')->with($filterName, m::on($this->nullFilterTest))->once();
            $router->shouldReceive('when')->with($route, $filterName)->once();
            
            $this->entrust->shouldReceive('can')->with($permName, m::any(true, false))->andReturn(true)->once();

            $this->entrust->routeNeedsPermission($route, $permName);
        });

        $this->specify('abort 403 if user does not have permission', function() use (
            $route, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('filter')->with($filterName, m::on($this->abortFilterTest))->once();
            $router->shouldReceive('when')->with($route, $filterName)->once();
            
            $this->entrust->app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();
            
            $this->entrust->shouldReceive('can')->with($permName, m::any(true, false))->andReturn(false)->once();
            $this->entrust->routeNeedsPermission($route, $permName);
        });

        $this->specify('return callback if user does not have permission', function() use (
            $route, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('filter')->with($filterName, m::on($this->customResponseFilterTest))->once();
            $router->shouldReceive('when')->with($route, $filterName)->once();
            
            $this->expectedResponse = new stdClass();
            
            $this->entrust->shouldReceive('can')->with($permName, m::any(true, false))->andReturn(false)->once();
            $this->entrust->routeNeedsPermission($route, $permName, $this->expectedResponse);
        });
    }

    public function testRouteNeedsRoleOrPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = new Entrust($app);

        $route = 'route';
        $oneRole = 'RoleA';
        $manyRole = ['RoleA', 'RoleB', 'RoleC'];
        $onePerm = 'can_a';
        $manyPerm = ['can_a', 'can_b', 'can_c'];

        $oneRoleOnePermFilterName = $this->makeFilterName($route, [$oneRole], [$onePerm]);
        $oneRoleManyPermFilterName = $this->makeFilterName($route, [$oneRole], $manyPerm);
        $manyRoleOnePermFilterName = $this->makeFilterName($route, $manyRole, [$onePerm]);
        $manyRoleManyPermFilterName = $this->makeFilterName($route, $manyRole, $manyPerm);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(
                m::anyOf(
                    $oneRoleOnePermFilterName,
                    $oneRoleManyPermFilterName,
                    $manyRoleOnePermFilterName,
                    $manyRoleManyPermFilterName
                ),
                m::type('Closure')
            )
            ->times(4)->ordered();

        $app->router->shouldReceive('when')
            ->with(
                $route,
                m::anyOf(
                    $oneRoleOnePermFilterName,
                    $oneRoleManyPermFilterName,
                    $manyRoleOnePermFilterName,
                    $manyRoleManyPermFilterName
                )
            )
            ->times(4);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $entrust->routeNeedsRoleOrPermission($route, $oneRole, $onePerm);
        $entrust->routeNeedsRoleOrPermission($route, $oneRole, $manyPerm);
        $entrust->routeNeedsRoleOrPermission($route, $manyRole, $onePerm);
        $entrust->routeNeedsRoleOrPermission($route, $manyRole, $manyPerm);
    }

    public function testFilterGeneratedByRouteNeedsRoleOrPermission()
    {
        // Set & Check Mock objects after each specify
        $this->beforeSpecify(function() {
            $app           = m::mock('Illuminate\Foundation\Application');
            $app->router   = m::mock('Route');
            $this->entrust = m::mock('Bbatsche\Entrust\Entrust[hasRole, can]', [$app]);
        });

        $this->afterSpecify(function() {
            m::close();
        });

        // Static values
        $route      = 'route';
        $roleName   = 'UserRole';
        $permName   = 'user-permission';
        $filterName = $this->makeFilterName($route, [$roleName], [$permName]);

        // Test spec and execution
        $this->specify('return no value with valid role and perm', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->twice();
            $router->shouldReceive('filter')->with($filterName, m::on($this->nullFilterTest))->twice();

            $this->entrust->shouldReceive('hasRole')->with($roleName, true)->andReturn(true)->once();
            $this->entrust->shouldReceive('hasRole')->with($roleName, false)->andReturn(true)->once();
            
            $this->entrust->shouldReceive('can')->with($permName, true)->andReturn(true)->once();
            $this->entrust->shouldReceive('can')->with($permName, false)->andReturn(true)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName);
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, null, true);
        });

        $this->specify('return no value with valid role and invalid perm if require all is false', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->nullFilterTest))->once();

            $this->entrust->shouldReceive('hasRole')->with($roleName, false)->andReturn(true)->once();
            $this->entrust->shouldReceive('can')->with($permName, false)->andReturn(false)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName);
        });

        $this->specify('return no value with invalid role and valid perm if require all is false', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->nullFilterTest))->once();

            $this->entrust->shouldReceive('hasRole')->with($roleName, false)->andReturn(false)->once();
            $this->entrust->shouldReceive('can')->with($permName, false)->andReturn(true)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName);
        });

        $this->specify('abort 403 with valid role and invalid perm if require all is true', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->abortFilterTest))->once();

            $this->entrust->app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();

            $this->entrust->shouldReceive('hasRole')->with($roleName, true)->andReturn(true)->once();
            $this->entrust->shouldReceive('can')->with($permName, true)->andReturn(false)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, null, true);
        });

        $this->specify('abort 403 with invalid role and valid perm if require all is true', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->abortFilterTest))->once();
            
            $this->entrust->app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();

            $this->entrust->shouldReceive('hasRole')->with($roleName, true)->andReturn(false)->once();
            $this->entrust->shouldReceive('can')->with($permName, true)->andReturn(true)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, null, true);
        });

        $this->specify('abort 403 with invalid role and invalid perm', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->twice();
            $router->shouldReceive('filter')->with($filterName, m::on($this->abortFilterTest))->twice();
            
            $this->entrust->app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->twice();

            $this->entrust->shouldReceive('hasRole')->with($roleName, false)->andReturn(false)->once();
            $this->entrust->shouldReceive('hasRole')->with($roleName, true)->andReturn(false)->once();
            
            $this->entrust->shouldReceive('can')->with($permName, false)->andReturn(false)->once();
            $this->entrust->shouldReceive('can')->with($permName, true)->andReturn(false)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName);
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, null, true);
        });

        $this->specify('return callback with valid role and invalid perm if require all is true', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->customResponseFilterTest))->once();

            $this->expectedResponse = new stdClass();

            $this->entrust->shouldReceive('hasRole')->with($roleName, true)->andReturn(true)->once();
            $this->entrust->shouldReceive('can')->with($permName, true)->andReturn(false)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, $this->expectedResponse, true);
        });

        $this->specify('return callback with invalid role and valid perm if require all is true', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->once();
            $router->shouldReceive('filter')->with($filterName, m::on($this->customResponseFilterTest))->once();

            $this->expectedResponse = new stdClass();

            $this->entrust->shouldReceive('hasRole')->with($roleName, true)->andReturn(false)->once();
            $this->entrust->shouldReceive('can')->with($permName, true)->andReturn(true)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, $this->expectedResponse, true);
        });

        $this->specify('return callback with invalid role and invalid perm', function() use (
            $route, $roleName, $permName, $filterName
        ) {
            $router = $this->entrust->app->router;
            
            $router->shouldReceive('when')->with($route, $filterName)->twice();
            $router->shouldReceive('filter')->with($filterName, m::on($this->customResponseFilterTest))->twice();

            $this->expectedResponse = new stdClass();

            $this->entrust->shouldReceive('hasRole')->with($roleName, false)->andReturn(false)->once();
            $this->entrust->shouldReceive('hasRole')->with($roleName, true)->andReturn(false)->once();
            
            $this->entrust->shouldReceive('can')->with($permName, false)->andReturn(false)->once();
            $this->entrust->shouldReceive('can')->with($permName, true)->andReturn(false)->once();
            
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, $this->expectedResponse);
            $this->entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, $this->expectedResponse, true);
        });
    }

    protected function makeFilterName($route, array $roles, array $permissions = null)
    {
        if (is_null($permissions)) {
            return implode('_', $roles) . '_' . substr(md5($route), 0, 6);
        } else {
            return implode('_', $roles) . '_' . implode('_', $permissions) . '_' . substr(md5($route), 0, 6);
        }
    }
}
