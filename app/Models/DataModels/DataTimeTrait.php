<?php

namespace App\Models\DataModels;

use DateTime;
use DateTimeZone;

trait DataTimeTrait
{
    static function ensureTime(array $fields, array &$target)
    {
        foreach ($fields as $f) {
            if (!isset($target[$f]) || !ctype_digit($target[$f]) || $target[$f] < 0) $target[$f] = time();
        }
    }

    function getStartOfMonth(int $year, int $month, DateTimeZone|null $timezone = null): DateTime
    {

        return new DateTime("first day of $year-$month 00:00:00", $timezone);
    }

    function getEndOfMonth(int $year, int $month, DateTimeZone|null $timezone = null): DateTime
    {
        return new DateTime("last day of $year-$month 23:59:59", $timezone);
    }

    function getNow(DateTimeZone|null $timezone = null): DateTime
    {
        return new DateTime('now', $timezone);
    }
}
