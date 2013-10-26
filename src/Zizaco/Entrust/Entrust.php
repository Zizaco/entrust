<?php namespace Zizaco\Entrust;

use Illuminate\Support\Facades\Facade;

class Entrust
{
    /**
     * Laravel application
     * 
     * @var Illuminate\Foundation\Application
     */
    public $_app;

    /**
     * Create a new confide instance.
     * 
     * @param  Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->_app = $app;
    }

    /**
     * Checks if the current user has a Role by its name
     * 
     * @param string $name Role name.
     *
     * @access public
     *
     * @return boolean
     */
    public function hasRole( $permission )
    {
        $user = $this->user();
        
        if( $user )
        {
            return $user->hasRole( $permission );
        }
        else
        {
            return false;
        }
    }

    /**
     * Check if the current user has a permission by its name
     * 
     * @param string $permission Permission string.
     *
     * @access public
     *
     * @return boolean
     */
    public function can( $permission )
    {
        $user = $this->user();
        
        if( $user )
        {
            return $user->can( $permission );
        }
        else
        {
            return false;
        }
    }



    /**
     * Get the currently authenticated user or null.
     *
     * @access public
     *
     * @return Illuminate\Auth\UserInterface|null
     */
    public function user()
    {
        return $this->_app['auth']->user();
    }

    /**
     * Filters a route for the name Role. If the third parameter
     * is null then return 403. Overwise the $result is returned
     *
     * @param string $route  Route pattern. i.e: "admin/*"
     * @param array|string $roles   The role(s) needed.
     * @param mixed $result i.e: Redirect::to('/')
     * @param bool $cumulative Must have all roles.
     * @access public
     *
     * @return void
     */
    public function routeNeedsRole( $route, $roles, $result = null, $cumulative=true )
    {
        if(!is_array($roles)) {
            $roles = array($roles);
        }

        $filter_name = implode('_',$roles).'_'.substr(md5($route),0,6);

        if (! $result instanceof Closure) {
            $result = function() use ($roles, $result, $cumulative) {
                $hasARole = array();
                foreach($roles as $role) {
                    if ($this->hasRole($role)) {
                        $hasARole[] = true;
                    } else {
                        $hasARole[] = false;
                    }
                }

                // Check to see if it is false and then
                // check additive flag and that the array only contains false.
                if(in_array(false, $hasARole) && ($cumulative || count(array_unique($hasARole)) == 1) ) {
                    if(! $result)
                        Facade::getFacadeApplication()->abort(403);

                    return $result;
                }
            };
        }

        // Same as Route::filter, registers a new filter
        $this->_app['router']->filter($filter_name, $result);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->_app['router']->when( $route, $filter_name );
    }

    /**
     * Filters a route for the permission. If the third parameter
     * is null then return 403. Overwise the $result is returned
     * 
     * @param string $route  Route pattern. i.e: "admin/*"
     * @param array|string $permissions   The permission needed.
     * @param mixed  $result i.e: Redirect::to('/')
     * @param bool $cumulative Must have all permissions
     *
     * @access public
     *
     * @return void
     */
    public function routeNeedsPermission( $route, $permissions, $result = null, $cumulative=true )
    {
        if(!is_array($permissions)) {
            $permissions = array($permissions);
        }

        $filter_name = implode('_',$permissions).'_'.substr(md5($route),0,6);

        if (! $result instanceof Closure)
        {
            $result = function() use ($permissions, $result, $cumulative) {
                $hasAPermission = array();
                foreach($permissions as $permission) {
                    if ($this->can($permission)) {
                        $hasAPermission[] = true;
                    } else {
                        $hasAPermission[] = false;
                    }
                }

                // Check to see if it is false and then
                // check additive flag and that the array only contains false.
                if(in_array(false, $hasAPermission) && ($cumulative || count(array_unique($hasAPermission)) == 1) ) {
                    if(! $result)
                        Facade::getFacadeApplication()->abort(403);

                    return $result;
                }
            };
        }

        // Same as Route::filter, registers a new filter
        $this->_app['router']->filter($filter_name, $result);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->_app['router']->when( $route, $filter_name );
    }

    /**
     * Filters a route for the permission. If the third parameter
     * is null then return 403. Overwise the $result is returned
     *
     * @param string $route  Route pattern. i.e: "admin/*"
     * @param array|string $roles   The role(s) needed.
     * @param array|string $permissions   The permission needed.
     * @param mixed  $result i.e: Redirect::to('/')
     * @param bool $cumulative Must have all permissions
     *
     * @access public
     *
     * @return void
     */
    public function routeNeedsRoleOrPermission( $route, $roles, $permissions, $result = null, $cumulative=false )
    {
        if(!is_array($roles)) {
            $roles = array($roles);
        }
        if(!is_array($permissions)) {
            $permissions = array($permissions);
        }

        $filter_name = implode('_',$roles).'_'.implode('_',$permissions).'_'.substr(md5($route),0,6);

        if (! $result instanceof Closure)
        {
            $result = function() use ($roles, $permissions, $result, $cumulative) {
                $hasARole = array();
                foreach($roles as $role) {
                    if ($this->hasRole($role)) {
                        $hasARole[] = true;
                    } else {
                        $hasARole[] = false;
                    }
                }

                $hasAPermission = array();
                foreach($permissions as $permission) {
                    if ($this->can($permission)) {
                        $hasAPermission[] = true;
                    } else {
                        $hasAPermission[] = false;
                    }
                }
                // Check to see if it is false and then
                // check additive flag and that the array only contains false.
                if(((in_array(false, $hasARole) || in_array(false, $hasAPermission))) && ($cumulative || count(array_unique(array_merge($hasARole, $hasAPermission))) == 1 )) {
                    if(! $result)
                        Facade::getFacadeApplication()->abort(403);

                    return $result;
                }
            };
        }

        // Same as Route::filter, registers a new filter
        $this->_app['router']->filter($filter_name, $result);

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->_app['router']->when( $route, $filter_name );
    }
}
