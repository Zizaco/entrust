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

}

class TestingModel
{
    use HasRole;

    public $roles = array();

    function __construct()
    {
        // Simulates Eloquent's relation access
        $role_a = m::mock('Role'); $role_a->name = "AdminA";
        $role_a->permissions = array('manage_a','manage_b');

        $role_b = m::mock('Role'); $role_b->name = "AdminB";
        $role_b->permissions = array('manage_b','manage_c');

        $this->roles = array($role_a, $role_b);
    }

    // Because this method is called by the trait
    public function belongsToMany($model, $table) {}
}
