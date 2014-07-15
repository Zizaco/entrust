# Entrust (Laravel4 Package)

![Entrust Poster](https://dl.dropbox.com/u/12506137/libs_bundles/entrust.png)

[![Build Status](https://api.travis-ci.org/Zizaco/entrust.png)](https://travis-ci.org/Zizaco/entrust)
[![ProjectStatus](http://stillmaintained.com/Zizaco/entrust.png)](http://stillmaintained.com/Zizaco/entrust)

Entrust provides a flexible way to add Role-based Permissions to **Laravel4**.

## Quick start

**PS:** Even though it's not needed. Entrust works very well with [Confide](https://github.com/Zizaco/confide) in order to eliminate repetitive tasks involving the management of users: Account creation, login, logout, confirmation by e-mail, password reset, etc.

[Take a look at Confide](https://github.com/Zizaco/confide)

### Required setup

In the `require` key of `composer.json` file add the following

    "zizaco/entrust": "dev-master"

Run the Composer update comand

    $ composer update

In your `config/app.php` add `'Zizaco\Entrust\EntrustServiceProvider'` to the end of the `$providers` array

```php
'providers' => array(

    'Illuminate\Foundation\Providers\ArtisanServiceProvider',
    'Illuminate\Auth\AuthServiceProvider',
    ...
    'Zizaco\Entrust\EntrustServiceProvider',

),
```

At the end of `config/app.php` add `'Entrust'    => 'Zizaco\Entrust\EntrustFacade'` to the `$aliases` array

```php
'aliases' => array(

    'App'        => 'Illuminate\Support\Facades\App',
    'Artisan'    => 'Illuminate\Support\Facades\Artisan',
    ...
    'Entrust'    => 'Zizaco\Entrust\EntrustFacade',

),
```
    
### Configuration

Set the property values in the `config/auth.php`. These values will be used by entrust to refer to the correct user table and model.

### User relation to roles

Now generate the Entrust migration

    $ php artisan entrust:migration

It will generate the `<timestamp>_entrust_setup_tables.php` migration. You may now run it with the artisan migrate command:

    $ php artisan migrate
    
After the migration, two new tables will be present: `roles` which contain the existent roles and it's permissions and `assigned_roles` which will represent the [Many-to-Many](http://four.laravel.com/docs/eloquent#many-to-many) relation between `User` and `Role`.

### Models

Create a Role model following the example at `app/models/Role.php`:

```php
<?php

use Zizaco\Entrust\EntrustRole;

class Role extends EntrustRole
{

}
```
    
The `Role` model has two main attributes: `name` and `permissions`.
`name`, as you can imagine, is the name of the Role. For example: "Admin", "Owner", "Employee".
The `permissions` field has been deprecated in preference for the permission table. You should no longer use it.
It is an array that is automatically serialized and unserialized when the Model is saved. This array should contain the name of the permissions of the `Role`. For example: `array( "manage_posts", "manage_users", "manage_products" )`.


Create a Permission model following the example at `app/models/Permission.php`:

```php
<?php

use Zizaco\Entrust\EntrustPermission;

class Permission extends EntrustPermission
{

}
```

The `Permission` model has two attributes: `name` and `display_name`.
`name`, as you can imagine, is the name of the Permission. For example: "Admin", "Owner", "Employee", "can_manage".
`display_name` is a viewer friendly version of the permission string. "Admin", "Can Manage", "Something Cool".

Next, use the `HasRole` trait in your existing `User` model. For example:

```php
<?php

use Zizaco\Entrust\HasRole;

class User extends Eloquent /* or ConfideUser 'wink' */{ 
    use HasRole; // Add this trait to your user model
    
...
```
    
This will do the trick to enable the relation with `Role` and the following methods `roles`, `hasRole( $name )`,
`can( $permission )`, and `ability($roles, $permissions, $options)` within your `User` model.

Don't forget to dump composer autoload

    $ composer dump-autoload

**And you are ready to go.**

## Usage

### Concepts
Let's start by creating the following `Role`s and `Permission`s:

```php
$owner = new Role;
$owner->name = 'Owner';
$owner->save();

$admin = new Role;
$admin->name = 'Admin';
$admin->save();

```
    
Next, with both roles created let's assign then to the users. Thanks to the `HasRole` trait this is as easy as:

```php
$user = User::where('username','=','Zizaco')->first();

/* role attach alias */
$user->attachRole( $admin ); // Parameter can be an Role object, array or id.

/* OR the eloquent's original: */
$user->roles()->attach( $admin->id ); // id only
```
Now we just need to add permissions to those Roles.

```php
$managePosts = new Permission;
$managePosts->name = 'manage_posts';
$managePosts->display_name = 'Manage Posts';
$managePosts->save();

$manageUsers = new Permission;
$manageUsers->name = 'manage_users';
$manageUsers->display_name = 'Manage Users';
$manageUsers->save();

$owner->perms()->sync(array($managePosts->id,$manageUsers->id));
$admin->perms()->sync(array($managePosts->id));
```

Now we can check for roles and permissions simply by doing:

```php
$user->hasRole("Owner");    // false
$user->hasRole("Admin");    // true
$user->can("manage_posts"); // true
$user->can("manage_users"); // false
```

You can have as many `Role`s was you want in each `User` and vice versa.

More advanced checking can be done using the awesome `ability` function. It takes in three parameters (roles, permissions, options).
`roles` is a set of roles to check. `permissions` is a set of permissions to check.
Either of the roles or permissions variable can be a comma separated string or array.

```php
$user->ability(array('Admin','Owner'), array('manage_posts','manage_users'));
//or
$user->ability('Admin,Owner', 'manage_posts,manage_users');

```
This will check whether the user has any of the provided roles and permissions. In this case it will return true since the user
is an Admin and has the manage_posts permission.

The third parameter is an options array.

```php
$options = array(
'validate_all' => true | false (Default: false),
'return_type' => boolean | array | both (Default: boolean)
);
```
`validate_all` is a boolean flag to set whether to check all the values for true, or to return true if at least one role or permission is matched.

`return_type` specifies whether to return a boolean, array of checked values, or both in an array.

Here's some example output.

```php
$options = array(
'validate_all' => true,
'return_type' => 'both'
);
list($validate,$allValidations) = $user->ability(array('Admin','Owner'), array('manage_posts','manage_users'), $options);

// Output
var_dump($validate);
bool(false)
var_dump($allValidations);
array(4) {
  ['role']=>
  bool(true)
  ['role_2']=>
  bool(false)
  ['manage_posts']=>
  bool(true)
  ['manage_users']=>
  bool(false)
}
```

### Short syntax Route filter

To filter a route by permission or role you can call the following in your `app/filters.php`:

```php
// Only users with roles that have the 'manage_posts' permission will
// be able to access any route within admin/post.
Entrust::routeNeedsPermission( 'admin/post*', 'manage_posts' );

// Only owners will have access to routes within admin/advanced
Entrust::routeNeedsRole( 'admin/advanced*', 'Owner' );

// Optionally the second parameter can be an array of permissions or roles.
// User would need to match all roles or permissions for that route.
Entrust::routeNeedsPermission( 'admin/post*', array('manage_posts','manage_comments') );

Entrust::routeNeedsRole( 'admin/advanced*', array('Owner','Writer') );
```

Both of these methods accepts a third parameter. If the third parameter is null then the return of a prohibited access will be `App::abort(403)`. Otherwise the third parameter will be returned. So you can use it like:

```php
Entrust::routeNeedsRole( 'admin/advanced*', 'Owner', Redirect::to('/home') );
```

Further more both of these methods accept a fourth parameter. It defaults to true and checks all roles/permissions given.
If you set it to false, the function will only fail if all roles/permissions fail for that user. Useful for admin applications where
you want to allow access for multiple groups.

```php
// If a user has `manage_posts`, `manage_comments` or both they will have access.
Entrust::routeNeedsPermission( 'admin/post*', array('manage_posts','manage_comments'), null, false );

// If a user is a member of `Owner`, `Writer` or both they will have access.
Entrust::routeNeedsRole( 'admin/advanced*', array('Owner','Writer'), null, false );

// If a user is a member of `Owner`, `Writer` or both, or user has `manage_posts`, `manage_comments` they will have access.
// You can set the 4th parameter to true then user must be member of Role and must has Permission.
Entrust::routeNeedsRoleOrPermission( 'admin/advanced*', array('Owner','Writer'), array('manage_posts','manage_comments'), null, false);
```

### Route filter

Entrust roles/permissions can be used in filters by simply using the `can` and `hasRole` methods from within the Facade.

```php
Route::filter('manage_posts', function()
{
    if (! Entrust::can('manage_posts') ) // Checks the current user
    {
        return Redirect::to('admin');
    }
});

// Only users with roles that have the 'manage_posts' permission will
// be able to access any admin/post route.
Route::when('admin/post*', 'manage_posts'); 
```

Using a filter to check for a role:

```php
Route::filter('owner_role', function()
{
    if (! Entrust::hasRole('Owner') ) // Checks the current user
    {
        App::abort(404);
    }
});

// Only owners will have access to routes within admin/advanced
Route::when('admin/advanced*', 'owner_role'); 
```

As you can see `Entrust::hasRole()` and `Entrust::can()` checks if the user is logged, and then if he has the role or permission. If the user is not logged the return will also be `false`.

## Troubleshooting

If you encounter an error when doing the migration that looks like:
```
SQLSTATE[HY000]: General error: 1005 Can't create table 'laravelbootstrapstarter.#sql-42c_f8' (errno: 150) (SQL: alter table `assigned_roles` add constraint assigned_roles_user_id_foreign foreign key (`
  user_id`) references `users` (`id`)) (Bindings: array (
  ))
```
Then it's likely that the `id` column in your user table does not match the `user_id` column in `assigned_roles`. Match sure both are `INT(10)`.

Name is having issues saving.

EntrustRole->name has a length limitation set within the rules variable of the [EntrustRole class](https://github.com/Zizaco/entrust/blob/master/src/Zizaco/Entrust/EntrustRole.php#L21).

You can adjust it by changing your Role Model.

```php
<?php

use Zizaco\Entrust\EntrustRole;

class Role extends EntrustRole
{
    /**
     * Ardent validation rules
     *
     * @var array
     */
    public static $rules = array(
      'name' => 'required|between:4,255'
    );
}
```

## License

Entrust is free software distributed under the terms of the MIT license

## Additional information

Any questions, feel free to contact me or ask [here](http://forums.laravel.io/viewtopic.php?id=9313)

Any issues, please [report here](https://github.com/Zizaco/entrust/issues)
