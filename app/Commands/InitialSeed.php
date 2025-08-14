<?php

namespace App\Commands;

use CodeIgniter\CLI\CLI;
use Config\Database;

class InitialSeed
{
    /**
     * @var array<array{
     *     prompt: string,
     *     type: 'text'|'password',
     *     required: bool,
     *     default: ?string
     * }>
     */
    private $prompt = [];
    private $data = [];
    private $dbGroup;

    function __construct(string $dbGroup)
    {
        $this->dbGroup = $dbGroup;
        $appname = getenv("app.name");
        $this->prompt = [
            "username" => [
                "prompt" => CLI::color(sprintf("%s admin username", $appname ? $appname : "Application"), "light_cyan"),
                "type" => "text",
                "required" => true,
                "default" => null
            ],
            "password" => [
                "prompt" => CLI::color("Admin password", "light_cyan"),
                "type" => "password",
                "required" => true,
                "default" => null
            ],
            "name" => [
                "prompt" => CLI::color("Admin name", "light_cyan"),
                "type" => "text",
                "required" => true,
                "default" => "Admin"
            ]
        ];
    }

    function runPrompt()
    {
        $yield = [];
        foreach ($this->prompt as $varname => $data) {
            while (true) {
                if ($data["type"] == "password") {
                    $dt = $this->promptSilent($data["prompt"]);
                } else {
                    $p = $data["prompt"];
                    if (isset($data["default"]) && $data["default"]) {
                        $p .= CLI::color(" [default: {$data["default"]}]", "yellow");
                    }
                    $dt = CLI::prompt($p);
                }
                fwrite(STDOUT, "\033[1A");
                fwrite(STDOUT, "\033[2K");
                if (trim($dt) == "" && isset($data["default"]) && $data["default"]) {
                    $dt = $data["default"];
                }
                if (trim($dt) != "" || !$data["required"]) {
                    $yield[$varname] = $dt;
                    break;
                } else {
                    $warn = "[Please insert valid data]";
                    CLI::write(CLI::color($warn, "yellow"));
                    $c = strlen($warn) + 1;
                    fwrite(STDOUT, "\033[1A");
                    fwrite(STDOUT, "\033[{$c}G");
                }
            }
        }
        $this->data = $yield;
        CLI::newLine();
    }

    function hasData()
    {
        return count($this->data) > 0;
    }

    private function promptSilent(string $prompt)
    {
        CLI::write(rtrim($prompt, ":") . " :");
        fwrite(STDOUT, "\033[1A");
        $len = $this->visible_length($prompt) + 3;
        fwrite(STDOUT, "\033[{$len}G");

        system('stty -echo');     // Disable echo
        $data = rtrim(fgets(STDIN));
        system('stty echo');      // Re-enable echo
        CLI::newLine();
        return $data;
    }

    private function visible_length($str)
    {
        // Strip ANSI escape codes (e.g. \033[31m)
        $stripped = preg_replace('/\033\[[0-9;]*[A-Za-z]/', '', $str);
        return strlen($stripped);
    }

    function doInsert(bool $verbose)
    {

        $db = Database::connect($this->dbGroup);
        var_dump($this->data);
        CLI::newLine(4);
        $error = false;
        $errorMessage = "";
        // $user = DataUser::fromArray((array)$this->data);
        // $user->status = DataUser::STATUS_ACTIVE;
        // $user->role = DataUser::ROLE_ADMIN;
        // try {
        //     $db->table("user")->insert($user->getInsertData());
        // } catch (Exception $e) {
        //     $error = true;
        //     $errorMessage = $e->getMessage();
        // }
        // if ($verbose) {
        //     fwrite(STDOUT, "\033[2A");
        //     fwrite(STDOUT, "\033[2K");
        //     if (!$error) {
        //         CLI::write("✔ Inserting '{$user->username}' as admin [complete]", 'green');
        //     } else {
        //         CLI::write("🞫 Inserting '{$user->username}' as admin [failed]: $errorMessage", 'red');
        //     }
        //     fwrite(STDOUT, "\033[2K");
        //     fwrite(STDOUT, "\033[2B");
        // }
        return $error;
    }
}
