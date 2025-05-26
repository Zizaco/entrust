<?php

namespace Zizaco\Entrust\Traits;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */
trait EntrustUserTrait
{
    /**
     * Get cached roles for the user.
     */
    public function cachedRoles()
    {
        $cacheKey = 'entrust_roles_for_user_'.$this->{$this->primaryKey};
        
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags(Config::get('entrust.role_user_table'))
                ->remember($cacheKey, Config::get('cache.ttl'), function () {
                    return $this->roles()->get();
                });
        }

        return $this->roles()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function save(array $options = []): bool
    {
        $this->flushRoleCache();
        return parent::save($options);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(array $options = []): bool
    {
        $result = parent::delete($options);
        $this->flushRoleCache();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function restore(): ?bool
    {
        $result = parent::restore();
        $this->flushRoleCache();
        return $result;
    }

    /**
     * Many-to-Many relations with Role.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Config::get('entrust.role'),
            Config::get('entrust.role_user_table'),
            Config::get('entrust.user_foreign_key'),
            Config::get('entrust.role_foreign_key')
        );
    }

    /**
     * Boot the user model.
     */
    public static function bootEntrustUserTrait(): void
    {
        static::deleting(function ($user) {
            if (!method_exists(Config::get('auth.providers.users.model'), 'bootSoftDeletes')) {
                $user->roles()->detach();
            }
        });
    }

    /**
     * Check if user has a role.
     */
    public function hasRole($name, bool $requireAll = false): bool
    {
        if (is_array($name)) {
            foreach ($name as $roleName) {
                $hasRole = $this->hasRole($roleName);

                if ($hasRole && !$requireAll) {
                    return true;
                }

                if (!$hasRole && $requireAll) {
                    return false;
                }
            }

            return $requireAll;
        }

        foreach ($this->cachedRoles() as $role) {
            if ($role->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has a permission.
     */
    public function can($permission, bool $requireAll = false): bool
    {
        if (is_array($permission)) {
            foreach ($permission as $permName) {
                $hasPerm = $this->can($permName);

                if ($hasPerm && !$requireAll) {
                    return true;
                }

                if (!$hasPerm && $requireAll) {
                    return false;
                }
            }

            return $requireAll;
        }

        foreach ($this->cachedRoles() as $role) {
            foreach ($role->cachedPermissions() as $perm) {
                if (Str::is($permission, $perm->name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check user abilities.
     */
    public function ability($roles, $permissions, array $options = []): array|bool
    {
        $roles = is_array($roles) ? $roles : explode(',', $roles);
        $permissions = is_array($permissions) ? $permissions : explode(',', $permissions);

        $options = $this->validateAbilityOptions($options);

        $checkedRoles = $this->checkRoles($roles);
        $checkedPermissions = $this->checkPermissions($permissions);

        $validateAll = $this->determineValidationResult($checkedRoles, $checkedPermissions, $options['validate_all']);

        return $this->formatAbilityResult($validateAll, $checkedRoles, $checkedPermissions, $options['return_type']);
    }

    /**
     * Attach a role to user.
     */
    public function attachRole($role): void
    {
        $this->roles()->attach($this->parseRoleId($role));
    }

    /**
     * Detach a role from user.
     */
    public function detachRole($role): void
    {
        $this->roles()->detach($this->parseRoleId($role));
    }

    /**
     * Attach multiple roles to user.
     */
    public function attachRoles($roles): void
    {
        foreach ($roles as $role) {
            $this->attachRole($role);
        }
    }

    /**
     * Detach multiple roles from user.
     */
    public function detachRoles($roles = null): void
    {
        $roles = $roles ?: $this->roles()->get();

        foreach ($roles as $role) {
            $this->detachRole($role);
        }
    }

    /**
     * Filter users by role.
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        });
    }

    /**
     * Flush role cache.
     */
    protected function flushRoleCache(): void
    {
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.role_user_table'))->flush();
        }
    }

    /**
     * Parse role ID from various inputs.
     */
    protected function parseRoleId($role): mixed
    {
        if (is_object($role)) {
            return $role->getKey();
        }

        if (is_array($role)) {
            return $role['id'];
        }

        return $role;
    }

    /**
     * Validate ability options.
     */
    protected function validateAbilityOptions(array $options): array
    {
        $options['validate_all'] = $options['validate_all'] ?? false;
        $options['return_type'] = $options['return_type'] ?? 'boolean';

        if (!in_array($options['return_type'], ['boolean', 'array', 'both'], true)) {
            throw new InvalidArgumentException('Invalid return type');
        }

        return $options;
    }

    /**
     * Check roles for ability.
     */
    protected function checkRoles(array $roles): array
    {
        $checked = [];
        foreach ($roles as $role) {
            $checked[$role] = $this->hasRole($role);
        }
        return $checked;
    }

    /**
     * Check permissions for ability.
     */
    protected function checkPermissions(array $permissions): array
    {
        $checked = [];
        foreach ($permissions as $permission) {
            $checked[$permission] = $this->can($permission);
        }
        return $checked;
    }

    /**
     * Determine validation result.
     */
    protected function determineValidationResult(array $checkedRoles, array $checkedPermissions, bool $validateAll): bool
    {
        if ($validateAll) {
            return !in_array(false, $checkedRoles, true) && !in_array(false, $checkedPermissions, true);
        }

        return in_array(true, $checkedRoles, true) || in_array(true, $checkedPermissions, true);
    }

    /**
     * Format ability result.
     */
    protected function formatAbilityResult(bool $validateAll, array $checkedRoles, array $checkedPermissions, string $returnType): array|bool
    {
        return match ($returnType) {
            'array' => ['roles' => $checkedRoles, 'permissions' => $checkedPermissions],
            'both' => [$validateAll, ['roles' => $checkedRoles, 'permissions' => $checkedPermissions]],
            default => $validateAll,
        };
    }
}
