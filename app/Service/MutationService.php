<?php

declare(strict_types=1);

namespace FAAPI\Service;

use FAAPI\FrontAccounting\Kernel;
use InvalidArgumentException;

final class MutationService
{
    /** @param array<string,mixed> $d */
    public function createItemCategory(array $d): array
    {
        Kernel::boot();
        $this->validateItemSetup($d);
        require_once Kernel::faRoot() . '/inventory/includes/db/items_category_db.inc';
        \add_item_category($d['description'], $d['taxTypeId'], $d['salesAccount'], $d['cogsAccount'], $d['inventoryAccount'], $d['adjustmentAccount'], $d['wipAccount'], $d['units'], $d['mbFlag'], $d['dimension1'], $d['dimension2'], $d['noSale'], $d['noPurchase']);
        $id = \db_insert_id();
        return ['id' => $id];
    }

    /** @param array<string,mixed> $d */
    public function updateItemCategory(int $id, array $d): array
    {
        Kernel::boot();
        $this->validateItemSetup($d);
        \db_query(
            'UPDATE ' . TB_PREF . 'stock_category SET '
            . 'description=' . \db_escape($d['description']) . ','
            . 'dflt_tax_type=' . \db_escape($d['taxTypeId']) . ','
            . 'dflt_units=' . \db_escape($d['units']) . ','
            . 'dflt_mb_flag=' . \db_escape($d['mbFlag']) . ','
            . 'dflt_sales_act=' . \db_escape($d['salesAccount']) . ','
            . 'dflt_cogs_act=' . \db_escape($d['cogsAccount']) . ','
            . 'dflt_inventory_act=' . \db_escape($d['inventoryAccount']) . ','
            . 'dflt_adjustment_act=' . \db_escape($d['adjustmentAccount']) . ','
            . 'dflt_wip_act=' . \db_escape($d['wipAccount']) . ','
            . 'dflt_dim1=' . \db_escape($d['dimension1']) . ','
            . 'dflt_dim2=' . \db_escape($d['dimension2']) . ','
            . 'dflt_no_sale=' . \db_escape($d['noSale']) . ','
            . 'dflt_no_purchase=' . \db_escape($d['noPurchase'])
            . ' WHERE category_id=' . \db_escape($id),
            'an item category could not be updated'
        );
        return ['id' => $id];
    }

    /** @param array<string,mixed> $d */
    public function createItem(array $d): array
    {
        Kernel::boot();
        $this->validateItemSetup($d, true);
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';
        \add_item($d['stockId'], $d['description'], $d['longDescription'], $d['categoryId'], $d['taxTypeId'], $d['units'], $d['mbFlag'], $d['salesAccount'], $d['inventoryAccount'], $d['cogsAccount'], $d['adjustmentAccount'], $d['wipAccount'], $d['dimension1'], $d['dimension2'], $d['noSale'], $d['editable'], $d['noPurchase']);
        return ['stockId' => $d['stockId']];
    }

    /** @param array<string,mixed> $d */
    public function updateItem(string $stockId, array $d): array
    {
        Kernel::boot();
        $this->validateItemSetup($d, true);
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';
        \update_item($stockId, $d['description'], $d['longDescription'], $d['categoryId'], $d['taxTypeId'], $d['units'], $d['mbFlag'], $d['salesAccount'], $d['inventoryAccount'], $d['cogsAccount'], $d['adjustmentAccount'], $d['wipAccount'], $d['dimension1'], $d['dimension2'], $d['noSale'], $d['editable'], $d['noPurchase']);
        return ['stockId' => $stockId];
    }

    /** @param array<string,mixed> $d */
    public function createCustomer(array $d): array
    {
        Kernel::boot();
        $this->validateCustomerSetup($d);
        require_once Kernel::faRoot() . '/sales/includes/db/customers_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/branches_db.inc';
        \add_customer($d['name'], $d['reference'], $d['address'], $d['taxId'], $d['currency'], $d['dimension1'], $d['dimension2'], $d['creditStatus'], $d['paymentTerms'], $d['discount'], $d['paymentDiscount'], $d['creditLimit'], $d['salesType'], $d['notes']);
        $customer = \get_customer_by_ref($d['reference']);
        $id = $customer['debtor_no'] ?? null;
        if ($id && $d['createDefaultBranch']) {
            \add_branch(
                $id,
                $d['branchName'] ?: $d['name'],
                $d['branchReference'] ?: $d['reference'],
                $d['branchAddress'] ?: $d['address'],
                $d['salesman'],
                $d['area'],
                $d['taxGroupId'],
                $d['branchSalesAccount'],
                $d['branchSalesDiscountAccount'],
                $d['branchReceivablesAccount'],
                $d['branchPaymentDiscountAccount'],
                $d['defaultLocation'],
                $d['branchPostAddress'] ?: $d['address'],
                0,
                $d['defaultShipVia'],
                $d['notes'],
                null
            );
        }
        return ['id' => $id, 'reference' => $d['reference']];
    }

    /** @param array<string,mixed> $d */
    public function updateCustomer(int $id, array $d): array
    {
        Kernel::boot();
        $this->validateCustomerSetup($d, false);
        require_once Kernel::faRoot() . '/sales/includes/db/customers_db.inc';
        \update_customer($id, $d['name'], $d['reference'], $d['address'], $d['taxId'], $d['currency'], $d['dimension1'], $d['dimension2'], $d['creditStatus'], $d['paymentTerms'], $d['discount'], $d['paymentDiscount'], $d['creditLimit'], $d['salesType'], $d['notes']);
        return ['id' => $id];
    }

    /** @param array<string,mixed> $d */
    public function createSupplier(array $d): array
    {
        Kernel::boot();
        $this->validateSupplierSetup($d);
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        \add_supplier($d['name'], $d['reference'], $d['address'], $d['supplierAddress'], $d['taxId'], $d['website'], $d['accountNumber'], $d['bankAccount'], $d['creditLimit'], $d['dimension1'], $d['dimension2'], $d['currency'], $d['paymentTerms'], $d['payableAccount'], $d['purchaseAccount'], $d['paymentDiscountAccount'], $d['notes'], $d['taxGroupId'], $d['taxIncluded']);
        return ['id' => \db_insert_id()];
    }

    /** @param array<string,mixed> $d */
    public function updateSupplier(int $id, array $d): array
    {
        Kernel::boot();
        $this->validateSupplierSetup($d);
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        \update_supplier($id, $d['name'], $d['reference'], $d['address'], $d['supplierAddress'], $d['taxId'], $d['website'], $d['accountNumber'], $d['bankAccount'], $d['creditLimit'], $d['dimension1'], $d['dimension2'], $d['currency'], $d['paymentTerms'], $d['payableAccount'], $d['purchaseAccount'], $d['paymentDiscountAccount'], $d['notes'], $d['taxGroupId'], $d['taxIncluded']);
        return ['id' => $id];
    }


    /** @param array<string,mixed> $d */
    public function createCustomerBranch(int $customerId, array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/db/branches_db.inc';
        $this->validateBranchSetup($d);
        $this->assertExists('debtors_master', 'debtor_no', $customerId, 'unknown customerId: ' . $customerId);
        \add_branch(
            $customerId,
            $d['branchName'],
            $d['branchReference'],
            $d['branchAddress'],
            $d['salesman'],
            $d['area'],
            $d['taxGroupId'],
            $d['salesAccount'],
            $d['salesDiscountAccount'],
            $d['receivablesAccount'],
            $d['paymentDiscountAccount'],
            $d['defaultLocation'],
            $d['postAddress'],
            $d['groupNo'],
            $d['defaultShipVia'],
            $d['notes'],
            $d['bankAccount'] ?: null
        );
        return ['id' => \db_insert_id(), 'customerId' => $customerId, 'reference' => $d['branchReference']];
    }

    /** @param array<string,mixed> $d */
    public function updateCustomerBranch(int $customerId, int $branchId, array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/db/branches_db.inc';
        $this->validateBranchSetup($d);
        $this->assertExists('cust_branch', 'branch_code', $branchId, 'unknown branchId: ' . $branchId, 'debtor_no=' . \db_escape($customerId));
        \update_branch(
            $customerId,
            $branchId,
            $d['branchName'],
            $d['branchReference'],
            $d['branchAddress'],
            $d['salesman'],
            $d['area'],
            $d['taxGroupId'],
            $d['salesAccount'],
            $d['salesDiscountAccount'],
            $d['receivablesAccount'],
            $d['paymentDiscountAccount'],
            $d['defaultLocation'],
            $d['postAddress'],
            $d['groupNo'],
            $d['defaultShipVia'],
            $d['notes'],
            $d['bankAccount'] ?: null
        );
        return ['id' => $branchId, 'customerId' => $customerId, 'reference' => $d['branchReference']];
    }

    /** @param array<string,mixed> $d */
    private function validateCustomerSetup(array $d, bool $validateBranch = true): void
    {
        $this->assertExists('currencies', 'curr_abrev', $d['currency'], 'unknown currency: ' . $d['currency']);
        $this->assertExists('payment_terms', 'terms_indicator', $d['paymentTerms'], 'unknown paymentTerms: ' . $d['paymentTerms']);
        $this->assertExists('sales_types', 'id', $d['salesType'], 'unknown salesType: ' . $d['salesType']);
        $this->assertExists('credit_status', 'id', $d['creditStatus'], 'unknown creditStatus: ' . $d['creditStatus']);
        if ($validateBranch && ($d['createDefaultBranch'] ?? false)) {
            $branch = [
                'salesman' => $d['salesman'],
                'area' => $d['area'],
                'taxGroupId' => $d['taxGroupId'],
                'salesAccount' => $d['branchSalesAccount'],
                'salesDiscountAccount' => $d['branchSalesDiscountAccount'],
                'receivablesAccount' => $d['branchReceivablesAccount'],
                'paymentDiscountAccount' => $d['branchPaymentDiscountAccount'],
                'defaultLocation' => $d['defaultLocation'],
                'defaultShipVia' => $d['defaultShipVia'],
            ];
            $this->validateBranchSetup($branch);
        }
    }

    /** @param array<string,mixed> $d */
    private function validateSupplierSetup(array $d): void
    {
        $this->assertExists('currencies', 'curr_abrev', $d['currency'], 'unknown currency: ' . $d['currency']);
        $this->assertExists('payment_terms', 'terms_indicator', $d['paymentTerms'], 'unknown paymentTerms: ' . $d['paymentTerms']);
        $this->assertExists('tax_groups', 'id', $d['taxGroupId'], 'unknown taxGroupId: ' . $d['taxGroupId']);
        $this->assertGlAccount($d['payableAccount'], 'payableAccount');
        if ($d['purchaseAccount'] !== '') {
            $this->assertGlAccount($d['purchaseAccount'], 'purchaseAccount');
        }
        $this->assertGlAccount($d['paymentDiscountAccount'], 'paymentDiscountAccount');
    }

    /** @param array<string,mixed> $d */
    private function validateBranchSetup(array $d): void
    {
        $this->assertExists('salesman', 'salesman_code', $d['salesman'], 'unknown salesman: ' . $d['salesman']);
        $this->assertExists('areas', 'area_code', $d['area'], 'unknown sales area: ' . $d['area']);
        $this->assertExists('tax_groups', 'id', $d['taxGroupId'], 'unknown taxGroupId: ' . $d['taxGroupId']);
        $this->assertExists('locations', 'loc_code', $d['defaultLocation'], 'unknown defaultLocation: ' . $d['defaultLocation']);
        $this->assertExists('shippers', 'shipper_id', $d['defaultShipVia'], 'unknown defaultShipVia: ' . $d['defaultShipVia']);
        $this->assertGlAccount($d['salesAccount'], 'salesAccount');
        $this->assertGlAccount($d['salesDiscountAccount'], 'salesDiscountAccount');
        $this->assertGlAccount($d['receivablesAccount'], 'receivablesAccount');
        $this->assertGlAccount($d['paymentDiscountAccount'], 'paymentDiscountAccount');
    }

    /** @param array<string,mixed> $d */
    private function validateItemSetup(array $d, bool $withCategory = false): void
    {
        if ($withCategory) {
            $this->assertExists('stock_category', 'category_id', $d['categoryId'], 'unknown categoryId: ' . $d['categoryId']);
        }
        $this->assertExists('item_tax_types', 'id', $d['taxTypeId'], 'unknown taxTypeId: ' . $d['taxTypeId']);
        $this->assertExists('item_units', 'abbr', $d['units'], 'unknown units: ' . $d['units']);
        foreach (['salesAccount', 'cogsAccount', 'inventoryAccount', 'adjustmentAccount', 'wipAccount'] as $field) {
            $this->assertGlAccount($d[$field], $field);
        }
    }

    private function assertGlAccount(string $account, string $field): void
    {
        if ($account === '') {
            throw new InvalidArgumentException($field . ' is required');
        }
        $this->assertExists('chart_master', 'account_code', $account, 'unknown ' . $field . ': ' . $account);
    }

    private function assertExists(string $table, string $column, int|string $value, string $message, string $extraWhere = ''): void
    {
        $sql = 'SELECT 1 FROM ' . TB_PREF . $table . ' WHERE ' . $column . '=' . \db_escape($value);
        if ($extraWhere !== '') {
            $sql .= ' AND ' . $extraWhere;
        }
        $sql .= ' LIMIT 1';
        $res = \db_query($sql, 'could not validate setup dependency');
        if (\db_num_rows($res) === 0) {
            throw new InvalidArgumentException($message);
        }
    }

}
