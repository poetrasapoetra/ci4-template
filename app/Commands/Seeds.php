<?php

namespace App\Commands;

use App\Database\Seeds\SeedExtended;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class Seeds
{
    private static function versioning()
    {
        return [];
        // return [
        //     'V0.1' => [
        //         ToolsSeeds::class
        //     ]
        // ];
    }

    /**
     * @return SeedExtended[]
     */
    static function getClasses(Database $cfg, ?BaseConnection $db = null)
    {
        $v = getenv('app.version', true);
        if (!is_string($v)) {
            $v = 'V0.0';
        }
        $versions = array_keys(self::versioning());
        $returningSeeds = [];
        foreach ($versions as $vr) {
            if (version_compare($v, $vr) >= 0) continue;
            foreach (self::versioning()[$vr] as $class) {
                $returningSeeds[] = new $class($cfg, $db);
            }
        }
        return $returningSeeds;
    }

    static function getLastVersion(): string
    {
        $keys = array_keys(self::versioning());
        return array_pop($keys) ?? "V0.0";
    }
}
