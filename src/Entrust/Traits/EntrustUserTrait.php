<?php namespace Zizaco\Entrust\Traits;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

trait EntrustUserTrait
{
    //Big block of caching functionality.
    public function cachedRoles()
    {
        $userPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_roles_for_user_' . $this->$userPrimaryKey;
        return Cache::tags(Config::get('entrust.role_user_table'))->remember($cacheKey, Config::get('cache.ttl'), function () {
            return $this->roles()->get();
        });
    }

    public function save(array $options = [])
    {   //both inserts and updates
        parent::save($options);
        Cache::tags(Config::get('entrust.role_user_table'))->flush();
    }

    public function delete(array $options = [])
    {   //soft or hard
        parent::delete($options);
        Cache::tags(Config::get('entrust.role_user_table'))->flush();
    }

    public function restore()
    {   //soft delete undo's
        parent::restore();
        Cache::tags(Config::get('entrust.role_user_table'))->flush();
    }

    /**
     * Many-to-Many relations with Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Config::get('entrust.role'), Config::get('entrust.role_user_table'), Config::get('entrust.user_foreign_key'), Config::get('entrust.role_foreign_key'));
    }

    /**
     * Boot the user model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the user model uses soft deletes.
     *
     * @return void|bool
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            if (!method_exists(Config::get('auth.model'), 'bootSoftDeletes')) {
                $user->roles()->sync([]);
            }

            return true;
        });
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param string|array $name Role name or array of role names.
     * @param bool $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasRole($name, $requireAll = false)
    {
        if (is_array($name))
        {
            return $this->has('hasRole',$name, $requireAll);
        }

        foreach ($this->cachedRoles() as $role) {
            if ($role->name == $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function can($permission, $requireAll = false)
    {
        if (is_array($permission))
        {
            return $this->has('can',$permission, $requireAll);
        }

        foreach ($this->cachedRoles() as $role) {
            // Validate against the Permission table
            foreach ($role->cachedPermissions() as $perm) {
                if (str_is($permission, $perm->name)) {
                    return true;
                }
            }
        }
        return false;

    }


    /**
     * Checks role(s) and permission(s).
     *
     * @param string|array $roles Array of roles or comma separated string
     * @param string|array $permissions Array of permissions or comma separated string.
     * @param array $options validate_all (true|false) or return_type (boolean|array|both)
     *
     * @throws \InvalidArgumentException
     *
     * @return array|bool
     */
    public function ability($roles, $permissions, $options = [])
    {
        // Convert string to array if that's what is passed in.
        $permissions = $this->seprateBycomma($permissions);
        $roles = $this->seprateBycomma($roles);

        // Set up default values and validate options.
        if (!isset($options['validate_all'])) {
            $options['validate_all'] = false;
        }
        if (!is_bool($options['validate_all'])) {
            throw new InvalidArgumentException();
        }

        if (!isset($options['return_type'])) {
            $options['return_type'] = 'boolean';
        }
        if ( !in_array($options['return_type'], ['boolean','array','both',] )) {
            throw new InvalidArgumentException();
        }


        // Loop through roles and permissions and check each.
        $checkedRoles = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->can($permission);
        }

        $validateAll = false;
        // If validate all and there is a false in either
        // Check that if validate all, then there should not be any false.
        // Check that if not validate all, there must be at least one true.
        if (($options['validate_all'] && $this->hasAllRolesAndPermissions($checkedRoles, $checkedPermissions))  ||
            (!$options['validate_all'] && (in_array(true, $checkedRoles) || in_array(true, $checkedPermissions)))
        ) {
            $validateAll = true;
        }

        // Return based on option
        if ($options['return_type'] == 'boolean') {
            return $validateAll;
        }
        if ($options['return_type'] == 'array') {
            return ['roles' => $checkedRoles, 'permissions' => $checkedPermissions];
        }
        return [$validateAll, ['roles' => $checkedRoles, 'permissions' => $checkedPermissions]];


    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $role
     */
    public function attachRole($role)
    {
        $role = $this->getRole($role);
        $this->roles()->attach($role);
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     */
    public function detachRole($role)
    {
        $role = $this->getRole($role);
        $this->roles()->detach($role);
    }

    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     */
    public function attachRoles($roles)
    {
        foreach ($roles as $role) {
            $this->attachRole($role);
        }
    }

    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     */
    public function detachRoles($roles = null)
    {
        if (!$roles) $roles = $this->roles()->get();

        foreach ($roles as $role) {
            $this->detachRole($role);
        }
    }

    /**
     * @param $checkedRoles
     * @param $checkedPermissions
     * @return bool
     */
    private function hasAllRolesAndPermissions($checkedRoles, $checkedPermissions)
    {
        return !in_array(false, $checkedRoles) and !in_array(false, $checkedPermissions);
    }

    /**
     * @param $permissions
     * @return array
     */
    private function seprateBycomma($permissions)
    {
        if (!is_array($permissions)) {
            $permissions = explode(',', $permissions);
            return $permissions;
        }
        return $permissions;
    }

    /**
     * @param $role
     * @return mixed
     */
    private function getRole($role)
    {
        if (is_object($role)) {
            $role = $role->getKey();
        }

        if (is_array($role)) {
            $role = $role['id'];
            return $role;
        }
        return $role;
    }

    /**
     * @param $permission
     * @param $requireAll
     * @return bool
     */
    private function has($method,$permission, $requireAll)
    {
        foreach ($permission as $permName) {
            $hasPerm = $this->$method($permName);

            if ($hasPerm && !$requireAll) {
                return true;
            }
            if (!$hasPerm && $requireAll) {
                return false;
            }
        }

        // If we've made it this far and $requireAll is FALSE, then NONE of the perms were found
        // If we've made it this far and $requireAll is TRUE, then ALL of the perms were found.
        // Return the value of $requireAll;
        return $requireAll;
    }

}
