<?php

namespace Zizaco\Entrust;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

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
     * Execute the console command (compatibility with Laravel < 5.5).
     */
    public function fire()
    {
        $this->handle();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->loadViews();
        
        $tables = [
            'roles' => Config::get('entrust.roles_table'),
            'role_user' => Config::get('entrust.role_user_table'),
            'permissions' => Config::get('entrust.permissions_table'),
            'permission_role' => Config::get('entrust.permission_role_table')
        ];

        $this->displayTableInfo($tables);

        if ($this->confirmCreation()) {
            $this->createMigrationFile($tables);
        }
    }

    /**
     * Load the package views.
     */
    protected function loadViews(): void
    {
        $this->laravel->view->addNamespace('entrust', substr(__DIR__, 0, -8).'views');
    }

    /**
     * Display table information to the user.
     */
    protected function displayTableInfo(array $tables): void
    {
        $this->line('');
        $this->info('Tables: '.implode(', ', $tables));
        
        $message = "A migration that creates '".implode("', '", $tables).
                   "' tables will be created in database/migrations directory";
        
        $this->comment($message);
        $this->line('');
    }

    /**
     * Confirm with user before creating migration.
     */
    protected function confirmCreation(): bool
    {
        return $this->confirm(
            "Proceed with the migration creation? [Yes|no]", 
            "Yes"
        );
    }

    /**
     * Create the migration file.
     */
    protected function createMigrationFile(array $tables): void
    {
        $this->info("Creating migration...");
        
        if ($this->createMigration(
            $tables['roles'],
            $tables['role_user'],
            $tables['permissions'],
            $tables['permission_role']
        )) {
            $this->info("Migration successfully created!");
        } else {
            $this->error(
                "Couldn't create migration.\nCheck the write permissions ".
                "within the database/migrations directory."
            );
        }
        
        $this->line('');
    }

    /**
     * Generate the migration file content.
     */
    protected function createMigration(
        string $rolesTable,
        string $roleUserTable,
        string $permissionsTable,
        string $permissionRoleTable
    ): bool {
        $migrationPath = $this->getMigrationPath();
        $data = $this->getMigrationData($rolesTable, $roleUserTable, $permissionsTable, $permissionRoleTable);
        
        $output = $this->laravel->view->make('entrust::generators.migration')->with($data)->render();

        return $this->writeMigrationFile($migrationPath, $output);
    }

    /**
     * Get the path for the migration file.
     */
    protected function getMigrationPath(): string
    {
        return database_path('migrations/'.date('Y_m_d_His').'_entrust_setup_tables.php');
    }

    /**
     * Prepare data for the migration template.
     */
    protected function getMigrationData(
        string $rolesTable,
        string $roleUserTable,
        string $permissionsTable,
        string $permissionRoleTable
    ): array {
        $userModelName = Config::get('auth.providers.users.model');
        $userModel = new $userModelName();
        
        return [
            'rolesTable' => $rolesTable,
            'roleUserTable' => $roleUserTable,
            'permissionsTable' => $permissionsTable,
            'permissionRoleTable' => $permissionRoleTable,
            'usersTable' => $userModel->getTable(),
            'userKeyName' => $userModel->getKeyName()
        ];
    }

    /**
     * Write the migration file to disk.
     */
    protected function writeMigrationFile(string $path, string $content): bool
    {
        if (!file_exists($path) && $fs = fopen($path, 'x')) {
            fwrite($fs, $content);
            fclose($fs);
            return true;
        }
        
        return false;
    }
}
