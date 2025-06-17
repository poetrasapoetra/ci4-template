<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Uninstall extends BaseCommand
{
    protected $group       = 'Installation';
    protected $name        = 'app:uninstall';
    protected $description = 'Uninstall application from the system.';
    protected $usage       = 'app:uninstall [options]';
    protected $options     = ['-y' => 'Yes, skip prompt.'];


    function run(array $params)
    {
        $yes = array_key_exists("y", $params);
        if (!$yes) {
            $opt = ["n" => "Cancel", "y" => "Process uninstall"];
            $instalPrompt = CLI::promptByKey(
                [CLI::color(sprintf("%s %s uninstall:", getenv('app.name'), getenv('app.version')), "red"), "Process installation?"],
                $opt
            );
            if ($instalPrompt == "n") {
                CLI::write("Uninstallation canceled");
                return;
            }
        }
        CLI::write(sprintf("Uninstalling %s %s", getenv('app.name'), getenv('app.version')), "light_cyan");
        $installScript = new InstallationScript(false);
        $installScript->uninstall();
        CLI::write("Uninstallation complete", "light_cyan");
        fwrite(STDOUT, "\033[0K");
        CLI::newLine();
    }
}
