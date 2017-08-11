<?php namespace Adesr\Entrust;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Adesr\Entrust
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ModelsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'entrust:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates model files following the Entrust specifications.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel->view->addNamespace('entrust', substr(__DIR__, 0, -8).'views');

        $this->line('');
        $this->info( "Entrust Model Generator" );

        $message = "Entrust Models (Role, Permission and Menu) will be created in app directory";

        $this->comment($message);
        $this->line('');

        if ($this->confirm("Proceed with the models creation? [Yes|no]", "Yes")) {

            $this->line('');

            $this->info("Creating models...");
            if ($this->createModels()) {

                $this->info("Models successfully created!");
            } else {
                $this->error(
                    "Couldn't create models.\n Check the write permissions".
                    " within the app directory."
                );
            }

            $this->line('');

        }
    }

    /**
     * Create the migration.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function createModels()
    {
        $isDone = true;
        $modelFiles = [
            'Role' => base_path("/app")."/Role.php",
            'Permission' => base_path("/app")."/Permission.php",
            'Menu' => base_path("/app")."/Menu.php",
        ];

        foreach ($modelFiles as $key => $file) {
            $output = $this->laravel->view->make('entrust::generators.model'. $key)->render();
            if (!file_exists($file) && $fs = fopen($file, 'x')) {
                fwrite($fs, $output);
                fclose($fs);
                $isDone &= true;
            }
        }

        return $isDone;
    }
}
