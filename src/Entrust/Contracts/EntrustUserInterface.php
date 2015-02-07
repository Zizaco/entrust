<?php namespace MicheleAngioni\Entrust\Contracts;

interface EntrustUserInterface
{
    public function roles();

    public function hasRole($name, $requireAll = false);
    public function can($permission, $requireAll = false);
    public function ability($roles, $permissions, $options = array());

    public function attachRole($role);
    public function detachRole($role);
    public function attachRoles($roles);
    public function detachRoles($roles);
}
