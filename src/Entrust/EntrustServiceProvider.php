<?php namespace Zizaco\Entrust;

use Illuminate\Support\ServiceProvider;

class EntrustServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('zizaco/entrust', 'entrust', __DIR__.'/../');

        $this->commands('command.entrust.migration');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEntrust();

        $this->registerCommands();
    }

    /**
	 * Register the application bindings.
	 *
	 * @return void
	 */
	private function registerEntrust()
	{
		$this->app->bind('entrust', function ($app) {
            return new Entrust($app);
        });
	}

	/**
	 * Register the artisan commands.
	 *
	 * @return void
	 */
	private function registerCommands()
	{
        $this->app->bindShared('command.entrust.migration', function ($app) {
            return new MigrationCommand();
        });
	}

    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'command.entrust.migration'
        );
    }
}
