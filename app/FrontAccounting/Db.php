<?php

declare(strict_types=1);

namespace FAAPI\FrontAccounting;

final class Db
{
    /** @return list<array<string,mixed>> */
    public static function rows(mixed $result): array
    {
        $rows = [];
        while ($row = \db_fetch_assoc($result)) {
            $rows[] = self::normalizeRow($row);
        }
        return $rows;
    }

    /** @return array<string,mixed> */
    public static function row(mixed $result): array
    {
        $row = \db_fetch_assoc($result);
        return is_array($row) ? self::normalizeRow($row) : [];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    public static function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && is_numeric($value)) {
                if (ctype_digit($value)) {
                    $row[$key] = (int) $value;
                } elseif (is_numeric($value)) {
                    $row[$key] = (float) $value;
                }
            }
        }
        return $row;
    }
}
