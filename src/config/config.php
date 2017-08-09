<?php

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Adesr\Entrust
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Entrust Role Model
    |--------------------------------------------------------------------------
    |
    | This is the Role model used by Entrust to create correct relations.  Update
    | the role if it is in a different namespace.
    |
    */
    'role' => 'App\Role',

    /*
    |--------------------------------------------------------------------------
    | Entrust Roles Table
    |--------------------------------------------------------------------------
    |
    | This is the roles table used by Entrust to save roles to the database.
    |
    */
    'roles_table' => 'roles',

    /*
    |--------------------------------------------------------------------------
    | Entrust role foreign key
    |--------------------------------------------------------------------------
    |
    | This is the role foreign key used by Entrust to make a proper
    | relation between permissions and roles & roles and users
    |
    */
    'role_foreign_key' => 'role_id',

    /*
    |--------------------------------------------------------------------------
    | Application User Model
    |--------------------------------------------------------------------------
    |
    | This is the User model used by Entrust to create correct relations.
    | Update the User if it is in a different namespace.
    |
    */
    'user' => 'App\User',

    /*
    |--------------------------------------------------------------------------
    | Application Users Table
    |--------------------------------------------------------------------------
    |
    | This is the users table used by the application to save users to the
    | database.
    |
    */
    'users_table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Entrust role_user Table
    |--------------------------------------------------------------------------
    |
    | This is the role_user table used by Entrust to save assigned roles to the
    | database.
    |
    */
    'role_user_table' => 'role_user',

    /*
    |--------------------------------------------------------------------------
    | Entrust user foreign key
    |--------------------------------------------------------------------------
    |
    | This is the user foreign key used by Entrust to make a proper
    | relation between roles and users
    |
    */
    'user_foreign_key' => 'user_id',

    /*
    |--------------------------------------------------------------------------
    | Entrust Permission Model
    |--------------------------------------------------------------------------
    |
    | This is the Permission model used by Entrust to create correct relations.
    | Update the permission if it is in a different namespace.
    |
    */
    'permission' => 'App\Permission',

    /*
    |--------------------------------------------------------------------------
    | Entrust Permissions Table
    |--------------------------------------------------------------------------
    |
    | This is the permissions table used by Entrust to save permissions to the
    | database.
    |
    */
    'permissions_table' => 'permissions',

    /*
    |--------------------------------------------------------------------------
    | Entrust permission_role Table
    |--------------------------------------------------------------------------
    |
    | This is the permission_role table used by Entrust to save relationship
    | between permissions and roles to the database.
    |
    */
    'permission_role_table' => 'permission_role',

    /*
    |--------------------------------------------------------------------------
    | Entrust permission foreign key
    |--------------------------------------------------------------------------
    |
    | This is the permission foreign key used by Entrust to make a proper
    | relation between permissions and roles
    |
    */
    'permission_foreign_key' => 'permission_id',

    /*
    |--------------------------------------------------------------------------
    | Entrust Menu Model
    |--------------------------------------------------------------------------
    |
    | This is the Menu model used by Entrust to create correct relations.
    | Update the menu if it is in a different namespace.
    |
    */
    'menu' => 'App\Menu',

    /*
    |--------------------------------------------------------------------------
    | Entrust Menu Table
    |--------------------------------------------------------------------------
    |
    | This is the menu table used by Entrust to save menu to the
    | database.
    |
    */
    'menu_table' => 'menus',

    /*
    |--------------------------------------------------------------------------
    | Entrust menu_role Table
    |--------------------------------------------------------------------------
    |
    | This is the menu_role table used by Entrust to save relationship
    | between menus and roles to the database.
    |
    */
    'menu_role_table' => 'menu_role',

    /*
    |--------------------------------------------------------------------------
    | Entrust menu_permission Table
    |--------------------------------------------------------------------------
    |
    | This is the menu_permission table used by Entrust to save relationship
    | between menus and permissions to the database.
    |
    */
    'menu_permission_table' => 'menu_permission',

    /*
    |--------------------------------------------------------------------------
    | Entrust menu foreign key
    |--------------------------------------------------------------------------
    |
    | This is the menu foreign key used by Entrust to make a proper
    | relation between menu and roles / permissions
    |
    */
    'menu_foreign_key' => 'menu_id',

    /*
    |--------------------------------------------------------------------------
    | Entrust menu foreign key (parent-child structure)
    |--------------------------------------------------------------------------
    |
    | This is the menu foreign key used by Entrust to make a proper
    | relation between menu and its decendants
    |
    */
    'menu_child_key' => 'slug',

    /*
    |--------------------------------------------------------------------------
    | Entrust menu foreign key (parent-child structure)
    |--------------------------------------------------------------------------
    |
    | This is the menu foreign key used by Entrust to make a proper
    | relation between menu and its ancestor
    |
    */
    'menu_parent_key' => 'parent',
];
