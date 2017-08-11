<?php echo '<?php' ?>

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
        DB::beginTransaction();

        // Create table for storing roles
        Schema::create('{{ $rolesTable }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for associating roles to users (Many-to-Many)
        Schema::create('{{ $roleUserTable }}', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->foreign('user_id')->references('{{ $userKeyName }}')->on('{{ $usersTable }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('{{ $rolesTable }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['user_id', 'role_id']);
        });

        // Create table for storing permissions
        Schema::create('{{ $permissionsTable }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for associating permissions to roles (Many-to-Many)
        Schema::create('{{ $permissionRoleTable }}', function (Blueprint $table) {
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->foreign('permission_id')->references('id')->on('{{ $permissionsTable }}')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('{{ $rolesTable }}')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });

        // Create table for storing menus
        Schema::create('{{ $menusTable }}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('parent')->nullable();
            $table->string('icon')->nullable();
            $table->tinyInteger('is_active')->unsigned()->default(0);
            $table->tinyInteger('order_no')->unsigned()->default(0);
            $table->timestamps();
        });

        // Create table for associating menus to roles (Many-to-Many)
        Schema::create('{{ $menuRoleTable }}', function (Blueprint $table) {
            $table->integer('role_id')->unsigned();
            $table->integer('menu_id')->unsigned();

            $table->foreign('role_id')->references('id')->on('roles')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menus')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['menu_id', 'role_id']);
        });

        // Create table for associating menus to permissions (Many-to-Many)
        Schema::create('{{ $menuPermissionTable }}', function (Blueprint $table) {
            $table->integer('menu_id')->unsigned();
            $table->integer('permission_id')->unsigned();

            $table->foreign('menu_id')->references('id')->on('menus')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['menu_id', 'permission_id']);
        });

        DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('{{ $permissionRoleTable }}');
        Schema::drop('{{ $menuPermissionTable }}');
        Schema::drop('{{ $permissionsTable }}');
        Schema::drop('{{ $roleUserTable }}');
        Schema::drop('{{ $menuRoleTable }}');
        Schema::drop('{{ $rolesTable }}');
        Schema::drop('{{ $menusTable }}');
    }
}
