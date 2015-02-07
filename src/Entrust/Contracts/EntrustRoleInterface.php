<?php namespace MicheleAngioni\Entrust\Contracts;

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
