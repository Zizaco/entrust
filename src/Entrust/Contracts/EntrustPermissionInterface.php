<?php namespace Adesr\Entrust\Contracts;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Adesr\Entrust
 */

interface EntrustPermissionInterface
{

    /**
     * Many-to-Many relations with role model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles();
}
