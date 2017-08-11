<?php namespace Adesr\Entrust\Traits;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Adesr\Entrust
 */

use Illuminate\Support\Facades\Config;

trait EntrustMenuTrait
{
    /**
     * Many-to-Many relations with role model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Config::get('entrust.role'), Config::get('entrust.menu_role_table'), Config::get('entrust.menu_foreign_key'), Config::get('entrust.role_foreign_key'));
    }

    /**
     * Many-to-Many relations with permission model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function perms()
    {
        return $this->belongsToMany(Config::get('entrust.permission'), Config::get('entrust.menu_permission_table'), Config::get('entrust.menu_foreign_key'), Config::get('entrust.permission_foreign_key'));
    }

    /**
     * One-to-Many relations with itself.
     * Get direct decendant (child)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Config::get('entrust.menu'), Config::get('entrust.menu_parent_key'), Config::get('entrust.menu_child_key'));
    }

    /**
     * One-to-Many relations with itself.
     * Get direct ancestor (parent)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Config::get('entrust.menu'), Config::get('entrust.menu_parent_key'), Config::get('entrust.menu_child_key'));
    }

    /**
     * Boot the permission model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the permission model uses soft deletes.
     *
     * @return void|bool
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function($menu) {
            if (!method_exists(Config::get('entrust.menu'), 'bootSoftDeletes')) {
                $menu->roles()->sync([]);
                $menu->perms()->sync([]);
            }

            return true;
        });
    }

    /**
     * Attach permission to current menu.
     *
     * @param object|array $permission
     *
     * @return void
     */
    public function attachPermission($permission)
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            return $this->attachPermissions($permission);
        }

        $this->perms()->attach($permission);
    }

    /**
     * Detach permission from current menu.
     *
     * @param object|array $permission
     *
     * @return void
     */
    public function detachPermission($permission)
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            return $this->detachPermissions($permission);
        }

        $this->perms()->detach($permission);
    }

    /**
     * Attach multiple permissions to current menu.
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function attachPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            $this->attachPermission($permission);
        }
    }

    /**
     * Detach multiple permissions from current menu
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function detachPermissions($permissions = null)
    {
        if (!$permissions) $permissions = $this->perms()->get();

        foreach ($permissions as $permission) {
            $this->detachPermission($permission);
        }
    }

    /**
     * Sync multiple permissions to current menu.
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function syncPermissions($permissions)
    {
        $perms = [];
        if(is_object($permissions)) {
            foreach ($permissions as $v) {
                $perms[] = $v->getKey();
            }
        }
        if(is_array($permissions)) {
            $perms = $permissions;
        }

        $this->perms()->sync($perms);
    }

    /**
     * Get all ancestors (parents) of given menu in one dimentional array
     *
     * @param array $menu           Array of $menu
     *
     * @return array
     */
    public function getAncestors($menu)
    {
        $ancestors = [];
        foreach ($menu as $val) {
            $ancestors[] = $val->id;
            $parent = $val->parent()->get();
            if($parent->isNotEmpty()) {
                $ancestors[] = $this->getAncestors($parent);
            }
        }
        // return collect($ancestors)->flatten()->unique()->values()->all();
        $return = [];
        array_walk_recursive($ancestors, function($a) use(&$return) { $return[] = $a; });
        return array_unique($return);
    }
}
