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
     * is null then return 404. Overwise the $result is returned
     * 
     * @param string $route  Route pattern. i.e: "admin/*"
     * @param string $role   The role needed.
     * @param mixed  $result i.e: Redirect::to('/')
     *
     * @access public
     *
     * @return void
     */
    public function routeNeedsRole( $route, $role, $result = null )
    {
        $filter_name = $role.'_'.substr(md5($route),0,6);

        // Same as Route::filter, registers a new filter
        $this->_app['router']->addFilter($filter_name, function() use ($role, $result) {
            if (! $this->hasRole($role))
            {
                if(! $result)
                    Facade::getFacadeApplication()->abort(404);

                return $result;
            }
        });

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->_app['router']->matchFilter( $route, $filter_name );
    }

    /**
     * Filters a route for the permission. If the third parameter
     * is null then return 404. Overwise the $result is returned
     * 
     * @param string $route  Route pattern. i.e: "admin/*"
     * @param string $role   The permission needed.
     * @param mixed  $result i.e: Redirect::to('/')
     *
     * @access public
     *
     * @return void
     */
    public function routeNeedsPermission( $route, $permission, $result = null )
    {
        $filter_name = $permission.'_'.substr(md5($route),0,6);

        // Same as Route::filter, registers a new filter
        $this->_app['router']->addFilter($filter_name, function() use ($permission, $result) {
            if (! $this->can($permission))
            {
                if(! $result)
                    Facade::getFacadeApplication()->abort(404);

                return $result;
            }
        });

        // Same as Route::when, assigns a route pattern to the
        // previously created filter.
        $this->_app['router']->matchFilter( $route, $filter_name );
    }
}
