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
            $table->increments('id');
            $table->string('name');
            $table->text('permissions'); // Model's permissions array parsed as JSON
            $table->timestamps();
        });

        // Creates the assigned_roles (Many-to-Many relation) table
        Schema::create('assigned_roles', function($table)
        {
            $table->increments('id');
            $table->integer('user_id');
            $table->index('user_id');
            $table->integer('role_id');
            $table->index('role_id');
            $table->foreign('user_id')->references('id')->on('users'); // assumes a users table
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
        Schema::drop('roles');
    }

}
