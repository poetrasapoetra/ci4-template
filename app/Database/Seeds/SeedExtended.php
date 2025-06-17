<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Seeder;
use Config\Database;

class SeedExtended extends Seeder
{
    protected array $sqls = [];
    protected string $table = '';
    protected int $pointer = 0;
    protected bool $raw = false;
    public int $insert_count = 0;

    function __construct(Database $cfg, ?BaseConnection $db = null)
    {
        parent::__construct($cfg, $db);
    }
    function getQueryCount()
    {
        return count($this->sqls);
    }
    function getTable()
    {
        return $this->table;
    }
    function next()
    {
        $affected = 0;
        $hasNext = false;
        $error = false;
        if ($this->pointer < $this->getQueryCount()) {
            $sql = $this->sqls[$this->pointer++];
            try {
                if ($this->raw) {
                    $this->db->query($sql);
                } else {
                    $this->db->table($this->table)->insert($sql);
                }
                $affected = $this->db->affectedRows();
            } catch (DatabaseException $e) {
                $affected = 0;
                $error = true;
                if (isDev()) {
                    CLI::error($e->getMessage());
                }
            }
            $this->insert_count += $affected;
            $hasNext =  $this->pointer < $this->getQueryCount() && !$error;
        }
        return [
            "affected" => $affected,
            "next" => $hasNext,
            "error" => $error
        ];
    }
}
