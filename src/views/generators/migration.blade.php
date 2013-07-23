{{ '<?php' }}

use Illuminate\Database\Migrations\Migration;

class EntrustSetupTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Creates the roles table
        Schema::create('roles', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name')->unique();
            $table->string('permissions');
            $table->timestamps();
        });

        // Creates the assigned_roles (Many-to-Many relation) table
        Schema::create('assigned_roles', function($table)
        {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users'); // assumes a users table
            $table->foreign('role_id')->references('id')->on('roles');
        });

        // Creates the permissions table
        Schema::create('permissions', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('display_name');
            $table->timestamps();
        });

        // Creates the permission_role (Many-to-Many relation) table
        Schema::create('permission_role', function($table)
        {
            $table->increments('id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->foreign('permission_id')->references('id')->on('permissions'); // assumes a users table
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('assigned_roles');
        Schema::drop('permission_role');
        Schema::drop('roles');
        Schema::drop('permissions');
    }

}
