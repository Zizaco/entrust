<?php namespace Adesr\Entrust\Contracts;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Adesr\Entrust
 */

interface EntrustMenuInterface
{

    /**
     * Many-to-Many relations with role model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles();

    /**
     * Many-to-Many relations with permission model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function perms();

    /**
     * One-to-Many relations with itself.
     * Get direct decendant (child)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children();

    /**
     * One-to-Many relations with itself.
     * Get direct ancestor (parent)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent();

    /**
     * Sync multiple permissions to current role.
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function syncPermissions($permissions);

    /**
     * Get all ancestors (parents) of given menu in one dimentional array
     *
     * @param object $menu      Object of menu
     *
     * @return array
     */
    public function getAncestors($menu);

}
