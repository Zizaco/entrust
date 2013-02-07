<?php namespace Zizaco\Entrust;

trait HasRole
{
    public function roles()
    {
        return $this->belongsToMany('Role', 'assigned_roles');
    }

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
}
