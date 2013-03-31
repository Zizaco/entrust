<?php namespace Zizaco\Entrust;

trait HasRole
{
    /**
     * Many-to-Many relations with Role
     */
    public function roles()
    {
        return $this->belongsToMany('Role', 'assigned_roles');
    }

    /**
     * Checks if the user has a Role by its name
     * 
     * @param string $name Role name.
     *
     * @access public
     *
     * @return boolean
     */
    public function hasRole( $name )
    {
        foreach ($this->roles as $role) {
            if( $role->name == $name )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has a permission by its name
     * 
     * @param string $permission Permission string.
     *
     * @access public
     *
     * @return boolean
     */
    public function can( $permission )
    {
        foreach ($this->roles as $role) {
            if( is_array($role->permissions) && in_array($permission, $role->permissions) )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Alias to eloquent many-to-many relation's
     * attach() method
     * 
     * @param mixed $role
     *
     * @access public
     *
     * @return void
     */
    public function attachRole( $role )
    {
        if( is_object($role))
            $role = $role->getKey();

        if( is_array($role))
            $role = $role['id'];

        $this->roles()->attach( $role );
    }

    /**
     * Alias to eloquent many-to-many relation's
     * detach() method
     *
     * @param mixed $role
     *
     * @access public
     *
     * @return void
     */
    public function detachRole( $role )
    {
        if( is_object($role))
            $role = $role->getKey();

        if( is_array($role))
            $role = $role['id'];

        $this->roles()->detach( $role );
    }
}
