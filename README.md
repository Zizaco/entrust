# Entrust (Laravel4 Package)

Entrust provides a flexible way to add Role-based Permissions to **Laravel4**.

First and foremost I must give credit to the original developers of this package. Andrew Elkins (@andrewelkins) and Leroy Merlin (@zizaco) did excellent work with this package and the fundamental design and functionality. My fork is intended to:

- Remove extra components not really relevant to role & permission management (in particular, Ardent).
- Add extra functionality I felt was useful and particularly suited to this package.
- Make integrating the package more flexible and dynamic (eventually).

Were my changes ever to be integrated back into the Zizaco version of this plugin, I think that would be lovely. Either way though, I hope to demonstrate some genuinely helpful features and options.

## Quick start

**PS:** Even though it's not needed. Entrust works very well with [Confide](https://github.com/Zizaco/confide) in order to eliminate repetitive tasks involving the management of users: Account creation, login, logout, confirmation by e-mail, password reset, etc.

[Take a look at Confide](https://github.com/Zizaco/confide)

### Required setup

In the `require` key of `composer.json` file add the following

    "bbatsche/entrust": "~2.0"

Run the Composer update command

    $ composer update

In your `config/app.php` add `'Bbatsche\Entrust\EntrustServiceProvider'` to the end of the `$providers` array

```php
'providers' => array(

    'Illuminate\Foundation\Providers\ArtisanServiceProvider',
    'Illuminate\Auth\AuthServiceProvider',
    ...
    'Bbatsche\Entrust\EntrustServiceProvider',

),
```

At the end of `config/app.php` add `'Entrust'    => 'Bbatsche\Entrust\EntrustFacade'` to the `$aliases` array

```php
'aliases' => array(

    'App'        => 'Illuminate\Support\Facades\App',
    'Artisan'    => 'Illuminate\Support\Facades\Artisan',
    ...
    'Entrust'    => 'Bbatsche\Entrust\EntrustFacade',

),
```

## Configuration

Set the property values in the `config/auth.php`. These values will be used by entrust to refer to the correct user table and model.

### User relation to roles

Now generate the Entrust migration

    $ php artisan entrust:migration

It will generate the `<timestamp>_entrust_setup_tables.php` migration. You may now run it with the artisan migrate command:

    $ php artisan migrate

After the migration, two new tables will be present: `roles` which contain the existent roles and it's permissions and `role_user` which will represent the [Many-to-Many](http://laravel.com/docs/4.2/eloquent#many-to-many) relation between `User` and `Role`.

### Models

Create a Role model following the example at `app/models/Role.php`:

```php
<?php

use Bbatsche\Entrust\EntrustRole;

class Role extends EntrustRole
{

}
```

The `Role` model has three main attributes: `name`, `display_name`, and `description`. `name`, as you can imagine, is
the name of the Role. It is the unique key used to represent the Role in your application. For example: "admin",
"owner", "employee". `display_name` is a human readable name for that role. It is not unique and should only be used
for display purposes. For example: "User Administrator", "Project Owner", "Widget  Co. Employee". `description` can be
used for a longer form description of the role. Both `display_name` and `description` are optional; their fields are
nullable in the database.

Create a Permission model following the example at `app/models/Permission.php`:

```php
<?php

use Bbatsche\Entrust\EntrustPermission;

class Permission extends EntrustPermission
{

}
```

The `Permission` model has the same three attributes as the `Role`: `name`, `display_name`, and `description`. These
three fields have the same general purpose as for your Roles, but instead applied to the Permission model.
`name` is the unique name of the Permission. For example: "create-post", "edit-user", "post-payment",
"mailing-list-subscribe". `display_name` is a viewer friendly version of the permission string. For example "Create
Posts", "Edit Users", "Post Payments", "Subscribe to mailing list". Description can be a more detailed explanation for
the Permission. In general, it may be helpful to think of the attributes in the form of a sentence: "The permission
`display_name` allows a user to `description`."

Next, use the `HasRole` trait in your existing `User` model. For example:

```php
<?php

use Bbatsche\Entrust\HasRole;

class User extends Eloquent
{
    use HasRole; // Add this trait to your user model

...
```

This will enable the relation with `Role` and add the following methods `roles()`, `hasRole($name)`,
`can($permission)`, and `ability($roles, $permissions, $options)` within your `User` model.

Don't forget to dump composer autoload

    $ composer dump-autoload

**And you are ready to go.**

## Usage

### Concepts
Let's start by creating the following `Role`s and `Permission`s:

```php
$owner = new Role();
$owner->name         = 'owner';
$owner->display_name = 'Project Owner'; // optional
$owner->description  = 'User is the owner of a given project'; // optional
$owner->save();

$admin = new Role();
$admin->name         = 'admin';
$admin->display_name = 'User Administrator'; // optional
$admin->description  = 'User is allowed to manage and edit other users'; //optional
$admin->save();
```

Next, with both roles created let's assign them to the users. Thanks to the `HasRole` trait this is as easy as:

```php
$user = User::where('username', '=', 'bbatsche')->first();

/* role attach alias */
$user->attachRole($admin); // Parameter can be an Role object, array or id.

/* OR the eloquent's original: */
$user->roles()->attach($admin->id); // id only
```
Now we just need to add permissions to those Roles.

```php
$createPost = new Permission();
$createPost->name         = 'create-post';
$createPost->display_name = 'Create Posts'; // optional
// Allow a user to...
$createPost->description  = 'create new blog posts'; // optional
$createPost->save();

$editUser = new Permission();
$editUser->name         = 'edit-user';
$editUser->display_name = 'Edit Users'; // optional
// Allow a user to...
$editUser->description  = 'edit existing users'; // optional
$editUser->save();

$owner->perms()->sync(array($createPost->id, $editUser->id));
$admin->perms()->sync(array($createPost->id));
```

Now we can check for roles and permissions simply by doing:

```php
$user->hasRole('owner');   // false
$user->hasRole('admin');   // true
$user->can('edit-user');   // false
$user->can('create-post'); // true
```

You can have as many `Role`s as you want for each `User` and vice versa.

More advanced checking can be done using the awesome `ability` function. It takes in three parameters (roles,
permissions, options). `roles` is a set of roles to check. `permissions` is a set of permissions to check.
Either of the roles or permissions variable can be a comma separated string or array.

```php
$user->ability(array('admin', 'owner'), array('create-post', 'edit-user'));
//or
$user->ability('admin, owner', 'create-post, edit-user');

```

This will check whether the user has any of the provided roles and permissions. In this case it will return true
since the user is an `admin` and has the `create-post` permission.

The third parameter is an options array.

```php
$options = array(
    'validate_all' => true | false (Default: false),
    'return_type'  => boolean | array | both (Default: boolean)
);
```

- `validate_all` is a boolean flag to set whether to check all the values for true, or to return true if at least one
  role or permission is matched.
- `return_type` specifies whether to return a boolean, array of checked values, or both in an array.

Here's some example output.

```php
$options = array(
    'validate_all' => true,
    'return_type' => 'both'
);
list($validate, $allValidations) = $user->ability(array('admin', 'owner'), array('create-post', 'edit-user'), $options);

// Output
var_dump($validate);
bool(false)
var_dump($allValidations);
array(4) {
  ['role']=>
  bool(true)
  ['role_2']=>
  bool(false)
  ['create-post']=>
  bool(true)
  ['edit-user']=>
  bool(false)
}
```

### Short Syntax Route Filter

To filter a route by permission or role you can call the following in your `app/filters.php`:

```php
// Only users with roles that have the 'manage_posts' permission will
// be able to access any route within admin/post.
Entrust::routeNeedsPermission('admin/post*', 'create-post');

// Only owners will have access to routes within admin/advanced
Entrust::routeNeedsRole('admin/advanced*', 'owner');

// Optionally the second parameter can be an array of permissions or roles.
// User would need to match all roles or permissions for that route.
Entrust::routeNeedsPermission('admin/post*', array('create-post', 'edit-comment'));

Entrust::routeNeedsRole('admin/advanced*', array('owner','writer'));
```

Both of these methods accept a third parameter. If the third parameter is null then the return of a prohibited access will be `App::abort(403)`. Otherwise the third parameter will be returned. So you can use it like:

```php
Entrust::routeNeedsRole('admin/advanced*', 'owner', Redirect::to('/home'));
```

Further more both of these methods accept a fourth parameter. It defaults to true and checks all roles/permissions
given. If you set it to false, the function will only fail if all roles/permissions fail for that user. Useful for
admin applications where you want to allow access for multiple groups.

```php
// If a user has `create-post`, `edit-comment` or both they will have access.
Entrust::routeNeedsPermission('admin/post*', array('create-post', 'edit-comment'), null, false);

// If a user is a member of `owner`, `writer` or both they will have access.
Entrust::routeNeedsRole('admin/advanced*', array('owner','writer'), null, false);

// If a user is a member of `owner`, `writer` or both, or user has `create-post`, `edit-comment` they will have access.
// You can set the 4th parameter to true then user must be member of Role and must has Permission.
Entrust::routeNeedsRoleOrPermission(
    'admin/advanced*',
    array('owner', 'writer'),
    array('create-post', 'edit-comment'),
    null,
    false
);
```

### Route filter

Entrust roles/permissions can be used in filters by simply using the `can` and `hasRole` methods from within the Facade.

```php
Route::filter('manage_posts', function()
{
    // Checks the current user
    if (!Entrust::can('create-post')) {
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
    // Checks the current user
    if (!Entrust::hasRole('Owner')) {
        App::abort(404);
    }
});

// Only owners will have access to routes within admin/advanced
Route::when('admin/advanced*', 'owner_role');
```

As you can see `Entrust::hasRole()` and `Entrust::can()` checks if the user is logged in, and then if he or she has the
role or permission. If the user is not logged the return will also be `false`.

## Troubleshooting

If you encounter an error when doing the migration that looks like:

```
SQLSTATE[HY000]: General error: 1005 Can't create table 'laravelbootstrapstarter.#sql-42c_f8' (errno: 150)
    (SQL: alter table `role_user` add constraint role_user_user_id_foreign foreign key (`user_id`)
    references `users` (`id`)) (Bindings: array ())
```

Then it's likely that the `id` column in your user table does not match the `user_id` column in `role_user`. Match sure
both are `INT(10)`.

## License

Entrust is free software distributed under the terms of the MIT license

## Additional information

Any questions, feel free to contact me or ask [here](http://laravel.io/forum/09-23-2014-package-zizaco-entrust)

Any issues, please [report here](https://github.com/bbatsche/entrust/issues)
