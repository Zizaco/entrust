<?php

namespace Zizaco\Entrust;

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;

/**
 * This class is the main entry point of entrust. Usually the interaction
 * with this class will be done through the Entrust Facade
 *
 * @license MIT
 * @package Zizaco\Entrust
 */
class Entrust
{
    /**
     * Laravel application
     */
    protected Application $app;

    /**
     * Create a new confide instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Checks if the current user has a role by its name
     *
     * @param string|array $role Role name or array of role names.
     * @param bool $requireAll Whether all roles are required.
     * @return bool
     */
    public function hasRole($role, bool $requireAll = false): bool
    {
        return $this->user()?->hasRole($role, $requireAll) ?? false;
    }

    /**
     * Check if the current user has a permission by its name
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool $requireAll Whether all permissions are required.
     * @return bool
     */
    public function can($permission, bool $requireAll = false): bool
    {
        return $this->user()?->can($permission, $requireAll) ?? false;
    }

    /**
     * Check if the current user has a role or permission by its name
     *
     * @param array|string $roles The role(s) needed.
     * @param array|string $permissions The permission(s) needed.
     * @param array $options The Options.
     * @return bool
     */
    public function ability($roles, $permissions, array $options = []): bool
    {
        return $this->user()?->ability($roles, $permissions, $options) ?? false;
    }

    /**
     * Get the currently authenticated user or null.
     */
    public function user(): ?Authenticatable
    {
        return $this->app->auth->user();
    }

    /**
     * Filters a route for a role or set of roles.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string $route Route pattern. i.e: "admin/*"
     * @param array|string $roles The role(s) needed
     * @param mixed $result i.e: Redirect::to('/')
     * @param bool $requireAll User must have all roles
     */
    public function routeNeedsRole(string $route, $roles, $result = null, bool $requireAll = true): void
    {
        $this->registerRouteFilter(
            $route,
            $roles,
            fn () => $this->handleRouteAccessCheck($this->hasRole($roles, $requireAll), $result),
            'role'
        );
    }

    /**
     * Filters a route for a permission or set of permissions.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string $route Route pattern. i.e: "admin/*"
     * @param array|string $permissions The permission(s) needed
     * @param mixed $result i.e: Redirect::to('/')
     * @param bool $requireAll User must have all permissions
     */
    public function routeNeedsPermission(string $route, $permissions, $result = null, bool $requireAll = true): void
    {
        $this->registerRouteFilter(
            $route,
            $permissions,
            fn () => $this->handleRouteAccessCheck($this->can($permissions, $requireAll), $result),
            'permission'
        );
    }

    /**
     * Filters a route for role(s) and/or permission(s).
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string $route Route pattern. i.e: "admin/*"
     * @param array|string $roles The role(s) needed
     * @param array|string $permissions The permission(s) needed
     * @param mixed $result i.e: Redirect::to('/')
     * @param bool $requireAll User must have all roles and permissions
     */
    public function routeNeedsRoleOrPermission(
        string $route,
        $roles,
        $permissions,
        $result = null,
        bool $requireAll = false
    ): void {
        $filterName = $this->generateFilterName([$roles, $permissions], $route);

        $closure = function () use ($roles, $permissions, $result, $requireAll) {
            $hasRole = $this->hasRole($roles, $requireAll);
            $hasPerms = $this->can($permissions, $requireAll);

            $hasAccess = $requireAll ? ($hasRole && $hasPerms) : ($hasRole || $hasPerms);

            $this->handleRouteAccessCheck($hasAccess, $result);
        };

        $this->app->router->filter($filterName, $closure);
        $this->app->router->when($route, $filterName);
    }

    /**
     * Register a route filter with the router.
     */
    protected function registerRouteFilter(string $route, $items, callable $closure, string $type): void
    {
        $filterName = $this->generateFilterName($items, $route, $type);
        
        $this->app->router->filter($filterName, $closure);
        $this->app->router->when($route, $filterName);
    }

    /**
     * Generate a unique filter name based on items and route.
     */
    protected function generateFilterName($items, string $route, string $type = ''): string
    {
        $parts = array_map(fn ($item) => is_array($item) ? implode('_', $item) : $item, (array) $items);
        
        return ($type ? $type . '_' : '') . implode('_', $parts) . '_' . substr(md5($route), 0, 6);
    }

    /**
     * Handle the route access check result.
     */
    protected function handleRouteAccessCheck(bool $hasAccess, $result): void
    {
        if (!$hasAccess) {
            empty($result) ? $this->app->abort(403) : $result;
        }
    }
}
