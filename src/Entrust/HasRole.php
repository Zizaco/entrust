<?php namespace Zizaco\Entrust;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\InvalidArgumentException;

trait HasRole
{
    /**
     * Many-to-Many relations with Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
      return $this->belongsToMany(Config::get('entrust::role'), Config::get('entrust::assigned_roles_table'), 'user_id', 'role_id')->withPivot('model_name', 'model_id')->withTimestamps();
    }

    /**
     * Checks if the user has a Role by its name.
     *
     * @param string $name Role name.
     *
     * @return bool
     */
    public function hasRole($name, $modelName=false, $modelId=false)
    {
        foreach ($this->roles as $role) {
            if ($role->name == $name) {
                if ($this->_checkRole($role, $modelName, $modelId)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function _checkRole($role, $modelName=false, $modelId=false)
    {
        $pivotModelName = $role->pivot->model_name;
        // is the role check for a model class?
        if ($modelName) {
            $pivotModelId = $role->pivot->model_id;
            // does this role's model class match the named class?
            if ($pivotModelName == $modelName) {
                if (!empty($modelId)) { // is the role check for a model instance?
                    if ($pivotModelId == $modelId) {
                        // error_log("MATCH: Instance level role permits instance level check on " . $role->name . ": '{$pivotModelName}[$pivotModelId]' vs '{$modelName}[$modelId]'");
                        return true;
                    } else if (empty($pivotModelId)) {
                        // error_log("MATCH: Model level role permits instance level check on " . $role->name . ": '{$pivotModelName}[$pivotModelId]' vs '{$modelName}[$modelId]'");
                        return true;
                    } else {
                        // error_log("NO MATCH: Instance level role does not permit instance level check on " . $role->name . ": '{$pivotModelName}[$pivotModelId]' vs '{$modelName}[$modelId]'");
                    }
                // if the role's model id is unset, this role applies to all instances of the class
                } else if (empty($pivotModelId)) {
                    // error_log("MATCH: Model level role permits instance level check on " . $role->name . ": '{$pivotModelName}[$pivotModelId]' vs '{$modelName}[$modelId]'");
                    return true;
                } else {
                  // error_log("NO MATCH: Instance level role does not permit model level role " .$role->name . ": '{$pivotModelName}[$pivotModelId]' vs '{$modelName}[$modelId]'");
                }
            } else if (empty($pivotModelName)) {
                // error_log("MATCH: Global level role permits model level check " .$role->name . ": '$pivotModelName' vs '$modelName'");
                return true;
            } else if ($pivotModelId) {
                // error_log("NO MATCH: Instance level role does not permit model level check " .$role->name . ": '{$pivotModelName}[$pivotModelId]' vs '{$modelName}[$modelId]'");
            } else {
                // error_log("NO MATCH: Model level role does not permit model level check " .$role->name . ": '{$pivotModelName}[$pivotModelId]' vs '{$modelName}[$modelId]'");
            }
        // if the role's model is unset, this is a global role and returns true
        } else if (empty($pivotModelName)) {
            // error_log("MATCH: Global level role permits global level check " . $role->name );
            return true;
        } else if ($role->pivot->model_id) {
            // error_log("NO MATCH: Instance level role does not permit global level check " . $role->name . ": '$pivotModelName' vs '$modelName'" );
        } else {
            // error_log("NO MATCH: Model level role does not permit global level check " . $role->name . ": '$pivotModelName' vs '$modelName'" );
        }

        return false;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string $permission Permission string.
     *
     * @return bool
     */
    public function can($permission, $modelName = false, $modelId = false)
    {
        foreach ($this->roles as $role) {
            if ($this->_checkRole($role, $modelName, $modelId)) {
                // Deprecated permission value within the role table.
                if (is_array($role->permissions) && in_array($permission, $role->permissions) ) {
                    return true;
                }

                // Validate against the Permission table
                foreach ($role->perms as $perm) {
                    if ($perm->name == $permission) {
                        return true;
                    }
                }
            }
        }

        return false;
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

        // check for model type and id
        $modelName = false;
        if (isset($options['model_name'])) {
            $modelName = $options['model_name'];
        }

        $modelId = false;
        if (isset($options['model_id'])) {
            $modelId = $options['model_id'];
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
            $checkedRoles[$role] = $this->hasRole($role, $modelName, $modelId);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->can($permission, $modelName, $modelId);
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
    public function attachRole($role, $modelName=null, $modelId=null)
    {
        if( is_object($role)) {
            $role = $role->getKey();
        }

        if( is_array($role)) {
            $role = $role['id'];
        }

        $this->roles()->attach( $role, array( 'model_name' => $modelName, 'model_id' => $modelId ));
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     *
     * @return void
     */
    public function detachRole($role, $modelName=null, $modelId=null)
    {
        if (is_object($role)) {
            $role = $role->getKey();
        }

        if (is_array($role)) {
            $role = $role['id'];
        }

        // $sql = sprintf("delete from %s where role_id = %s AND model_name = '%s' AND model_id = %s",
        //                Config::get('entrust::assigned_roles_table'),
        //                $role,
        //                $modelName,
        //                $modelId);
        // DB::delete($sql);
        $this->roles()->newPivotStatementForId($role)
             ->where('model_name', '=', $modelName)
             ->where('model_id', '=', $modelId)
             ->delete();

        //$this->roles()->detach($role);
    }

    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     *
     * @return void
     */
    public function attachRoles($roles, $modelName=null, $modelId=null)
    {
        foreach ($roles as $role) {
            $this->attachRole($role, $modelName, $modelId);
        }
    }

    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     *
     * @return void
     */
    public function detachRoles($roles, $modelName=null, $modelId=null)
    {
        foreach ($roles as $role) {
            $this->detachRole($role, $modelName, $modelId);
        }
    }
}
