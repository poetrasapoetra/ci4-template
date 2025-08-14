<?php

namespace App\Commands;

use App\Models\DataModels\DataUser;
use App\Models\ModelUser;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\MigrationRunner;
use Config\Database;
use Config\Migrations;

class InstallationScript
{
    private MigrationRunner $runner;
    private $db;
    private string $table;
    private string $dbGroup = 'default';

    function __construct(private bool $verbose = false)
    {
        $migration = new Migrations();
        $this->runner = new MigrationRunner($migration);
        $this->db = Database::connect($this->dbGroup);
        $this->table = $migration->table;
    }

    protected function migrate($migration)
    {
        include_once $migration->path;
        $class = $migration->class;
        $instance = new $class();
        $instance->up();
    }

    private function colorBlue(string $data)
    {
        return CLI::color($data, 'blue', 'blue');
    }

    private function colorGray(string $data)
    {
        return CLI::color($data, 'light_gray', 'light_gray');
    }

    private function progress(int $step, int $total, $new = false)
    {
        $MAX_BAR = 50;
        if (!$new) {
            fwrite(STDOUT, "\033[1A");
        } else {
            fwrite(STDOUT, "\x1B[2K");
        }
        $step = abs($step);
        $tstep = max($total, 1);
        $percent = $step / $tstep;
        $barCount = (int) round($percent * $MAX_BAR);
        CLI::print(
            $this->colorBlue(str_repeat('█', $barCount))
                . $this->colorGray(str_repeat('░', $MAX_BAR - $barCount))
                . sprintf(' %.2f%%', $percent * 100)
        );
        CLI::newLine();
    }

    function install(?InitialSeed $data = null)
    {
        $addUser = !is_null($data) && $data->hasData();
        if ($this->verbose) {
            CLI::write("◯ Loading data ...");
        }
        $runner = $this->runner;
        $runner->ensureTable();
        $migrations = $runner->findMigrations();
        foreach ($runner->getHistory() as $h) {
            unset($migrations[$runner->getObjectUid($h)]);
        }
        $cfg = new Database();
        $seeds = Seeds::getClasses($cfg);
        $total_task = count($migrations) + count($seeds);
        if ($addUser) $total_task += 1;
        $task_done = 0;

        if ($this->verbose) {
            fwrite(STDOUT, "\033[1A");
            fwrite(STDOUT, "\033[2K");
            CLI::write('✔ Loading data [done]', 'green');
        }
        if ($total_task == 0) {
            fwrite(STDOUT, "\033[2A");
            fwrite(STDOUT, "\033[2K");
            CLI::write("Nothing to install or update", 'yellow');
            CLI::newLine(2);
            return;
        }
        CLI::newLine(3);
        $this->progress($task_done, $total_task);
        $batch = $runner->getLastBatch() + 1;
        foreach ($migrations as $m) {
            if ($this->verbose) {
                fwrite(STDOUT, "\033[3A");
                fwrite(STDOUT, "\033[2K");
                CLI::write("◯ Running migration '$m->class'", "yellow");
                CLI::newLine(2);
            }
            $this->db->table($this->table)->insert([
                'version'   => $m->version,
                'class'     => $m->class,
                'group'     => $this->dbGroup,
                'namespace' => $m->namespace,
                'time'      => time(),
                'batch'     => $batch,
            ]);
            $this->migrate($m);
            $task_done++;
            if ($this->verbose) {
                fwrite(STDOUT, "\033[3A");
                CLI::write("✔ Running migration '$m->class' [complete]", "green");
                fwrite(STDOUT, "\033[2K");
                CLI::newLine(2);
            }
            $this->progress($task_done, $total_task);
        }
        if (count($seeds) > 0) {
            CLI::newLine(1);
        }
        foreach ($seeds as $s) {
            if ($this->verbose) {
                fwrite(STDOUT, "\033[3A");
                fwrite(STDOUT, "\033[2K");
                CLI::write("◯ Seeding table '" . $s->getTable() . "'", 'yellow');
                CLI::newLine(2);
            }
            $sql_run = 0;
            $total_affected = 0;
            $i = 0;
            $next = true;
            $error = false;
            while ($next) {
                list(
                    "affected" => $affected,
                    "next" => $next,
                    "error" => $error
                ) = $s->next();
                $i++;
                $total_affected += $affected;
                if ($affected > 0) {
                    $sql_run++;
                    if ($this->verbose) {
                        $percent = $i / $s->getQueryCount() * 100;
                        fwrite(STDOUT, "\033[3A");
                        fwrite(STDOUT, "\033[2K");
                        if (!$error) {
                            CLI::write(sprintf("◯ Seeding table '%s' [%.2f%%]", $s->getTable(), $percent), 'yellow');
                        } else {
                            CLI::write(sprintf("⮿ Seeding table '%s' [%.2f%%]", $s->getTable(), $percent), 'red');
                        }
                        CLI::newLine(1);
                        $this->progress($task_done, $total_task);
                    }
                }
            }
            $task_done++;

            if ($this->verbose) {
                fwrite(STDOUT, "\033[3A");
                fwrite(STDOUT, "\033[2K");
                if (!$error) {
                    CLI::write("✔ Seeding table '" . $s->getTable() . "' [complete] " . $total_affected . " rows inserted", 'green');
                } else {
                    CLI::write(sprintf("🞫 Seeding table '%s'", $s->getTable(), $percent), 'red');
                }
                fwrite(STDOUT, "\033[2K");
                fwrite(STDOUT, "\033[1B");
            }
            $this->progress($task_done, $total_task);
        }

        if ($addUser) {
            if (!$data->doInsert($this->verbose)) $task_done += 1;
            $this->progress($task_done, $total_task);
        }

        $this->setVersion(Seeds::getLastVersion(), env('app.version', 'V0.0'));
    }
    function uninstall()
    {
        $this->runner->regress();
        $this->setVersion("V0.0", env('app.version', 'V0.0'));
    }
    function setVersion(string $newVer, string $oldVer = "V0.0")
    {
        $baseEnv = ROOTPATH . 'env';
        $envFile = ROOTPATH . '.env';

        if (!is_file($envFile)) {
            if (!is_file($baseEnv)) {
                CLI::write('Both default shipped `env` file and custom `.env` are missing.', 'yellow');
                CLI::newLine();

                return false;
            }

            copy($baseEnv, $envFile);
        }

        $oldFileContents = (string) file_get_contents($envFile);
        $replacementKey  = "\napp.version = {$newVer}";

        if (strpos($oldFileContents, 'app.version') === false) {
            return file_put_contents($envFile, $replacementKey, FILE_APPEND) !== false;
        }

        $newFileContents = preg_replace($this->keyPattern($oldVer), $replacementKey, $oldFileContents);
        // echo $newFileContents;
        return file_put_contents($envFile, $newFileContents) !== false;
    }
    protected function keyPattern(string $oldKey): string
    {
        $escaped = preg_quote($oldKey, '/');

        if ($escaped !== '') {
            $escaped = "[{$escaped}]*";
        }

        return "/^[#\\s]*app.version[=\\s]*['\"]*{$escaped}['\"]*$/m";
    }
}
