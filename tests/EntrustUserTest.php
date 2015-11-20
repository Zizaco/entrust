<?php

use Zizaco\Entrust\Contracts\EntrustUserInterface;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Zizaco\Entrust\Permission;
use Zizaco\Entrust\Role;
use Mockery as m;

class EntrustUserTest extends PHPUnit_Framework_TestCase
{
    private $facadeMocks = array();
    
    public function setUp()
    {
        parent::setUp();
        
        $app = m::mock('app')->shouldReceive('instance')->getMock();
        
        $this->facadeMocks['config'] = m::mock('config');
        $this->facadeMocks['cache'] = m::mock('cache');
        
        Config::setFacadeApplication($app);
        Config::swap($this->facadeMocks['config']);
        
        Cache::setFacadeApplication($app);
        Cache::swap($this->facadeMocks['cache']);
    }
    
    public function tearDown()
    {
        m::close();
    }

    public function testRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $belongsToMany = new stdClass();
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('belongsToMany')
            ->with('role_table_name', 'assigned_roles_table_name', 'user_id', 'role_id')
            ->andReturn($belongsToMany)
            ->once();

        Config::shouldReceive('get')->once()->with('entrust.role')
            ->andReturn('role_table_name');
        Config::shouldReceive('get')->once()->with('entrust.role_user_table')
            ->andReturn('assigned_roles_table_name');
        Config::shouldReceive('get')->once()->with('entrust.user_foreign_key')
            ->andReturn('user_id');
        Config::shouldReceive('get')->once()->with('entrust.role_foreign_key')
            ->andReturn('role_id');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($belongsToMany, $user->roles());
    }

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(9)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(9)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(9)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasRole('RoleA'));
        $this->assertTrue($user->hasRole('RoleB'));
        $this->assertFalse($user->hasRole('RoleC'));

        $this->assertTrue($user->hasRole(['RoleA', 'RoleB']));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC']));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], true));
        $this->assertFalse($user->hasRole(['RoleC', 'RoleD']));
    }

    public function testCan()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('manage_a');
        $permB = $this->mockPermission('manage_b');
        $permC = $this->mockPermission('manage_c');

        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(11)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(7)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(11)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(11)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(11)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->can('manage_a'));
        $this->assertTrue($user->can('manage_b'));
        $this->assertTrue($user->can('manage_c'));
        $this->assertFalse($user->can('manage_d'));

        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_c']));
        $this->assertTrue($user->can(['manage_a', 'manage_b', 'manage_d']));
        $this->assertFalse($user->can(['manage_a', 'manage_b', 'manage_d'], true));
        $this->assertFalse($user->can(['manage_d', 'manage_e']));
    }


    public function testCanWithPlaceholderSupport ()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('admin.posts');
        $permB = $this->mockPermission('admin.pages');
        $permC = $this->mockPermission('admin.users');

        $role = $this->mockRole('Role');

        $role->perms = [$permA, $permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$role];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $role->shouldReceive('cachedPermissions')->times(6)->andReturn($role->perms);
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(6)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(6)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(6)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->can('admin.posts'));
        $this->assertTrue($user->can('admin.pages'));
        $this->assertTrue($user->can('admin.users'));
        $this->assertFalse($user->can('admin.config'));

        $this->assertTrue($user->can(['admin.*']));
        $this->assertFalse($user->can(['site.*']));
    }
    
    
    public function testAbilityShouldReturnBoolean()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(16)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(12)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(32)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(32)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(32)->andReturn($user->roles);
        
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            )
        );
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a role.
        $this->assertTrue(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            )
        );
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a permission.
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB]
            )
        );
        $this->assertFalse(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => true]
            )
        );

        // Case: User lacks everything.
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB]
            )
        );
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => true]
            )
        );
    }

    public function testAbilityShouldReturnArray()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';


        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(16)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(12)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(32)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(32)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(32)->andReturn($user->roles);
        
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                'permissions' => [$userPermNameA => true, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                'permissions' => [$userPermNameA => true, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );


        // Case: User lacks a role.
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );


        // Case: User lacks a permission.
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );


        // Case: User lacks everything.
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
    }

    public function testAbilityShouldReturnBoth()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(16)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(12)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(32)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(32)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(32)->andReturn($user->roles);
        
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                    'permissions' => [$userPermNameA => true, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                    'permissions' => [$userPermNameA => true, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );


        // Case: User lacks a role.
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                    'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                    'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );


        // Case: User lacks a permission.
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                    'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                    'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );


        // Case: User lacks everything.
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                    'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                    'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );
    }

    public function testAbilityShouldAcceptStrings()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('user_can_a');
        $permB = $this->mockPermission('user_can_b');
        $permC = $this->mockPermission('user_can_c');

        $roleA = $this->mockRole('UserRoleA');
        $roleB = $this->mockRole('UserRoleB');

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(4)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(2)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(8)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(8)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(8)->andReturn($user->roles);
        
        $user->shouldReceive('hasRole')
            ->with(m::anyOf('UserRoleA', 'UserRoleB'), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with('NonUserRoleB', m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf('user_can_a', 'user_can_b', 'user_can_c'), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with('user_cannot_b', m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            $user->ability(
                ['UserRoleA', 'NonUserRoleB'],
                ['user_can_a', 'user_cannot_b'],
                ['return_type' => 'both']
            ),
            $user->ability(
                'UserRoleA,NonUserRoleB',
                'user_can_a,user_cannot_b',
                ['return_type' => 'both']
            )
        );
    }

    public function testAbilityDefaultOptions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleA->shouldReceive('cachedPermissions')->times(16)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(12)->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('entrust.role_user_table')->times(32)->andReturn('role_user');
        Config::shouldReceive('get')->with('cache.ttl')->times(32)->andReturn('1440');
        Cache::shouldReceive('tags->remember')->times(32)->andReturn($user->roles);
        
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            ),
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );


        // Case: User lacks a role.
        $this->assertSame(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            ),
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );


        // Case: User lacks a permission.
        $this->assertSame(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB]
            ),
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );


        // Case: User lacks everything.
        $this->assertSame(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB]
            ),
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );
    }

    public function testAbilityShouldThrowInvalidArgumentException()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('manage_a');

        $roleA = $this->mockRole('RoleA');
        $roleA->perms = [$permA];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA];
        $user->id = 4;
        $user->primaryKey = 'id';

        function isExceptionThrown(
            HasRoleUser $user,
            array $roles,
            array $perms,
            array $options
        ) {
            $isExceptionThrown = false;

            try {
                $user->ability($roles, $perms, $options);
            } catch (InvalidArgumentException $e) {
                $isExceptionThrown = true;
            }

            return $isExceptionThrown;
        }

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasRole')
            ->times(3);
        $user->shouldReceive('can')
            ->times(3);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertFalse(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'boolean']));
        $this->assertFalse(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'array']));
        $this->assertFalse(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'both']));
        $this->assertTrue(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'potato']));
    }

    public function testAttachRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleObject = m::mock('Role');
        $roleArray = ['id' => 2];

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('roles')
            ->andReturn($user);
        $user->shouldReceive('attach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('attach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('attach')
            ->with(3)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->attachRole($roleObject);
        $user->attachRole($roleArray);
        $user->attachRole(3);
    }

    public function testDetachRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleObject = m::mock('Role');
        $roleArray = ['id' => 2];

        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $roleObject->shouldReceive('getKey')
            ->andReturn(1);

        $user->shouldReceive('roles')
            ->andReturn($user);
        $user->shouldReceive('detach')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detach')
            ->with(3)
            ->once()->ordered();


        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->detachRole($roleObject);
        $user->detachRole($roleArray);
        $user->detachRole(3);
    }

    public function testAttachRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('attachRole')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('attachRole')
            ->with(3)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->attachRoles([1, 2, 3]);
    }

    public function testDetachRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('detachRole')
            ->with(1)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(2)
            ->once()->ordered();
        $user->shouldReceive('detachRole')
            ->with(3)
            ->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->detachRoles([1, 2, 3]);
    }

    public function testDetachAllRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];

        $relationship = m::mock('BelongsToMany');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('entrust.role')->once()->andReturn('App\Role');
        Config::shouldReceive('get')->with('entrust.role_user_table')->once()->andReturn('role_user');
        Config::shouldReceive('get')->with('entrust.user_foreign_key')->once()->andReturn('user_id');
        Config::shouldReceive('get')->with('entrust.role_foreign_key')->once()->andReturn('role_id');

        $relationship->shouldReceive('get')
                     ->andReturn($user->roles)->once();

        $user->shouldReceive('belongsToMany')
                    ->andReturn($relationship)->once();

        $user->shouldReceive('detachRole')->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $user->detachRoles();

    }

    protected function mockPermission($permName)
    {
        $permMock = m::mock('Zizaco\Entrust\Permission');
        $permMock->name = $permName;
        $permMock->display_name = ucwords(str_replace('_', ' ', $permName));
        $permMock->id = 1;

        return $permMock;
    }

    protected function mockRole($roleName)
    {
        $roleMock = m::mock('Zizaco\Entrust\Role');
        $roleMock->name = $roleName;
        $roleMock->perms = [];
        $roleMock->permissions = [];
        $roleMock->id = 1;

        return $roleMock;
    }
}

class HasRoleUser implements EntrustUserInterface
{
    use EntrustUserTrait;

    public $roles;
    public $primaryKey;
    public $id;
    
    public function __construct() {
        $this->primaryKey = 'id';
        $this->id = 4;
    }

    public function belongsToMany($role, $assignedRolesTable)
    {

    }
}
