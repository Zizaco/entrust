<?php namespace Zizaco\Entrust;

use Execption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use LaravelBook\Ardent\Ardent;

class EntrustPermission extends Ardent
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Ardent validation rules.
     *
     * @var array
     */
    public static $rules = array(
        'name' => 'required|between:4,128',
        'display_name' => 'required|between:4,128'
    );

    /**
     * Creates a new instance of the model.
     *
     * @return void
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->table = Config::get('entrust::permissions_table');
    }

    /**
     * Many-to-Many relations with Roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Config::get('entrust::role'), Config::get('entrust::permission_role_table'));
    }

    /**
     * Before delete all constrained foreign relations.
     *
     * @param bool $forced
     *
     * @return bool
     */
    public function beforeDelete($forced = false)
    {
        try {
            DB::table(Config::get('entrust::permission_role_table'))->where('permission_id', $this->id)->delete();
        } catch (Exception $e) {
            // do nothing
        }

        return true;
    }
}
