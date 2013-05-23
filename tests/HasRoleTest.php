<?php

use Zizaco\Entrust\HasRole;
use Mockery as m;

class HasRoleTest extends PHPUnit_Framework_TestCase {

    public function tearDown()
    {
        m::close();
    }

    public function testHasRole()
    {
        $model = new TestingModel;

        $this->assertTrue( $model->hasRole( 'AdminA' ) );
        $this->assertTrue( $model->hasRole( 'AdminB' ) );
        $this->assertFalse($model->hasRole( 'AdminC' ) );
    }

    public function testCan()
    {
        $model = new TestingModel;

        $this->assertTrue( $model->can( 'manage_a' ) );
        $this->assertTrue( $model->can( 'manage_b' ) );
        $this->assertTrue( $model->can( 'manage_c' ) );
        $this->assertFalse($model->can( 'manage_d' ) );
    }

    public function testAbility()
    {
        $model = new TestingModel;

        $this->assertTrue( $model->ability( 'AdminA', 'manage_a', array('validate_all' => true, 'return_type' => 'boolean' ) ) );
        $this->assertFalse( $model->ability( 'AdminA', 'wrong_permission', array('validate_all' => true, 'return_type' => 'boolean' ) ) );
        $this->assertTrue( $model->ability( 'AdminA', 'manage_b', array('validate_all' => false, 'return_type' => 'boolean' ) ) );
        $this->assertTrue( $model->ability( 'AdminA', 'manage_b' ) );
        $this->assertTrue( $model->ability( 'AdminA', 'wrong_permission', array('validate_all' => false, 'return_type' => 'boolean' ) ) );
        $this->assertTrue( $model->ability( 'AdminA', 'wrong_permission' ) );
        $this->assertArrayHasKey('roles', $model->ability( 'AdminA', 'manage_a', array('validate_all' => true, 'return_type' => 'array' ) ) );
        $this->assertArrayHasKey('permissions', $model->ability( 'AdminA', 'wrong_permission', array('validate_all' => true, 'return_type' => 'array' ) ) );
    }

}

class TestingModel
{
    use HasRole;

    public $roles = array();
    public $perms = array();

    function __construct()
    {
        // Simulates Eloquent's relation access
        $role_a = m::mock('Role'); $role_a->name = "AdminA";
        $role_a->permissions = array('manage_a','manage_b');

        $role_b = m::mock('Role'); $role_b->name = "AdminB";
        $role_b->permissions = array('manage_b','manage_c');

        $this->roles = array($role_a, $role_b);

        // Simulates Eloquent's relation access
        $permission_a = m::mock('Permission'); $permission_a->name = "manage_a";
        $permission_a->display_name = "manage a";
        $permission_b = m::mock('Permission'); $permission_b->name = "manage_b";
        $permission_b->display_name = "manage b";

        $this->perms = array($permission_a, $permission_b);

        $role_a->perms = $this->perms;
        $role_b->perms = $this->perms;
    }

    // Because this method is called by the trait
    public function belongsToMany($model, $table) {}
}
