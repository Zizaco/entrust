<?php namespace Zizaco\Entrust;

use LaravelBook\Ardent\Ardent;

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
     * An array of permission ids
     * Used internally only
     *
     * @var array
     */
    protected $_permissions = [];

    /**
     * Show when permissions should be saved in the database
     *
     * @var boolean
     */
    private $_permissionsChanged = false;

    /**
     * The "booting" method of the model.
     * Overrided to perform save/update of the role's permissions.
     *
     * @see \LaravelBook\Ardent\Ardent::boot()
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::saved(function($role){
            if ($this->_permissionsChanged) {
                $role->perms()->sync($role->_permissions);
            }
        });
        static::updating(function($role){
            if ($this->_permissionsChanged) {
                $role->perms()->sync($role->_permissions);
            }
        });
    }

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
     * Set permissions by name
     * Finds permission ids to sync them after saving/updating
     *
     * @param array $value
     */
    public function setPermissionsAttribute($value) {
        if (!is_array($value)) {
            $value = [ $value ];
        }
        $this->_permissions = \DB::table('permissions')->select(['id'])->whereIn('name', $value)->lists('id');
        $this->_permissionsChanged = true;
    }

    /**
     * Returns an array of permission names assigned to the role
     *
     * @param string $value
     * @return array
     */
    public function getPermissionsAttribute($value) {
        return $this->perms()->lists('name');
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
