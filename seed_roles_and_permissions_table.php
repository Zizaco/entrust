<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Role;
use App\Permission;

class SeedRolesAndPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $superadmin = new Role();
		$superadmin->name         = 'superadmin';
		$superadmin->display_name = 'User Super Administrator'; // optional
		$superadmin->description  = 'User is allowed to manage app.'; // optional
		$superadmin->save();
		
		$admin = new Role();
		$admin->name         = 'admin';
		$admin->display_name = 'User Administrator'; // optional
		$admin->description  = 'User is allowed to manage and edit other users'; // optional
		$admin->save();
		
		$member = new Role();
		$member->name         = 'member';
		$member->display_name = 'Members'; // optional
		$member->description  = 'Members of App.'; // optional
		$member->save();
		
		$createUser = new Permission();
		$createUser->name         = 'create-user';
		$createUser->display_name = 'Create Users'; // optional
		// Allow a user to...
		$createUser->description  = 'create new users'; // optional
		$createUser->save();
	
		$editUser = new Permission();
		$editUser->name         = 'edit-user';
		$editUser->display_name = 'Edit Users'; // optional
		// Allow a user to...
		$editUser->description  = 'edit existing users'; // optional
		$editUser->save();
		
		$listUser = new Permission();
		$listUser->name         = 'list-user';
		$listUser->display_name = 'List Users'; // optional
		// Allow a user to...
		$listUser->description  = 'List users'; // optional
		$listUser->save();
		
		$manageOptions = new Permission();
		$manageOptions->name         = 'manage-options';
		$manageOptions->display_name = 'Manage Options'; // optional
		// Allow a user to...
		$manageOptions->description  = 'Manage Options'; // optional
		$manageOptions->save();
		
		$uploadFiles = new Permission();
		$uploadFiles->name         = 'upload-files';
		$uploadFiles->display_name = 'Upload Files Own Folder'; // optional
		// Allow a user to...
		$uploadFiles->description  = 'Able to upload Files to own folder'; // optional
		$uploadFiles->save();
		
		$downloadFiles = new Permission();
		$downloadFiles->name         = 'download-files';
		$downloadFiles->display_name = 'Download Own Files'; // optional
		// Allow a user to...
		$downloadFiles->description  = 'Able to download Own Files'; // optional
		$downloadFiles->save();
		
		$superadmin->attachPermissions(array($listUser));
		
	
		$admin->attachPermissions(array($createUser, $editUser, $listUser, $manageOptions, $uploadFiles));
		// equivalent to $admin->perms()->sync(array($createUser->id));
	
		$member->attachPermissions(array($uploadFiles, $downloadFiles));
		
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
