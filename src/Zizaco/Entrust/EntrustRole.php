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
     * Before save should serialize permissions to save
     * as text into the database
     *
     * @param bool $forced
     * @return bool
     */
    public function beforeSave( $forced = false )
    {
        // TODO remove in a future version
        // @deprecated
        try {
            if(isset($this->permissions)) {
                $this->permissions = json_encode($this->permissions);
            }
        } catch(Execption $e) {}

        return true;
    }

    /**
     * After save should un-serialize permissions to be
     * usable again
     *
     * @param bool $success
     * @param bool $forced
     * @return bool
     */
    public function afterSave( $success,  $forced = false )
    {
        // TODO remove in a future version
        // @deprecated
        try {
            $this->permissions = json_decode($this->permissions);
        } catch(Execption $e) {}

        return true;
    }

    /**
     * When an serialized permission comes from the database
     * it may become an array within the object.
     *
     * @param  array  $attributes
     * @param  bool   $sync
     * @return void
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        if( isset($attributes['permissions']) )
        {
            $attributes['permissions'] = json_decode($attributes['permissions'], true);
        }

        parent::setRawAttributes( $attributes, $sync );
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
