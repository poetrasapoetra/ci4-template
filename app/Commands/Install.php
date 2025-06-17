<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Install extends BaseCommand
{
    protected $group       = 'Installation';
    protected $name        = 'app:install';
    protected $description = 'Install application in the system.';
    protected $usage       = 'app:install [options]';
    protected $options     = ['-y' => 'Yes, skip prompt.', '-v' => 'Verbose'];


    function run(array $params)
    {
        // CLI::clearScreen();
        $verbose = array_key_exists("v", $params);
        $yes = array_key_exists("y", $params);
        $instalPrompt = "";
        if (!$yes) {
            $opt = ["y" => "Process install", "v" => "Install verbose", "n" => "Cancel"];
            if ($verbose) {
                $opt = ["y" => "Process install", "n" => "Cancel"];
            }
            $instalPrompt = CLI::promptByKey(
                [CLI::color(sprintf("%s %s install:", getenv('app.name'), getenv('app.version')), "light_cyan"), "Process installation?"],
                $opt
            );
            if ($instalPrompt == "n") {
                CLI::write("Installation canceled", 'red');
                return;
            }
        }
        $verbose = ($verbose || ($instalPrompt == "v"));
        CLI::write(sprintf("Installing %s %s", getenv('app.name'), Seeds::getLastVersion()), "light_cyan");
        CLI::newLine();
        $v = getenv('app.version', true);
        $initialPromptData = null;
        if (version_compare("V0.0", $v) == 0) {
            $initialPromptData = new InitialSeed("default");
            $initialPromptData->runPrompt();
        }
        $installScript = new InstallationScript($verbose);
        CLI::wait(1);
        $installScript->install($initialPromptData);
        if ($verbose) {
            fwrite(STDOUT, "\033[2A");
            fwrite(STDOUT, "\033[0J");
            CLI::newLine(2);
        }
        CLI::write("Installation complete", "light_cyan");
    }

    function promptSilent(string $prompt)
    {
        CLI::write($prompt);
        fwrite(STDOUT, "\033[1A");
        $len = $this->visible_length($prompt) + 1;
        fwrite(STDOUT, "\033[{$len}G");

        system('stty -echo');     // Disable echo
        $password = rtrim(fgets(STDIN));
        system('stty echo');      // Re-enable echo
        CLI::newLine();
        return $password;
    }
    function visible_length($str)
    {
        // Strip ANSI escape codes (e.g. \033[31m)
        $stripped = preg_replace('/\033\[[0-9;]*[A-Za-z]/', '', $str);
        return strlen($stripped);
    }
}
