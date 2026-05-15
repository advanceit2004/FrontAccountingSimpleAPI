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


    /** @return array<string,mixed> */
    public function item(string $stockId): array
    {
        Kernel::boot();
        return Db::row(\db_query('SELECT * FROM ' . TB_PREF . 'stock_master WHERE stock_id=' . \db_escape($stockId), 'could not get item'));
    }

    /** @return array<string,mixed> */
    public function itemCategory(int $id): array
    {
        Kernel::boot();
        return Db::row(\db_query('SELECT * FROM ' . TB_PREF . 'stock_category WHERE category_id=' . \db_escape($id), 'could not get item category'));
    }

    /** @return array<string,mixed> */
    public function customer(int $id): array
    {
        Kernel::boot();
        return Db::row(\db_query('SELECT * FROM ' . TB_PREF . 'debtors_master WHERE debtor_no=' . \db_escape($id), 'could not get customer'));
    }

    /** @return array<string,mixed> */
    public function supplier(int $id): array
    {
        Kernel::boot();
        return Db::row(\db_query('SELECT * FROM ' . TB_PREF . 'suppliers WHERE supplier_id=' . \db_escape($id), 'could not get supplier'));
    }

    /** @return array<string,mixed> */
    public function bankAccount(int $id): array
    {
        Kernel::boot();
        return Db::row(\db_query('SELECT * FROM ' . TB_PREF . 'bank_accounts WHERE id=' . \db_escape($id), 'could not get bank account'));
    }

    /** @return array<string,mixed> */
    public function glAccount(string $code): array
    {
        Kernel::boot();
        return Db::row(\db_query('SELECT * FROM ' . TB_PREF . 'chart_master WHERE account_code=' . \db_escape($code), 'could not get GL account'));
    }

    public function setInactive(string $resource, string $id, bool $inactive): void
    {
        Kernel::boot();
        $map = [
            'item' => ['table' => 'stock_master', 'key' => 'stock_id'],
            'customer' => ['table' => 'debtors_master', 'key' => 'debtor_no'],
            'supplier' => ['table' => 'suppliers', 'key' => 'supplier_id'],
            'category' => ['table' => 'stock_category', 'key' => 'category_id'],
        ];
        if (!isset($map[$resource])) {
            throw new \InvalidArgumentException('unsupported inactive resource: ' . $resource);
        }
        \db_query(
            'UPDATE ' . TB_PREF . $map[$resource]['table'] . ' SET inactive=' . \db_escape($inactive ? 1 : 0) . ' WHERE ' . $map[$resource]['key'] . '=' . \db_escape($id),
            'could not update inactive status'
        );
    }

    /** @return list<array<string,mixed>> */
    public function salesOrders(?int $customerId = null): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'sales_orders WHERE trans_type=' . \db_escape(ST_SALESORDER);
        if ($customerId) {
            $sql .= ' AND debtor_no=' . \db_escape($customerId);
        }
        $sql .= ' ORDER BY order_no DESC';
        return $this->queryRows($sql, 'could not get sales orders');
    }

    /** @return list<array<string,mixed>> */
    public function salesInvoices(?int $customerId = null): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'debtor_trans WHERE type=' . \db_escape(ST_SALESINVOICE);
        if ($customerId) {
            $sql .= ' AND debtor_no=' . \db_escape($customerId);
        }
        $sql .= ' ORDER BY trans_no DESC';
        return $this->queryRows($sql, 'could not get sales invoices');
    }

    /** @return list<array<string,mixed>> */
    public function purchaseOrders(?int $supplierId = null): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'purch_orders';
        if ($supplierId) {
            $sql .= ' WHERE supplier_id=' . \db_escape($supplierId);
        }
        $sql .= ' ORDER BY order_no DESC';
        return $this->queryRows($sql, 'could not get purchase orders');
    }

    /** @return list<array<string,mixed>> */
    public function customerPayments(?int $customerId = null): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'debtor_trans WHERE type=' . \db_escape(ST_CUSTPAYMENT);
        if ($customerId) {
            $sql .= ' AND debtor_no=' . \db_escape($customerId);
        }
        $sql .= ' ORDER BY trans_no DESC';
        return $this->queryRows($sql, 'could not get customer payments');
    }

    /** @return list<array<string,mixed>> */
    public function supplierPayments(?int $supplierId = null): array
    {
        Kernel::boot();
        $sql = 'SELECT * FROM ' . TB_PREF . 'supp_trans WHERE type=' . \db_escape(ST_SUPPAYMENT);
        if ($supplierId) {
            $sql .= ' AND supplier_id=' . \db_escape($supplierId);
        }
        $sql .= ' ORDER BY trans_no DESC';
        return $this->queryRows($sql, 'could not get supplier payments');
    }

    /** @return list<array<string,mixed>> */
    private function queryRows(string $sql, string $message): array
    {
        return Db::rows(\db_query($sql, $message));
    }
}
