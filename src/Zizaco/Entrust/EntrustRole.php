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
     * beforeSave
     *
     * @return bool
     */
    public function beforeSave( $forced = false )
    {
        $this->permissions = json_encode($this->permissions);

        return true;
    }

    /**
     * afterSave
     *
     * @return bool
     */
    public function afterSave( $success,  $forced = false )
    {
        $this->permissions = json_decode($this->permissions);

        return true;
    }

    /**
     * Set the array of model attributes. No checking is done.
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
