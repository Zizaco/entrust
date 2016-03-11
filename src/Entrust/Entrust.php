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
    private $filterNameStr;
    private $route;
    private $privilegesArr;
    private $result;
    private $requireAll = false;

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
    public function hasRole($role)
    {
        if ($user = $this->user()) {
            return $user->hasRole($role, $this->requireAll);
        }

        return false;
    }

    private function checkUserPrivileges()
    {
        $res = [];
        foreach ($this->privilegesArr as $method => $value) {
            $res[] = $this->$method($value, $this->requireAll);
        }

        return $res;
    }

    /**
     * Check if the current user has a permission by its name
     *
     * @param string $permission Permission string.
     *
     * @return bool
     */
    public function can($permission)
    {
        if ($user = $this->user()) {
            return $user->can($permission, $this->requireAll);
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
        $this->privilegesArr = ['hasRole' => $roles];
        $this->route         = $route;
        $this->result        = $result;
        $this->requireAll    = $requireAll;


        $this->applyRouteFilters();
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
        $this->privilegesArr = ['can' => $permissions];
        $this->route         = $route;
        $this->result        = $result;
        $this->requireAll    = $requireAll;

        $this->applyRouteFilters();
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
        $this->privilegesArr = ['hasRole' => $roles, 'can' => $permissions];
        $this->route         = $route;
        $this->result        = $result;
        $this->requireAll    = $requireAll;

        $this->applyRouteFilters();
    }

    public function routeNeedsRoleAndPermission($route, $roles, $permissions, $result = false)
    {
        $this->routeNeedsRoleOrPermission($route, $roles, $permissions, $result, true);
    }

    private function addToFilterName($param)
    {
        if ($this->filterNameStr) {
            $this->filterNameStr .= '_';
        }
        $this->filterNameStr .= is_array($param)?implode('_', $param) : $param;
    }

    /**
     * @param $closure
     * @param $filterName
     *
     * @internal param $route
     */
    private function applyRouteFilters()
    {
        $filterName = $this->makeFilterName();
        $closure    = $this->makeClosure();

        $this->app->router->filter($filterName, $closure);
        $this->app->router->when($this->route, $filterName);
    }

    /**
     * @param $has string
     * @param $route
     * @param $need
     * @param bool|false $result
     * @param bool|true $requireAll
     */
    private function makeClosure()
    {
        $closure = function () {
            $hasPerms = $this->checkUserPrivileges();

            $hasRolePerm = false;
            if ($this->requireAll) {
                $hasRolePerm = true;
            }

            foreach ($hasPerms as $hasPerms) {
                if ($hasPerms and !$this->requireAll) {
                    $hasRolePerm = true;
                    break;
                }
                if (!$hasPerms and $this->requireAll) {
                    $hasRolePerm = false;
                    break;
                }
            }

            if ($hasRolePerm) {
                return null;
            }
            if ($this->result) {
                return $this->result;
            }
            $this->app->abort(403);
        };

        return $closure;
    }

    private function makeFilterName()
    {
        $this->filterNameStr = '';
        foreach ($this->privilegesArr as $need) {
            $this->addToFilterName($need);
        }
        $this->addToFilterName(substr(md5($this->route), 0, 6));

        return $this->filterNameStr;
    }
}
