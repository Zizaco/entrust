<?php namespace Zizaco\Entrust;

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
     * @return Illuminate\Auth\UserInterface|null
     */
    public function user()
    {
        return $this->_app['auth']->user();
    }
}
