<?php

namespace Zizaco\Entrust\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */
trait EntrustPermissionTrait
{
    /**
     * Many-to-Many relations with role model.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Config::get('entrust.role'),
            Config::get('entrust.permission_role_table'),
            Config::get('entrust.permission_foreign_key'),
            Config::get('entrust.role_foreign_key')
        );
    }

    /**
     * Boot the permission model.
     * Attach event listener to remove the many-to-many records when trying to delete.
     * Will NOT delete any records if the permission model uses soft deletes.
     */
    public static function bootEntrustPermissionTrait(): void
    {
        static::deleting(function ($permission) {
            if (! method_exists(Config::get('entrust.permission'), 'bootSoftDeletes')) {
                $permission->roles()->detach();
            }
        });
    }
}
