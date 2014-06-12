<?php namespace Zizaco\Entrust;

use LaravelBook\Ardent\Ardent;
use Config;

class EntrustRole extends Ardent
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Ardent validation rules
     *
     * @var array
     */
    public static $rules = array(
      'name' => 'required|between:4,128'
    );

    /**
     * Creates a new instance of the model
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->table = Config::get('entrust::roles_table');
    }

    /**
     * Many-to-Many relations with Users
     */
    public function users()
    {
        return $this->belongsToMany(Config::get('auth.model'), Config::get('entrust::assigned_roles_table'))
            ->withTimestamps()
            ->whereRaw('(assigned_roles.expires_at > NOW() OR assigned_roles.expires_at IS NULL)')
            ->whereNull('assigned_roles.deleted_at');
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
			return $this->belongsToMany(Config::get('entrust::permission'), Config::get('entrust::permission_role_table'));
        } catch(Execption $e) {}
    }

    /**
     * Before save should serialize permissions to save
     * as text into the database
     *
     * @param array $value
     */
    public function setPermissionsAttribute($value)
    {
        $this->attributes['permissions'] = json_encode($value);
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
            \DB::table(Config::get('entrust::assigned_roles_table'))->where('role_id', $this->id)->delete();
            \DB::table(Config::get('entrust::permission_role_table'))->where('role_id', $this->id)->delete();
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

    /**
     * Attach multiple permissions to current role
     *
     * @param $permissions
     * @access public
     * @return void
     */
    public function attachPermissions($permissions)
    {
        foreach ($permissions as $permission)
        {
            $this->attachPermission($permission);
        }
    }

    /**
     * Detach multiple permissions from current role
     *
     * @param $permissions
     * @access public
     * @return void
     */
    public function detachPermissions($permissions)
    {
        foreach ($permissions as $permission)
        {
            $this->detachPermission($permission);
        }
    }

}
