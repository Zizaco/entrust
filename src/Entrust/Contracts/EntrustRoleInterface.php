<?php namespace Zizaco\Entrust\Contracts;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

interface EntrustRoleInterface
{
    public function users();
    public function perms();

    public function savePermissions($inputPermissions);
    public function attachPermission($permission);
    public function detachPermission($permission);
    public function attachPermissions($permissions);
    public function detachPermissions($permissions);
}
