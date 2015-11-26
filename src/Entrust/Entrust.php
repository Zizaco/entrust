<?php namespace Zizaco\Entrust;

/**
 * This class is the main entry point of entrust. Usually this the interaction
 * with this class will be done through the Entrust Facade
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

class Entrust
{
    /**
     * Laravel application
     *
     * @var \Illuminate\Foundation\Application
     */
    public  $app;
    private $filterName;

    /**
     * Create a new confide instance.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Checks if the current user has a role by its name
     *
     * @param string $name Role name.
     *
     * @return bool
     */
    public function hasRole($role, $requireAll = false)
    {
        if ($user = $this->user()) {
            return $user->hasRole($role, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a permission by its name
     *
     * @param string $permission Permission string.
     *
     * @return bool
     */
    public function can($permission, $requireAll = false)
    {
        if ($user = $this->user()) {
            return $user->can($permission, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a role or permission by its name
     *
     * @param array|string $roles       The role(s) needed.
     * @param array|string $permissions The permission(s) needed.
     * @param array $options            The Options.
     *
     * @return bool
     */
    public function ability($roles, $permissions, $options = [])
    {
        if ($user = $this->user()) {
            return $user->ability($roles, $permissions, $options);
        }

        return false;
    }

    /**
     * Get the currently authenticated user or null.
     *
     * @return Illuminate\Auth\UserInterface|null
     */
    public function user()
    {
        return $this->app->auth->user();
    }

    /**
     * Filters a route for a role or set of roles.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string $route       Route pattern. i.e: "admin/*"
     * @param array|string $roles The role(s) needed
     * @param mixed $result       i.e: Redirect::to('/')
     * @param bool $requireAll    User must have all roles
     *
     * @return mixed
     */
    public function routeNeedsRole($route, $roles, $result = false, $requireAll = true)
    {
        $this->routeNeeds('hasRole', $route, $roles, $result, $requireAll);
    }

    /**
     * Filters a route for a permission or set of permissions.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string $route             Route pattern. i.e: "admin/*"
     * @param array|string $permissions The permission(s) needed
     * @param mixed $result             i.e: Redirect::to('/')
     * @param bool $requireAll          User must have all permissions
     *
     * @return mixed
     */
    public function routeNeedsPermission($route, $permissions, $result = false, $requireAll = true)
    {
        $this->routeNeeds('can', $route, $permissions, $result, $requireAll);
    }

    /**
     * Filters a route for role(s) and/or permission(s).
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string $route             Route pattern. i.e: "admin/*"
     * @param array|string $roles       The role(s) needed
     * @param array|string $permissions The permission(s) needed
     * @param mixed $result             i.e: Redirect::to('/')
     * @param bool $requireAll          User must have all roles and permissions
     *
     * @return void
     */
    public function routeNeedsRoleOrPermission($route, $roles, $permissions, $result = false, $requireAll = false)
    {
        $this->makeFilterNameFor([$roles, $permissions],$route);

        $closure = function () use ($roles, $permissions, $result, $requireAll) {
            $hasPerms = $this->can($permissions, $requireAll);
            $hasRole  = $this->hasRole($roles, $requireAll);

            if ($requireAll) {
                $hasRolePerm = $hasRole && $hasPerms;
            } else {
                $hasRolePerm = $hasRole || $hasPerms;
            }

            if (!$hasRolePerm) {
                return $result?$result : $this->app->abort(403);
            }
        };

        $this->applyRouteFilters($route, $closure);
    }

    public function routeNeedsRoleAndPermission($route, $roles, $permissions, $result = false)
    {
        $this->routeNeedsRoleOrPermission($route, $roles, $permissions, $result = false, true);
    }

    private function addToFilterName($param)
    {
        if ($this->filterName) {
            $this->filterName .= '_';
        }
        $this->filterName .= is_array($param)?implode('_', $param) : $param;
    }

    /**
     * @param $route
     * @param $closure
     */
    private function applyRouteFilters($route, $closure)
    {
        $this->app->router->filter($this->filterName, $closure);
        $this->app->router->when($route, $this->filterName);
    }

    /**
     * @param $has string
     * @param $route
     * @param $need
     * @param bool|false $result
     * @param bool|true $requireAll
     */
    private function routeNeeds($has, $route, $need, $result = false, $requireAll = true)
    {
        $this->makeFilterNameFor([$need],$route);
        $closure = function () use ($has, $need, $result, $requireAll) {
            if (!$this->$has($need, $requireAll)) {
                return $result?$result : $this->app->abort(403);
            }
        };
        $this->applyRouteFilters($route, $closure);
    }

    private function makeFilterNameFor($needs,$route)
    {
        $this->filterName = '';
        foreach($needs as $need){
            $this->addToFilterName($need);
        }
        $this->addToFilterName(substr(md5($route), 0, 6));
    }

}
