<?php namespace Zizaco\Entrust;

use Illuminate\Support\ServiceProvider;

class EntrustServiceProvider extends ServiceProvider {

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
		$this->package('zizaco/entrust');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['command.entrust.migration'] = $this->app->share(function($app)
		{
			return new MigrationCommand($app);
		});

		$this->commands(
			'command.entrust.migration'
		);
	}
}
