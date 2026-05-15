<?php

declare(strict_types=1);

namespace FAAPI\Service;

use FAAPI\FrontAccounting\Db;
use FAAPI\FrontAccounting\Kernel;

final class CatalogService
{
    /** @return list<array<string,mixed>> */
    public function companies(): array
    {
        Kernel::boot();
        $rows = [];
        foreach (($GLOBALS['db_connections'] ?? []) as $index => $connection) {
            $rows[] = [
                'id' => (int) $index,
                'name' => $connection['name'] ?? ('Company ' . $index),
                'database' => $connection['dbname'] ?? null,
                'tablePrefix' => $connection['tbpref'] ?? null,
            ];
        }
        return $rows;
    }

    /** @return list<array<string,mixed>> */
    public function items(bool $includeInactive = false, bool $fixedAssets = false): array
    {
        Kernel::boot();
        $where = [$fixedAssets ? "mb_flag='F'" : "mb_flag!='F'"];
        if (!$includeInactive) {
            $where[] = '!inactive';
        }
        return $this->queryRows('SELECT * FROM ' . TB_PREF . 'stock_master WHERE ' . implode(' AND ', $where) . ' ORDER BY stock_id', 'could not get items');
    }

    /** @return list<array<string,mixed>> */
    public function customers(bool $includeInactive = false): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'debtors_master';
        if (!$includeInactive) {
            $sql .= ' WHERE !inactive';
        }
        $sql .= ' ORDER BY name';
        return $this->queryRows($sql, 'could not get customers');
    }

    /** @return list<array<string,mixed>> */
    public function suppliers(bool $includeInactive = false): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'suppliers';
        if (!$includeInactive) {
            $sql .= ' WHERE !inactive';
        }
        $sql .= ' ORDER BY supp_name';
        return $this->queryRows($sql, 'could not get suppliers');
    }

    /** @return list<array<string,mixed>> */
    public function currencies(bool $includeInactive = false): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_currencies.inc';
        return Db::rows(\get_currencies($includeInactive));
    }

    /** @return list<array<string,mixed>> */
    public function exchangeRates(?string $currency = null): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'exchange_rates';
        if ($currency !== null && $currency !== '') {
            $sql .= ' WHERE curr_code=' . \db_escape($currency);
        }
        $sql .= ' ORDER BY date_ DESC, curr_code';
        return $this->queryRows($sql, 'could not get exchange rates');
    }

    /** @return list<array<string,mixed>> */
    public function bankAccounts(bool $includeInactive = false): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_bank_accounts.inc';
        return Db::rows(\get_bank_accounts($includeInactive));
    }

    /** @return list<array<string,mixed>> */
    public function glAccounts(?string $from = null, ?string $to = null, ?string $type = null): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_accounts.inc';
        return Db::rows(\get_gl_accounts($from ?: null, $to ?: null, $type ?: null));
    }

    /** @return list<array<string,mixed>> */
    public function taxTypes(bool $includeInactive = false): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/taxes/db/tax_types_db.inc';
        return Db::rows(\get_all_tax_types($includeInactive));
    }

    /** @return list<array<string,mixed>> */
    public function taxGroups(bool $includeInactive = false): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/taxes/db/tax_groups_db.inc';
        return Db::rows(\get_all_tax_groups($includeInactive));
    }

    /** @return list<array<string,mixed>> */
    public function locations(bool $includeInactive = false, bool $fixedAssets = false): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/inventory/includes/db/items_locations_db.inc';
        return Db::rows(\get_item_locations($includeInactive, $fixedAssets ? 1 : 0));
    }

    /** @return list<array<string,mixed>> */
    private function queryRows(string $sql, string $message): array
    {
        return Db::rows(\db_query($sql, $message));
    }
}
