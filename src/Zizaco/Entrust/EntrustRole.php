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
     * Before save should serialize permissions to save
     * as text into the database
     *
     * @return bool
     */
    public function beforeSave( $forced = false )
    {
        $this->permissions = json_encode($this->permissions);

        return true;
    }

    /**
     * After save should un-serialize permissions to be
     * usable again
     *
     * @return bool
     */
    public function afterSave( $success,  $forced = false )
    {
        $this->permissions = json_decode($this->permissions);

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
            $attributes['permissions'] = json_decode($attributes['permissions']);
        }

        parent::setRawAttributes( $attributes, $sync );
    }
}
