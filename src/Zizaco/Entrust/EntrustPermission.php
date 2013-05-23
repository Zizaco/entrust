<?php namespace Zizaco\Entrust;

use LaravelBook\Ardent\Ardent;

class EntrustPermission extends Ardent
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * Ardent validation rules
     *
     * @var array
     */
    public static $rules = array(
      'name' => 'required|between:4,32',
      'display_name' => 'required|between:4,32'
    );

}
