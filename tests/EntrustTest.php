<?php

use Zizaco\Entrust\Entrust;
use Illuminate\Support\Facades\Facade;
use Mockery as m;

class EntrustTest extends PHPUnit_Framework_TestCase
{
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
        $entrust = m::mock('Zizaco\Entrust\Entrust[user]', [$app]);
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
            ->with('UserRole')
            ->andReturn(true)
            ->once();
            
        $user->shouldReceive('hasRole')
            ->with('NonUserRole')
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
        $entrust = m::mock('Zizaco\Entrust\Entrust[user]', [$app]);
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
            ->with('user_can')
            ->andReturn(true)
            ->once();
            
        $user->shouldReceive('can')
            ->with('user_cannot')
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
        $emptyClosure = function () {};

        $oneRoleFilterName = $this->makeFilterName($route, [$oneRole]);
        $manyRoleFilterName = $this->makeFilterName($route, $manyRole);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($oneRoleFilterName, $manyRoleFilterName), m::type('Closure'))
            ->twice()->ordered();
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($oneRoleFilterName, $manyRoleFilterName), m::mustBe($emptyClosure))
            ->twice()->ordered();
            
        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($oneRoleFilterName, $manyRoleFilterName))
            ->times(4);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $entrust->routeNeedsRole($route, $oneRole);
        $entrust->routeNeedsRole($route, $manyRole);    
        $entrust->routeNeedsRole($route, $oneRole, $emptyClosure);
        $entrust->routeNeedsRole($route, $manyRole, $emptyClosure);     
    }
    
    public function testFilterGeneratedByRouteNeedsRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = m::mock('Zizaco\Entrust\Entrust[hasRole]', [$app]);
        $facadeApp = m::mock('_mockedApplication');
        Facade::setFacadeApplication($facadeApp);
        
        $route = 'route';
        $userRoleA = 'UserRoleA';
        $userRoleB = 'UserRoleB';
        $nonUserRoleA = 'NonUserRoleA';
        $nonUserRoleB = 'NonUserRoleB';
        $nonUserRoles = [$nonUserRoleA, $nonUserRoleB];
        $customResponse = new stdClass();
        
        $roles = [];
        $isPassedCustomResponse = false;
        $isCumulative = false;

        $callFilterAndAssert = function ($filter) use (
            $nonUserRoles,
            $customResponse,
            &$roles,
            &$isPassedCustomResponse,
            &$isCumulative
        ) {
            if (!($filter instanceof Closure)) {
                return false;
            }
            
            $result = null;
            $numAgainst = count(array_intersect($nonUserRoles, $roles));
            $numTotal = count($roles);
            $isUserAuthorized = !($numAgainst > 0 && ($isCumulative || $numAgainst === $numTotal));

            try {
                $result = $filter();
            } catch(Exception $e) {
                $this->assertSame('abort', $e->getMessage());
                $this->assertFalse($isPassedCustomResponse);
                $this->assertFalse($isUserAuthorized);
                return true;
            }

            if ($isUserAuthorized) {
                $this->assertNull($result);
            } else {
                $this->assertSame($customResponse, $result);
            }

            return true;
        };
        
        $runTestCase = function (
            array $caseRoles,
            $result = null,
            $cumulative = true
        ) use (
            $entrust,
            $route,
            &$roles,
            &$isPassedCustomResponse,
            &$isCumulative
        ) {
            list($roles, $isPassedCustomResponse, $isCumulative)
                = [$caseRoles, !is_null($result), $cumulative];
            $entrust->routeNeedsRole(
                $route,
                $caseRoles,
                $result,
                $cumulative
            );      
        };
 
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::type('string'), m::on($callFilterAndAssert));
        $app->router->shouldReceive('when')
            ->with($route, m::type('string'));
            
        $facadeApp->shouldReceive('abort')
            ->with(403)->andThrow('Exception', 'abort');

        $entrust->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleA, $userRoleB))
            ->andReturn(true);
        $entrust->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleA, $nonUserRoleB))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */  
        // Case: User has both roles.
        $runTestCase([$userRoleA, $userRoleB]);
        $runTestCase([$userRoleA, $userRoleB], null, false);
        $runTestCase([$userRoleA, $userRoleB], $customResponse);
        $runTestCase([$userRoleA, $userRoleB], $customResponse, false);
        
        // Case: User lacks a role.
        $runTestCase([$nonUserRoleA, $userRoleB]);
        $runTestCase([$nonUserRoleA, $userRoleB], null, false);
        $runTestCase([$nonUserRoleA, $userRoleB], $customResponse);
        $runTestCase([$nonUserRoleA, $userRoleB], $customResponse, false);
        
        // Case: User lacks both roles.
        $runTestCase([$nonUserRoleA, $nonUserRoleB]);
        $runTestCase([$nonUserRoleA, $nonUserRoleB], null, false);
        $runTestCase([$nonUserRoleA, $nonUserRoleB], $customResponse);
        $runTestCase([$nonUserRoleA, $nonUserRoleB], $customResponse, false);    
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
        $emptyClosure = function () {};

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
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($onePermFilterName, $manyPermFilterName), m::mustBe($emptyClosure))
            ->twice()->ordered();
            
        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($onePermFilterName, $manyPermFilterName))
            ->times(4);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $entrust->routeNeedsPermission($route, $onePerm);
        $entrust->routeNeedsPermission($route, $manyPerm);  
        $entrust->routeNeedsPermission($route, $onePerm, $emptyClosure);
        $entrust->routeNeedsPermission($route, $manyPerm, $emptyClosure);       
    }
    
    public function testFilterGeneratedByRouteNeedsPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = m::mock('Zizaco\Entrust\Entrust[can]', [$app]);
        $facadeApp = m::mock('_mockedApplication');
        Facade::setFacadeApplication($facadeApp);
        
        $route = 'route';
        $userPermA = 'user_can_a';
        $userPermB = 'user_can_b';
        $nonUserPermA = 'user_cannot_a';
        $nonUserPermB = 'user_cannot_b';
        $nonUserPerms = [$nonUserPermA, $nonUserPermB];
        $customResponse = new stdClass();
        
        $perms = [];
        $isPassedCustomResponse = false;
        $isCumulative = false;

        $callFilterAndAssert = function ($filter) use (
            $nonUserPerms,
            $customResponse,
            &$perms,
            &$isPassedCustomResponse,
            &$isCumulative
        ) {
            if (!($filter instanceof Closure)) {
                return false;
            }
            
            $result = null;
            $numAgainst = count(array_intersect($nonUserPerms, $perms));
            $numTotal = count($perms);
            $isUserAuthorized = !($numAgainst > 0 && ($isCumulative || $numAgainst === $numTotal));

            try {
                $result = $filter();
            } catch(Exception $e) {
                $this->assertSame('abort', $e->getMessage());
                $this->assertFalse($isPassedCustomResponse);
                $this->assertFalse($isUserAuthorized);
                return true;
            }

            if ($isUserAuthorized) {
                $this->assertNull($result);
            } else {
                $this->assertSame($customResponse, $result);
            }

            return true;
        };
        
        $runTestCase = function (
            array $casePerms,
            $result = null,
            $cumulative = true
        ) use (
            $entrust,
            $route,
            &$perms,
            &$isPassedCustomResponse,
            &$isCumulative
        ) {
            list($perms, $isPassedCustomResponse, $isCumulative)
                = [$casePerms, !is_null($result), $cumulative];
            $entrust->routeNeedsPermission(
                $route,
                $casePerms,
                $result,
                $cumulative
            );      
        };
 
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::type('string'), m::on($callFilterAndAssert));
        $app->router->shouldReceive('when')
            ->with($route, m::type('string'));
            
        $facadeApp->shouldReceive('abort')
            ->with(403)->andThrow('Exception', 'abort');

        $entrust->shouldReceive('can')
            ->with(m::anyOf($userPermA, $userPermB))
            ->andReturn(true);
        $entrust->shouldReceive('can')
            ->with(m::anyOf($nonUserPermA, $nonUserPermB))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */  
        // Case: User has both permissions.
        $runTestCase([$userPermA, $userPermB]);
        $runTestCase([$userPermA, $userPermB], null, false);
        $runTestCase([$userPermA, $userPermB], $customResponse);
        $runTestCase([$userPermA, $userPermB], $customResponse, false);
        
        
        // Case: User lacks a permission.
        $runTestCase([$nonUserPermA, $userPermB]);
        $runTestCase([$nonUserPermA, $userPermB], null, false);
        $runTestCase([$nonUserPermA, $userPermB], $customResponse, false);
        $runTestCase([$nonUserPermA, $userPermB], $customResponse);
        
        // Case: User lacks both permissions.
        $runTestCase([$nonUserPermA, $nonUserPermB]);
        $runTestCase([$nonUserPermA, $nonUserPermB], null, false);
        $runTestCase([$nonUserPermA, $nonUserPermB], $customResponse, false);
        $runTestCase([$nonUserPermA, $nonUserPermB], $customResponse);    
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
        $emptyClosure = function () {};

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
        $app->router->shouldReceive('filter')
            ->with(
                m::anyOf(
                    $oneRoleOnePermFilterName,
                    $oneRoleManyPermFilterName,
                    $manyRoleOnePermFilterName,
                    $manyRoleManyPermFilterName
                ), 
                m::mustBe($emptyClosure)
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
            ->times(8);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $entrust->routeNeedsRoleOrPermission($route, $oneRole, $onePerm);
        $entrust->routeNeedsRoleOrPermission($route, $oneRole, $manyPerm);
        $entrust->routeNeedsRoleOrPermission($route, $manyRole, $onePerm);
        $entrust->routeNeedsRoleOrPermission($route, $manyRole, $manyPerm);

        $entrust->routeNeedsRoleOrPermission($route, $oneRole, $onePerm, $emptyClosure);
        $entrust->routeNeedsRoleOrPermission($route, $oneRole, $manyPerm, $emptyClosure);
        $entrust->routeNeedsRoleOrPermission($route, $manyRole, $onePerm, $emptyClosure);
        $entrust->routeNeedsRoleOrPermission($route, $manyRole, $manyPerm, $emptyClosure);      
    }
    
    public function testFilterGeneratedByRouteNeedsRoleOrPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = m::mock('Zizaco\Entrust\Entrust[hasRole,can]', [$app]);
        $facadeApp = m::mock('_mockedApplication');
        Facade::setFacadeApplication($facadeApp);

        $route = 'route';
        $userRoleA = 'UserRoleA';
        $userRoleB = 'UserRoleB';
        $userPermA = 'user_can_a';
        $userPermB = 'user_can_b';
        $nonUserRoleA = 'NonUserRoleA';
        $nonUserRoleB = 'NonUserRoleB';
        $nonUserPermA = 'user_cannot_a';
        $nonUserPermB = 'user_cannot_b';
        $nonUserRolesPerms = [$nonUserRoleA, $nonUserRoleB, $nonUserPermA, $nonUserPermB];
        $customResponse = new stdClass();
        
        $roles = [];
        $perms = [];
        $isPassedCustomResponse = false;
        $isCumulative = false;

        $callFilterAndAssert = function ($filter) use (
            $nonUserRolesPerms,
            $customResponse,
            &$roles,
            &$perms,
            &$isPassedCustomResponse,
            &$isCumulative
        ) {
            if (!($filter instanceof Closure)) {
                return false;
            }

            $result = null;
            $numAgainst = count(array_intersect($nonUserRolesPerms, array_merge($roles, $perms)));
            $numTotal = count(array_merge($roles, $perms));
            $isUserAuthorized = !($numAgainst > 0 && ($isCumulative || $numAgainst === $numTotal));

            try {
                $result = $filter();
            } catch(Exception $e) {
                $this->assertSame('abort', $e->getMessage());
                $this->assertFalse($isPassedCustomResponse);
                $this->assertFalse($isUserAuthorized);
                return true;
            }

            if ($isUserAuthorized) {
                $this->assertNull($result);
            } else {
                $this->assertSame($customResponse, $result);
            }

            return true;
        };
        
        $runTestCase = function (
            array $caseRoles,
            array $casePerms,
            $result = null,
            $cumulative = false
        ) use (
            $entrust,
            $route,
            &$roles,
            &$perms,
            &$isPassedCustomResponse,
            &$isCumulative
        ) {
            list($roles, $perms, $isPassedCustomResponse, $isCumulative)
                = [$caseRoles, $casePerms, !is_null($result), $cumulative];
            $entrust->routeNeedsRoleOrPermission(
                $route,
                $caseRoles,
                $casePerms,
                $result,
                $cumulative
            );      
        };

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::type('string'), m::on($callFilterAndAssert));
        $app->router->shouldReceive('when')
            ->with($route, m::type('string'));
            
        $facadeApp->shouldReceive('abort')
            ->with(403)->andThrow('Exception', 'abort');
            
        $entrust->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleA, $userRoleB))
            ->andReturn(true);
        $entrust->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleA, $nonUserRoleB))
            ->andReturn(false);
        $entrust->shouldReceive('can')
            ->with(m::anyOf($userPermA, $userPermB))
            ->andReturn(true);
        $entrust->shouldReceive('can')
            ->with(m::anyOf($nonUserPermA, $nonUserPermB))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */  
        // Case: User has everything.
        $runTestCase([$userRoleA, $userRoleB], [$userPermA, $userPermB]);
        $runTestCase([$userRoleA, $userRoleB], [$userPermA, $userPermB], null, true);
        $runTestCase([$userRoleA, $userRoleB], [$userPermA, $userPermB], $customResponse, true);
        $runTestCase([$userRoleA, $userRoleB], [$userPermA, $userPermB], $customResponse);
        
        // Case: User lacks a role.
        $runTestCase([$nonUserRoleA, $userRoleB], [$userPermA, $userPermB]);
        $runTestCase([$nonUserRoleA, $userRoleB], [$userPermA, $userPermB], null, true);
        $runTestCase([$nonUserRoleA, $userRoleB], [$userPermA, $userPermB], $customResponse, true);
        $runTestCase([$nonUserRoleA, $userRoleB], [$userPermA, $userPermB], $customResponse);
        
        // Case: User lacks a permission.
        $runTestCase([$userRoleA, $userRoleB], [$nonUserPermA, $userPermB]);
        $runTestCase([$userRoleA, $userRoleB], [$nonUserPermA, $userPermB], null, true);
        $runTestCase([$userRoleA, $userRoleB], [$nonUserPermA, $userPermB], $customResponse, true);
        $runTestCase([$userRoleA, $userRoleB], [$nonUserPermA, $userPermB], $customResponse);
        
        // Case: User lacks a role and a permission.
        $runTestCase([$nonUserRoleA, $userRoleB], [$nonUserPermA, $userPermB]);
        $runTestCase([$nonUserRoleA, $userRoleB], [$nonUserPermA, $userPermB], null, true);
        $runTestCase([$nonUserRoleA, $userRoleB], [$nonUserPermA, $userPermB], $customResponse, true);
        $runTestCase([$nonUserRoleA, $userRoleB], [$nonUserPermA, $userPermB], $customResponse);
        
        // Case: User lacks everything.
        $runTestCase([$nonUserRoleA, $nonUserRoleB], [$nonUserPermA, $nonUserPermB]);
        $runTestCase([$nonUserRoleA, $nonUserRoleB], [$nonUserPermA, $nonUserPermB], null, true);
        $runTestCase([$nonUserRoleA, $nonUserRoleB], [$nonUserPermA, $nonUserPermB], $customResponse, true);
        $runTestCase([$nonUserRoleA, $nonUserRoleB], [$nonUserPermA, $nonUserPermB], $customResponse);
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
