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
        require_once Kernel::faRoot() . '/inventory/includes/db/items_category_db.inc';
        \update_item_category($id, $d['description'], $d['taxTypeId'], $d['salesAccount'], $d['cogsAccount'], $d['inventoryAccount'], $d['adjustmentAccount'], $d['wipAccount'], $d['units'], $d['mbFlag'], $d['dimension1'], $d['dimension2'], $d['noSale'], $d['noPurchase']);
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
        return ['id' => \db_insert_id()];
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
