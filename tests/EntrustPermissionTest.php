<?php

use Zizaco\Entrust\EntrustPermission;

class EntrustPermissionTest extends PHPUnit_Framework_TestCase {

    /**
     * EntrustPermission instance
     *
     * @var Zizaco\Entrust\EntrustPermission
     *
     * @access protected
     */
    protected $permission;

    public function setUp()
    {
        $this->permission = new EntrustPermission;
    }
    public function testShouldEncodeJsonBeforeSave()
    {
        return true;
        $permissions = $this->permissionArray();

        $this->role->permissions = $permissions;

        // Before save should serialize permissions to save
        // as text into the database
        $this->role->beforeSave();

        $this->assertTrue( is_string($this->role->permissions) );
        $this->assertEquals( $this->role->permissions, json_encode($permissions) );
    }

    /*
        public function testShouldDecodeJsonAfterSave()
        {
            $permissions = $this->permissionArray();

            $this->role->permissions = json_encode($permissions);

            // After save should un-serialize permissions to be
            // usable again
            $this->role->afterSave( true );

            $this->assertTrue( is_array($this->role->permissions) );
            $this->assertEquals( $this->role->permissions, $permissions );
        }

        public function testShouldDecodeJsonFromDatabase()
        {
            $permissions = $this->permissionArray();

            $attributes = array(
                'name'=>'Administrator',
                'permissions'=>json_encode($permissions) // encoded as in database
            );

            // When an serialized permission comes from the database
            // it may become an array within the object.
            $this->role->setRawAttributes( $attributes );

            $this->assertTrue( is_array($this->role->permissions) );
            $this->assertEquals( $this->role->permissions, $permissions );
        }

        private function permissionArray()
        {
            return array( 'manage_stuff','do_something' );
        }
    */
}
