<?php namespace Zizaco\Entrust;

use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * This class is the main entry point of entrust. Usually this the interaction
 * with this class will be done trought the Entrust Facade
 *
 * @license MIT
 * @package Zizaco\Enstrust
 */
class Entrust
{
    /**
     * Laravel application
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;

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
     * Checks if a user has a Role by its name. Can optionally specify a class
     * name and id to check for a role on a given class or class instance
     * respectively.
     *
     * @param string $name Role name.
     * @param string $modelName the name of the resource model to which the check for roles and permissions will apply
     * @param mixed $modelId the instance identifier of the resource model
     *
     * @return bool
     */
    public function hasRole($name, $modelName=false, $modelId=false)
    {
        $user = $this->user();

        if (!empty($user)) {
            return $user->hasRole($name, $modelName, $modelId);
        }

        return false;
    }

    /**
     * Check if the current user has a permission by its name. Can optionally
     * specify a class name and id to check for a role on a given class or
     * class instance respectively.
     *
     * @param string $permission Permission string.
     * @param string $modelName the name of the resource model to which the check for roles and permissions will apply
     * @param mixed $modelId the instance identifier of the resource model
     *
     * @return bool
     */
    public function can($permission, $modelName=false, $modelId=false)
    {
      $user = $this->user();

      if ($user) {
          return $user->can($permission, $modelName, $modelId);
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
     * Filters a route for the name Role.
     *
     * If the third parameter is null then return 403.
     * Overwise the $result is returned.
     *
     * @param string       $route      Route pattern. i.e: "admin/*"
     * @param array|string $roles      The role(s) needed.
     * @param mixed        $result     i.e: Redirect::to('/')
     * @param bool         $cumulative Must have all roles.
     *
     * @return mixed
     */
    public function routeNeedsRole($route, $roles, $result = null, $cumulative = true)
    {
        if (!is_array($roles)) {
            $roles = array($roles);
        }

        $filter_name = implode('_', $roles).'_'.substr(md5($route), 0, 6);

        if (!$result instanceof Closure) {
            $result = function () use ($roles, $result, $cumulative) {
                $hasARole = array();
                foreach ($roles as $role) {
                    list($roleName, $modelName, $modelId) = explode(':', $role);

                    if ($this->hasRole($roleName, $modelName, $modelId)) {
                        $hasARole[] = true;
                    } else {
                        $hasARole[] = false;
                    }
                }

                // Check to see if it is false and then
                // check additive flag and that the array only contains false.
                if (in_array(false, $hasARole) && ($cumulative || count(array_unique($hasARole)) == 1)) {
                    if (! $result) {
                        Facade::getFacadeApplication()->abort(403);
                    }

                    return $result;
                }
            };
        }

        // Same as Route::filter, registers a new filter
        $this->app->router->filter($filter_name, $result);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->app->router->when($route, $filter_name);
    }

    /**
     * Filters a route for the permission.
     *
     * If the third parameter is null then return 403.
     * Overwise the $result is returned.
     *
     * @param string       $route       Route pattern. i.e: "admin/*"
     * @param array|string $permissions The permission needed.
     * @param mixed        $result      i.e: Redirect::to('/')
     * @param bool         $cumulative  Must have all permissions
     *
     * @return mixed
     */
    public function routeNeedsPermission($route, $permissions, $result = null, $cumulative = true)
    {
        if (!is_array($permissions)) {
            $permissions = array($permissions);
        }

        $filter_name = implode('_', $permissions).'_'.substr(md5($route), 0, 6);

        if (!$result instanceof Closure) {

            $result = function () use ($permissions, $result, $cumulative) {
                $hasAPermission = array();
                foreach ($permissions as $permission) {
                    list($permissionName, $modelName, $modelId) = explode(':', $permission);

                    if ($this->can($permissionName, $modelName, $modelId)) {
                        $hasAPermission[] = true;
                    } else {
                        $hasAPermission[] = false;
                    }
                }

                // Check to see if it is false and then
                // check additive flag and that the array only contains false.
                if (in_array(false, $hasAPermission) && ($cumulative || count(array_unique($hasAPermission)) == 1)) {
                    if (! $result) {
                        Facade::getFacadeApplication()->abort(403);
                    }

                    return $result;
                }
            };
        }

        // Same as Route::filter, registers a new filter
        $this->app->router->filter($filter_name, $result);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->app->router->when($route, $filter_name);
    }

    /**
     * Filters a route for the permission.
     *
     * If the third parameter is null then return 403.
     * Overwise the $result is returned.
     *
     * @param string       $route       Route pattern. i.e: "admin/*"
     * @param array|string $roles       The role(s) needed.
     * @param array|string $permissions The permission needed.
     * @param mixed        $result      i.e: Redirect::to('/')
     * @param bool         $cumulative  Must have all permissions
     *
     * @return void
     */
    public function routeNeedsRoleOrPermission($route, $roles, $permissions, $result = null, $cumulative = false)
    {
        if (!is_array($roles)) {
            $roles = array($roles);
        }
        if (!is_array($permissions)) {
            $permissions = array($permissions);
        }

        $filter_name = implode('_', $roles).'_'.implode('_', $permissions).'_'.substr(md5($route), 0, 6);

        if (!$result instanceof Closure) {

            $result = function () use ($roles, $permissions, $result, $cumulative) {
                $hasARole = array();
                foreach ($roles as $role) {
                    list($roleName, $modelName, $modelId) = explode(':', $role);

                    if ($this->hasRole($roleName, $modelName, $modelId)) {
                        $hasARole[] = true;
                    } else {
                        $hasARole[] = false;
                    }
                }

                $hasAPermission = array();
                foreach ($permissions as $permission) {
                    list($permissionName, $modelName, $modelId) = explode(':', $permission);

                    if ($this->can($permissionName, $modelName, $modelId)) {
                        $hasAPermission[] = true;
                    } else {
                        $hasAPermission[] = false;
                    }
                }
                // Check to see if it is false and then
                // check additive flag and that the array only contains false.
                if (((in_array(false, $hasARole) || in_array(false, $hasAPermission))) && ($cumulative || count(array_unique(array_merge($hasARole, $hasAPermission))) == 1 )) {
                    if (! $result) {
                        Facade::getFacadeApplication()->abort(403);
                    }

                    return $result;
                }
            };
        }

        // Same as Route::filter, registers a new filter
        $this->app->router->filter($filter_name, $result);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->app->router->when($route, $filter_name);
    }
}
