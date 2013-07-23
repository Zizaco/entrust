<?php namespace Zizaco\Entrust;

use LaravelBook\Ardent\Ardent;
use SebastianBergmann\Exporter\Exception;

class EntrustRole extends Ardent
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * Ardent validation rules
     *
     * @var array
     */
    public static $rules = array(
      'name' => 'required|between:4,16'
    );

    /**
     * Many-to-Many relations with Users
     */
    public function users()
    {
        return $this->belongsToMany('User', 'assigned_roles');
    }

    /**
     * Many-to-Many relations with Permission
     * named perms as permissions is already taken.
     */
    public function perms()
    {
        // To maintain backwards compatibility we'll catch the exception if the Permission table doesn't exist.
        // TODO remove in a future version
        try {
            return $this->belongsToMany('Permission');
        } catch(Execption $e) {}
    }

    /**
     * We can change role's permissions by setting $role->permissions variable to an array of permission names
     *
     * @param array $permNamesArray
     */
    public function setPermissionsAttribute($permNamesArray)
    {
        if(is_array($permNamesArray)) {
            try {
                $perms = \DB::table('permissions')->whereIn('name', array_values($permNamesArray))->lists('name', 'id');
                $this->perms()->sync(array_keys($perms));
                $this->attributes['permissions'] = json_encode($perms);
                $this->permsAreSavedByAttr(true);
            } catch (Exception $e) {}
        }
    }

    /**
     * Before save should serialize permissions to save as text into the database.
     *
     * @param bool $forced
     */
    public function beforeSave( $forced = false )
    {
        if($this->permsAreSavedByAttr()) {
            $this->permsAreSavedByAttr(false);
        }
        else {
            $this->attributes['permissions'] = json_encode($this->perms()->lists('name', 'permission_id'));
        }
    }

    /**
     * Helper function to determine if the permissions are saved by
     * Directly setting $role->permissions array.
     * @todo maybe there is a better way of doing this
     *
     * @param null $newValue
     * @return bool|null
     *
     */
    protected function permsAreSavedByAttr($newValue = null) {
        static $isSavedByAttr;
        if($newValue !== null) {
            $isSavedByAttr = $newValue;
        }
        else {
            return $isSavedByAttr;
        }
    }

    /**
     * When loading the object it should un-serialize permissions to be
     * usable again
     *
     * @param string $value
     * permissoins json
     */
    public function getPermissionsAttribute($value)
    {
        return (array)json_decode($value);
    }

    /**
     * Before delete all constrained foreign relations
     *
     * @param bool $forced
     * @return bool
     */
    public function beforeDelete( $forced = false )
    {
        try {
            \DB::table('assigned_roles')->where('role_id', $this->id)->delete();
            \DB::table('permission_role')->where('role_id', $this->id)->delete();
        } catch(Execption $e) {}

        return true;
    }


    /**
     * Save permissions inputted
     * @param $inputPermissions
     */
    public function savePermissions($inputPermissions)
    {
        if(! empty($inputPermissions)) {
            $this->perms()->sync($inputPermissions);
        } else {
            $this->perms()->detach();
        }
    }

    /**
     * Attach permission to current role
     * @param $permission
     */
    public function attachPermission( $permission )
    {
        if( is_object($permission))
            $permission = $permission->getKey();

        if( is_array($permission))
            $permission = $permission['id'];

        $this->perms()->attach( $permission );
    }

    /**
     * Detach permission form current role
     * @param $permission
     */
    public function detachPermission( $permission )
    {
        if( is_object($permission))
            $permission = $permission->getKey();

        if( is_array($permission))
            $permission = $permission['id'];

        $this->perms()->detach( $permission );
    }

}
