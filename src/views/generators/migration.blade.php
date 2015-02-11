<?php
use Illuminate\Support\Facades\Config;

$user_table = Config::get('auth.table');
$user_model = Config::get('auth.model');
$user_id = (new $user_model())->getKeyName();

echo "<?php\n";

?>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class EntrustSetupTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Creates the roles table
        Schema::create('{{ $roles_table }}', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Creates the assigned_roles (Many-to-Many relation) table
        Schema::create('{{ $user_role_table }}', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->foreign('user_id')->references('{{ $user_id }}')->on('{{ $user_table }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('{{ $roles_table }}');
        });

        // Creates the permissions table
        Schema::create('{{ $permissions_table }}', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->timestamps();
        });

        // Creates the permission_role (Many-to-Many relation) table
        Schema::create('{{ $permission_role_table }}', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->foreign('permission_id')->references('id')->on('{{ $permissions_table }}'); // assumes a users table
            $table->foreign('role_id')->references('id')->on('{{ $roles_table }}');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('{{ $user_role_table }}', function (Blueprint $table) {
            $table->dropForeign('{{ $user_role_table }}_user_id_foreign');
            $table->dropForeign('{{ $user_role_table }}_role_id_foreign');
        });

        Schema::table('{{ $permission_role_table }}', function (Blueprint $table) {
            $table->dropForeign('{{ $permission_role_table }}_permission_id_foreign');
            $table->dropForeign('{{ $permission_role_table }}_role_id_foreign');
        });

        Schema::drop('{{ $user_role_table }}');
        Schema::drop('{{ $permission_role_table }}');
        Schema::drop('{{ $roles_table }}');
        Schema::drop('{{ $permissions_table }}');
    }

}
