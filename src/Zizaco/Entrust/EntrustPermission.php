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

    /**
     * Many-to-Many relations with Roles
     */
    public function roles()
    {
        return $this->belongsToMany('Role', 'permission_role');
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
            \DB::table('permission_role')->where('permission_id', $this->id)->delete();
        } catch(Execption $e) {}

        return true;
    }

}
