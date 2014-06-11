<?php namespace Zizaco\Entrust;

use Symfony\Component\Process\Exception\InvalidArgumentException, DB, Config;

trait HasRole
{
    /**
     * Many-to-Many relations with Role
     */
    public function roles()
    {
        return $this->belongsToMany(Config::get('entrust::role'), Config::get('entrust::assigned_roles_table'))
            ->withTimestamps()
            ->whereRaw('(assigned_roles.expires_at > NOW() OR assigned_roles.expires_at IS NULL)')
            ->whereNull('assigned_roles.deleted_at');
    }

    /**
     * Many-to-Many relations with Role
     */
    public function inactiveRoles()
    {
        return $this->belongsToMany('Role', 'assigned_roles')
            ->withTimestamps()
            ->whereRaw('assigned_roles.expires_at <= NOW() OR assigned_roles.deleted_at IS NOT NULL');
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
            // Deprecated permission value within the role table.
            if( is_array($role->permissions) && in_array($permission, $role->permissions) )
            {
                return true;
            }

            // Validate against the Permission table
            foreach($role->perms as $perm) {
                if($perm->name == $permission) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks role(s) and permission(s) and returns bool, array or both
     * @param string|array $roles Array of roles or comma separated string
     * @param string|array $permissions Array of permissions or comma separated string.
     * @param array $options validate_all (true|false) or return_type (boolean|array|both) Default: false | boolean
     * @return array|bool
     * @throws InvalidArgumentException
     */
    public function ability( $roles, $permissions, $options=array() ) {
        // Convert string to array if that's what is passed in.
        if(!is_array($roles)){
            $roles = explode(',', $roles);
        }
        if(!is_array($permissions)){
            $permissions = explode(',', $permissions);
        }

        // Set up default values and validate options.
        if(!isset($options['validate_all'])) {
            $options['validate_all'] = false;
        } else {
            if($options['validate_all'] != true && $options['validate_all'] != false) {
                throw new InvalidArgumentException();
            }
        }
        if(!isset($options['return_type'])) {
            $options['return_type'] = 'boolean';
        } else {
            if($options['return_type'] != 'boolean' &&
                $options['return_type'] != 'array' &&
                $options['return_type'] != 'both') {
                throw new InvalidArgumentException();
            }
        }

        // Loop through roles and permissions and check each.
        $checkedRoles = array();
        $checkedPermissions = array();
        foreach($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role);
        }
        foreach($permissions as $permission) {
            $checkedPermissions[$permission] = $this->can($permission);
        }

        // If validate all and there is a false in either
        // Check that if validate all, then there should not be any false.
        // Check that if not validate all, there must be at least one true.
        if(($options['validate_all'] && !(in_array(false,$checkedRoles) || in_array(false,$checkedPermissions))) ||
            (!$options['validate_all'] && (in_array(true,$checkedRoles) || in_array(true,$checkedPermissions)))) {
            $validateAll = true;
        } else {
            $validateAll = false;
        }

        // Return based on option
        if($options['return_type'] == 'boolean') {
            return $validateAll;
        } elseif($options['return_type'] == 'array') {
            return array('roles' => $checkedRoles, 'permissions' => $checkedPermissions);
        } else {
            return array($validateAll, array('roles' => $checkedRoles, 'permissions' => $checkedPermissions));
        }

    }

    /**
     * Accept a role and attach it to the user IFF the user does not already 
     * have the role specified
     * 
     * @param mixed $role array or Role
     * @param array $attributes allows the caller to specify addtional 
     *  attributes to write onto the assigned_roles join table such as 
     *  expires_at and subscription_id
     * @return void
     */
    public function attachRole( $role, $attributes=[] )
    {
        if( is_array($role) )
        {
            $role = Role::findorfail( $role['id'] );
        }

        // Only attach the role if it is not already attached. Fail silently if 
        // the role is already attached since the objective of the call is 
        // accomplished
        if ( ! $this->hasRole($role->name) ) 
        {
            $this->roles()->attach( $role->getKey(), $attributes );
        }
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

        $assingedRole = DB::table('assigned_roles')
            ->where('user_id', $this->id )
            ->where('role_id', $role)
            ->update( ['deleted_at' => DB::raw('NOW()')] );
    }

    /**
     * Attach multiple roles to a user
     *
     * @param $roles
     * @access public
     * @return void
     */
    public function attachRoles($roles)
    {
        foreach ($roles as $role)
        {
            $this->attachRole($role);
        }
    }

    /**
     * Detach multiple roles from a user
     *
     * @param $roles
     * @access public
     * @return void
     */
    public function detachRoles($roles)
    {
        foreach ($roles as $role)
        {
            $this->detachRole($role);
        }
    }
}
