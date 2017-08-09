<?php namespace Zizaco\Entrust\Contracts;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
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
    public function child();

    /**
     * One-to-Many relations with itself.
     * Get direct ancestor (parent)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent();

}
