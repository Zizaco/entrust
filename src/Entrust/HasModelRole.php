<?php namespace Zizaco\Entrust;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\Process\Exception\InvalidArgumentException;

trait HasModelRole
{
    /**
     * Checks if the user has a Role for this resource by its name.
     *
     * @param string $name Role name.
     *
     * @return bool
     */
    public function hasRole($name)
    {
        return Facade::getFacadeApplication()->auth->user()->hasRole($name, __CLASS__, $this->id);
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string $permission Permission string.
     *
     * @return bool
     */
    public function can($permission)
    {
        return Facade::getFacadeApplication()->auth->user()->can($name, __CLASS__, $this->id);
    }

    /**
     * Checks role(s) and permission(s).
     *
     * @param string|array $roles       Array of roles or comma separated string
     * @param string|array $permissions Array of permissions or comma separated string.
     * @param array        $options     validate_all (true|false) or return_type (boolean|array|both)
     *
     * @throws \InvalidArgumentException
     *
     * @return array|bool
     */
    public function ability($roles, $permissions, $options = array())
    {
        // Convert string to array if that's what is passed in.
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        if (!is_array($permissions)) {
            $permissions = explode(',', $permissions);
        }

        // Set up default values and validate options.
        if (!isset($options['validate_all'])) {
            $options['validate_all'] = false;
        } else {
            if ($options['validate_all'] != true && $options['validate_all'] != false) {
                throw new InvalidArgumentException();
            }
        }
        if (!isset($options['return_type'])) {
            $options['return_type'] = 'boolean';
        } else {
            if ($options['return_type'] != 'boolean' &&
                $options['return_type'] != 'array' &&
                $options['return_type'] != 'both') {
                throw new InvalidArgumentException();
            }
        }

        // Loop through roles and permissions and check each.
        $checkedRoles = array();
        $checkedPermissions = array();
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role);
        }
        foreach ($permissions as $permission) {
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
        if ($options['return_type'] == 'boolean') {
            return $validateAll;
        } elseif ($options['return_type'] == 'array') {
            return array('roles' => $checkedRoles, 'permissions' => $checkedPermissions);
        } else {
            return array($validateAll, array('roles' => $checkedRoles, 'permissions' => $checkedPermissions));
        }

    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $role
     *
     * @return void
     */
    public function attachRole($role)
    {
        Facade::getFacadeApplication()->auth->user()->attachRole($role, __CLASS__, $this->id);
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     *
     * @return void
     */
    public function detachRole($role)
    {
        Facade::getFacadeApplication()->auth->user()->detachRole($role, __CLASS__, $this->id);
    }

    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     *
     * @return void
     */
    public function attachRoles($roles)
    {
        Facade::getFacadeApplication()->auth->user()->attachRoles($roles, __CLASS__, $this->id);
    }

    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     *
     * @return void
     */
    public function detachRoles($roles)
    {
        Facade::getFacadeApplication()->auth->user()->detachRoles($roles, __CLASS__, $this->id);
    }
}
