<?php

declare(strict_types=1);

namespace FAAPI\Service;

use FAAPI\FrontAccounting\Kernel;

final class MutationService
{
    /** @param array<string,mixed> $d */
    public function createItemCategory(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/inventory/includes/db/items_category_db.inc';
        \add_item_category($d['description'], $d['taxTypeId'], $d['salesAccount'], $d['cogsAccount'], $d['inventoryAccount'], $d['adjustmentAccount'], $d['wipAccount'], $d['units'], $d['mbFlag'], $d['dimension1'], $d['dimension2'], $d['noSale'], $d['noPurchase']);
        $id = \db_insert_id();
        return ['id' => $id];
    }

    /** @param array<string,mixed> $d */
    public function updateItemCategory(int $id, array $d): array
    {
        Kernel::boot();
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
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';
        \add_item($d['stockId'], $d['description'], $d['longDescription'], $d['categoryId'], $d['taxTypeId'], $d['units'], $d['mbFlag'], $d['salesAccount'], $d['inventoryAccount'], $d['cogsAccount'], $d['adjustmentAccount'], $d['wipAccount'], $d['dimension1'], $d['dimension2'], $d['noSale'], $d['editable'], $d['noPurchase']);
        return ['stockId' => $d['stockId']];
    }

    /** @param array<string,mixed> $d */
    public function updateItem(string $stockId, array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';
        \update_item($stockId, $d['description'], $d['longDescription'], $d['categoryId'], $d['taxTypeId'], $d['units'], $d['mbFlag'], $d['salesAccount'], $d['inventoryAccount'], $d['cogsAccount'], $d['adjustmentAccount'], $d['wipAccount'], $d['dimension1'], $d['dimension2'], $d['noSale'], $d['editable'], $d['noPurchase']);
        return ['stockId' => $stockId];
    }

    /** @param array<string,mixed> $d */
    public function createCustomer(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/db/customers_db.inc';
        \add_customer($d['name'], $d['reference'], $d['address'], $d['taxId'], $d['currency'], $d['dimension1'], $d['dimension2'], $d['creditStatus'], $d['paymentTerms'], $d['discount'], $d['paymentDiscount'], $d['creditLimit'], $d['salesType'], $d['notes']);
        $customer = \get_customer_by_ref($d['reference']);
        return ['id' => $customer['debtor_no'] ?? null, 'reference' => $d['reference']];
    }

    /** @param array<string,mixed> $d */
    public function updateCustomer(int $id, array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/db/customers_db.inc';
        \update_customer($id, $d['name'], $d['reference'], $d['address'], $d['taxId'], $d['currency'], $d['dimension1'], $d['dimension2'], $d['creditStatus'], $d['paymentTerms'], $d['discount'], $d['paymentDiscount'], $d['creditLimit'], $d['salesType'], $d['notes']);
        return ['id' => $id];
    }

    /** @param array<string,mixed> $d */
    public function createSupplier(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        \add_supplier($d['name'], $d['reference'], $d['address'], $d['supplierAddress'], $d['taxId'], $d['website'], $d['accountNumber'], $d['bankAccount'], $d['creditLimit'], $d['dimension1'], $d['dimension2'], $d['currency'], $d['paymentTerms'], $d['payableAccount'], $d['purchaseAccount'], $d['paymentDiscountAccount'], $d['notes'], $d['taxGroupId'], $d['taxIncluded']);
        return ['id' => \db_insert_id()];
    }

    /** @param array<string,mixed> $d */
    public function updateSupplier(int $id, array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        \update_supplier($id, $d['name'], $d['reference'], $d['address'], $d['supplierAddress'], $d['taxId'], $d['website'], $d['accountNumber'], $d['bankAccount'], $d['creditLimit'], $d['dimension1'], $d['dimension2'], $d['currency'], $d['paymentTerms'], $d['payableAccount'], $d['purchaseAccount'], $d['paymentDiscountAccount'], $d['notes'], $d['taxGroupId'], $d['taxIncluded']);
        return ['id' => $id];
    }
}
