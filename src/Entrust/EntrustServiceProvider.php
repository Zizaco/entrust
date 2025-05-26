<?php 

namespace Zizaco\Entrust;

use Illuminate\Support\Facades\Blade;
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
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->registerCommands();
        $this->registerBladeDirectives();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('entrust', fn($app) => new Entrust($app));
        $this->app->alias('entrust', Entrust::class);
        
        $this->registerCommands();
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php', 'entrust'
        );
    }

    /**
     * Publish the package configuration.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('entrust.php'),
        ], 'entrust-config');
    }

    /**
     * Register the artisan commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            'command.entrust.migration'
        ]);

        $this->app->singleton('command.entrust.migration', fn() => new MigrationCommand());
    }

    /**
     * Register the blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        if (!class_exists('\Blade')) {
            return;
        }

        $this->registerRoleDirectives();
        $this->registerPermissionDirectives();
        $this->registerAbilityDirectives();
    }

    /**
     * Register role related directives.
     */
    protected function registerRoleDirectives(): void
    {
        Blade::directive('role', fn($expression) => "<?php if (\\Entrust::hasRole({$expression})) : ?>");
        Blade::directive('endrole', fn() => "<?php endif; // Entrust::hasRole ?>");
    }

    /**
     * Register permission related directives.
     */
    protected function registerPermissionDirectives(): void
    {
        Blade::directive('permission', fn($expression) => "<?php if (\\Entrust::can({$expression})) : ?>");
        Blade::directive('endpermission', fn() => "<?php endif; // Entrust::can ?>");
    }

    /**
     * Register ability related directives.
     */
    protected function registerAbilityDirectives(): void
    {
        Blade::directive('ability', fn($expression) => "<?php if (\\Entrust::ability({$expression})) : ?>");
        Blade::directive('endability', fn() => "<?php endif; // Entrust::ability ?>");
    }

    /**
     * Get the services provided.
     */
    public function provides(): array
    {
        return [
            'command.entrust.migration',
            'entrust',
        ];
    }
}
