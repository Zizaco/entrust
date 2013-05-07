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

Set the propertly values to the `config/auth.php`. These values will be used by entrust to refer to the correct user table and model.

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
`permissions` is an array that is automagically serialized and unserialized and the Model is saved. This array should contain the name of the permissions of the `Role`. For example: `array( "manage_posts", "manage_users", "manage_products" )`.

Next, use the `HasRole` trait in your existing `User` model. For example:

```php
<?php

use Zizaco\Entrust\HasRole;

class User extends Eloquent /* or ConfideUser 'wink' */{ 
    use HasRole; // Add this trait to your user model
    
...
```
    
This will do the trick to enable the relation with `Role` and the following methods `roles`, `hasRole( $name )` and `can( $permission )` within your `User` model.

Don't forget to dump composer autoload

    $ composer dump-autoload

**And you are ready to go.**

## Usage

### Concepts
Let's start by creating the following `Role`s:

```php
$owner = new Role;
$owner->name = 'Owner';
$owner->permissions = array('manage_posts','manage_pages','manage_users');
$owner->save();

$admin = new Role;
$admin->name = 'Admin';
$admin->permissions = array('manage_posts','manage_pages');
$admin->save();
```
    
Next, with both roles created let's assign then to the users. Thanks to the `HasRole` trait this are gonna be easy as:

```php
$user = User::where('username','=','Zizaco')->first();

/* role attach alias */
$user->attachRole( $admin ); // Parameter can be an Role object, array or id.

/* OR the eloquent's original: */
$user->roles()->attach( $admin->id ); // id only
```
    
Now we can check for roles and permissions simply by doing:

```php
$user->hasRole("Owner");    // true
$user->hasRole("Admin");    // false
$user->can("manage_posts"); // true
$user->can("manage_users"); // false
```

You can have as many `Role`s was you want in each `User` and vice versa.

### Short syntax Route filter

To filter a route by permission or role you can call the following in your `app/filers.php`:

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

Both of these methods accepts a third parameter. If the third parameter is null then the return of a prohibited access will be `App::abort(404)`. Otherwise the third parameter will be returned. So you can use it like:

```php
Entrust::routeNeedsRole( 'admin/advanced*', 'Owner', Redirect::to('/home') );
```

Further both of these methods accept a fourth parameter. It defaults to true and checks all roles/permissions given.
If you set it to false, the function will only fail if all roles/permissions fail for that user. Useful for admin applications where
you want to allow access for multiple groups.

```php
// If a user has `manage_posts`, `manage_comments` or both they will have access.
Entrust::routeNeedsRole( 'admin/post*', array('manage_posts','manage_comments'), null, false );

// If a user is a member of `Owner`, `Writer` or both they will have access.
Entrust::routeNeedsPermission( 'admin/advanced*', array('Owner','Writer'), null, false );
```

### Route filter

Entrive roles/permissions can be used in filters by simply using the `can` and `hasRole` methods from within the Facade.

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

## License

Entrust is free software distributed under the terms of the MIT license

## Aditional information

Any questions, feel free to contact me or ask [here](http://forums.laravel.io/viewtopic.php?id=4658)

Any issues, please [report here](https://github.com/Zizaco/entrust/issues)
