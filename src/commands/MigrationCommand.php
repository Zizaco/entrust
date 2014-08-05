<?php namespace Zizaco\Entrust;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'entrust:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a migration following the Entrust specifications.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel->view->addNamespace('entrust',substr(__DIR__,0,-8).'views');
        
        $roles_table = Config::get('entrust::roles_table');
        $user_role_table = Config::get('entrust::user_role_table');
        $permissions_table = Config::get('entrust::permissions_table');
        $permission_role_table = Config::get('entrust::permission_role_table');
/*
        $roles_table = lcfirst($this->option('roles_table'));
        $user_role_table = lcfirst($this->option('user_role_table'));
        $permissions_table = lcfirst($this->option('permissions_table'));
        $permission_role_table = lcfirst($this->option('permission_role_table'));
*/
        $this->line('');
        $this->info(
            "Tables: "
            . "$roles_table, $user_role_table, $permissions_table, $permission_role_table"
        );
        $message = "A migration that creates '$roles_table', '$user_role_table', "
            . "'$permissions_table', '$permission_role_table'"
            . " tables will be created in the app/database/migrations directory.";

        $this->comment($message);
        $this->line('');

        if ( $this->confirm("Proceed with the migration creation? [Yes|no]") ) {

            $this->line('');

            $this->info( "Creating migration..." );
            
            $isMigrationCreated = $this->createMigration(
                $roles_table,
                $user_role_table,
                $permissions_table,
                $permission_role_table
            );
            
            if ($isMigrationCreated) {
                $this->info( "Migration successfully created!" );
            } else {
                $this->error(
                    "Couldn't create migration.\n Check the write permissions".
                    " within the app/database/migrations directory."
                );
            }

            $this->line('');

        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
/*    protected function getOptions()
    {
        return array(
            array(
                'roles_table',
                null,
                InputOption::VALUE_OPTIONAL,
                'Roles table.',
                Config::get('entrust::roles_table')
            ),
            array(
                'user_role_table',
                null,
                InputOption::VALUE_OPTIONAL,
                'Table relating users and roles.',
                Config::get('entrust::user_role_table')
            ),
            array(
                'permissions_table',
                null,
                InputOption::VALUE_OPTIONAL,
                'Permissions table.',
                Config::get('entrust::permissions_table')
            ),
            array(
                'permission_role_table',
                null,
                InputOption::VALUE_OPTIONAL,
                'Table relating permissions and roles.',
                Config::get('entrust::permission_role_table')
            ),
        );
    }
*/
    /**
     * Create the migration.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function createMigration(
        $roles_table,
        $user_role_table,
        $permissions_table,
        $permission_role_table
    ) {
        $migration_file = $this->laravel->path."/database/migrations/".date('Y_m_d_His')."_entrust_setup_tables.php";
        $output = $this->laravel->view->make('entrust::generators.migration')
            ->with('roles_table', $roles_table)
            ->with('user_role_table', $user_role_table)
            ->with('permissions_table', $permissions_table)
            ->with('permission_role_table', $permission_role_table)
            ->render();

        if (!file_exists($migration_file) && $fs = fopen($migration_file, 'x')) {
            fwrite($fs, $output);
            fclose($fs);
            return true;
        }

        return false;
    }
}
