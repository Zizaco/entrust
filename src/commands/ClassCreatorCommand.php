<?php namespace Zizaco\Entrust;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ClassCreatorCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'entrust:classes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates Role and Permission classes';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel->view->addNamespace('entrust', substr(__DIR__, 0, -8).'views');

        $roleModel           = Config::get('entrust.role');
        $permissionModel     = Config::get('entrust.permission');

        $roleModelName       = explode('/', $roleModel);
        $roleModelName       = end($roleModelName);

        $permissionModelName = explode('/', $permissionModel);
        $permissionModelName = end($permissionModelName);

        $classes = compact('roleModelName', 'permissionModelName');

        $this->line('');
        $this->info( "Models: $roleModelName, $permissionModelName" );

        $message = "Creates '$roleModelName', '$permissionModelName' classes will be created in app directory";

        $this->comment($message);
        $this->line('');

        if ($this->confirm("Proceed with the class creation? [Yes|no]", "Yes")) {

            $this->line('');
            $this->info("Creating classes...");

            foreach ($classes as $key => $class) {

                $classFile = app_path($class . '.php');

                if (file_exists($classFile)
                    && !$this->confirm($class . " exists. Proceed with overwriting? [Yes|no]", "Yes")) {
                    $this->info($class . " class creation skipped.");
                    continue;
                }

                if ($this->createClass($class, $classFile)) {
                    $this->info($class . " class successfully created!");
                } else {
                    $this->error(
                        "Couldn't create " . $class . " class.\n".
                        "Check the write permissions within the app directory."
                    );
                }
            }

        }

        $this->line('');
    }

    /**
     * Create the migration.
     *
     * @param string $class Class name
     * @param string $classFile Path to class file
     *
     * @return bool
     */
    protected function createClass($class, $classFile)
    {
        $data = compact('class');
        $output = $this->laravel->view->make('entrust::generators.class')->with($data)->render();

        if ($fs = fopen($classFile, 'w')) {
            fwrite($fs, $output);
            fclose($fs);
            return true;
        }

        return false;
    }
}
