<?php

declare(strict_types=1);

use Slim\App;
use FAAPI\Controller\AuthController;
use FAAPI\Controller\CatalogController;
use FAAPI\Controller\HealthController;
use FAAPI\Controller\ItemCategoryController;
use FAAPI\Controller\MutationController;
use FAAPI\Controller\TransactionController;
use FAAPI\Http\BearerTokenMiddleware;

return function (App $app): void {
    $app->get('/v1/health', [HealthController::class, 'show']);
    $app->post('/v1/auth/login', [AuthController::class, 'login']);

    $app->group('/v1', function ($group): void {
        $group->get('/companies', [CatalogController::class, 'companies'])->add(new BearerTokenMiddleware(['SA_OPEN']));
        $group->get('/items/categories', [ItemCategoryController::class, 'index'])->add(new BearerTokenMiddleware(['SA_ITEMCATEGORY', 'SA_ITEMSSTATVIEW', 'SA_ITEMSTRANSVIEW']));
        $group->get('/items/categories/{id}', [CatalogController::class, 'itemCategory'])->add(new BearerTokenMiddleware(['SA_ITEMCATEGORY', 'SA_ITEMSSTATVIEW', 'SA_ITEMSTRANSVIEW']));
        $group->patch('/items/categories/{id}/inactive', [CatalogController::class, 'setCategoryInactive'])->add(new BearerTokenMiddleware(['SA_ITEMCATEGORY']));
        $group->get('/items', [CatalogController::class, 'items'])->add(new BearerTokenMiddleware(['SA_ITEMSSTATVIEW', 'SA_ITEMSTRANSVIEW', 'SA_ITEM']));
        $group->get('/items/{stockId}', [CatalogController::class, 'item'])->add(new BearerTokenMiddleware(['SA_ITEMSSTATVIEW', 'SA_ITEMSTRANSVIEW', 'SA_ITEM']));
        $group->patch('/items/{stockId}/inactive', [CatalogController::class, 'setItemInactive'])->add(new BearerTokenMiddleware(['SA_ITEM']));
        $group->get('/customers', [CatalogController::class, 'customers'])->add(new BearerTokenMiddleware(['SA_CUSTOMER', 'SA_SALESTRANSVIEW', 'SA_SALESANALYTIC']));
        $group->get('/customers/{id}', [CatalogController::class, 'customer'])->add(new BearerTokenMiddleware(['SA_CUSTOMER', 'SA_SALESTRANSVIEW', 'SA_SALESANALYTIC']));
        $group->patch('/customers/{id}/inactive', [CatalogController::class, 'setCustomerInactive'])->add(new BearerTokenMiddleware(['SA_CUSTOMER']));
        $group->get('/suppliers', [CatalogController::class, 'suppliers'])->add(new BearerTokenMiddleware(['SA_SUPPLIER', 'SA_SUPPTRANSVIEW', 'SA_SUPPLIERANALYTIC']));
        $group->get('/suppliers/{id}', [CatalogController::class, 'supplier'])->add(new BearerTokenMiddleware(['SA_SUPPLIER', 'SA_SUPPTRANSVIEW', 'SA_SUPPLIERANALYTIC']));
        $group->patch('/suppliers/{id}/inactive', [CatalogController::class, 'setSupplierInactive'])->add(new BearerTokenMiddleware(['SA_SUPPLIER']));
        $group->get('/currencies', [CatalogController::class, 'currencies'])->add(new BearerTokenMiddleware(['SA_CURRENCY', 'SA_EXCHANGERATE', 'SA_OPEN']));
        $group->get('/exchange-rates', [CatalogController::class, 'exchangeRates'])->add(new BearerTokenMiddleware(['SA_EXCHANGERATE', 'SA_OPEN']));
        $group->get('/bank-accounts', [CatalogController::class, 'bankAccounts'])->add(new BearerTokenMiddleware(['SA_BANKACCOUNT', 'SA_BANKTRANSVIEW']));
        $group->get('/bank-accounts/{id}', [CatalogController::class, 'bankAccount'])->add(new BearerTokenMiddleware(['SA_BANKACCOUNT', 'SA_BANKTRANSVIEW']));
        $group->get('/gl/accounts', [CatalogController::class, 'glAccounts'])->add(new BearerTokenMiddleware(['SA_GLACCOUNT', 'SA_GLTRANSVIEW', 'SA_GLANALYTIC', 'SA_OPEN']));
        $group->get('/gl/accounts/{code}', [CatalogController::class, 'glAccount'])->add(new BearerTokenMiddleware(['SA_GLACCOUNT', 'SA_GLTRANSVIEW', 'SA_GLANALYTIC', 'SA_OPEN']));
        $group->get('/tax/types', [CatalogController::class, 'taxTypes'])->add(new BearerTokenMiddleware(['SA_TAXRATES', 'SA_TAXREP', 'SA_OPEN']));
        $group->get('/tax/groups', [CatalogController::class, 'taxGroups'])->add(new BearerTokenMiddleware(['SA_TAXGROUPS', 'SA_TAXREP', 'SA_OPEN']));
        $group->get('/locations', [CatalogController::class, 'locations'])->add(new BearerTokenMiddleware(['SA_INVENTORYLOCATION', 'SA_ITEMSSTATVIEW', 'SA_ITEMSTRANSVIEW']));

        $group->get('/sales/orders', [CatalogController::class, 'salesOrders'])->add(new BearerTokenMiddleware(['SA_SALESTRANSVIEW', 'SA_SALESORDER', 'SA_OPEN']));
        $group->post('/sales/orders', [TransactionController::class, 'createSalesOrder'])->add(new BearerTokenMiddleware(['SA_SALESORDER']));
        $group->get('/sales/invoices', [CatalogController::class, 'salesInvoices'])->add(new BearerTokenMiddleware(['SA_SALESTRANSVIEW', 'SA_SALESINVOICE', 'SA_OPEN']));
        $group->get('/purchase/orders', [CatalogController::class, 'purchaseOrders'])->add(new BearerTokenMiddleware(['SA_SUPPTRANSVIEW', 'SA_PURCHASEORDER', 'SA_OPEN']));
        $group->post('/purchase/orders', [TransactionController::class, 'createPurchaseOrder'])->add(new BearerTokenMiddleware(['SA_PURCHASEORDER']));
        $group->get('/customer-payments', [CatalogController::class, 'customerPayments'])->add(new BearerTokenMiddleware(['SA_SALESTRANSVIEW', 'SA_SALESPAYMNT', 'SA_OPEN']));
        $group->get('/supplier-payments', [CatalogController::class, 'supplierPayments'])->add(new BearerTokenMiddleware(['SA_SUPPTRANSVIEW', 'SA_SUPPLIERPAYMNT', 'SA_OPEN']));

        $group->post('/items/categories', [MutationController::class, 'createItemCategory'])->add(new BearerTokenMiddleware(['SA_ITEMCATEGORY']));
        $group->put('/items/categories/{id}', [MutationController::class, 'updateItemCategory'])->add(new BearerTokenMiddleware(['SA_ITEMCATEGORY']));
        $group->post('/items', [MutationController::class, 'createItem'])->add(new BearerTokenMiddleware(['SA_ITEM']));
        $group->put('/items/{stockId}', [MutationController::class, 'updateItem'])->add(new BearerTokenMiddleware(['SA_ITEM']));
        $group->post('/customers', [MutationController::class, 'createCustomer'])->add(new BearerTokenMiddleware(['SA_CUSTOMER']));
        $group->put('/customers/{id}', [MutationController::class, 'updateCustomer'])->add(new BearerTokenMiddleware(['SA_CUSTOMER']));
        $group->post('/suppliers', [MutationController::class, 'createSupplier'])->add(new BearerTokenMiddleware(['SA_SUPPLIER']));
        $group->put('/suppliers/{id}', [MutationController::class, 'updateSupplier'])->add(new BearerTokenMiddleware(['SA_SUPPLIER']));
        $group->get('/journal-entries/{id}', [TransactionController::class, 'getJournalEntry'])->add(new BearerTokenMiddleware(['SA_GLTRANSVIEW', 'SA_JOURNALENTRY', 'SA_OPEN']));
        $group->post('/journal-entries', [TransactionController::class, 'createJournalEntry'])->add(new BearerTokenMiddleware(['SA_JOURNALENTRY']));
        $group->post('/stock-adjustments', [TransactionController::class, 'createStockAdjustment'])->add(new BearerTokenMiddleware(['SA_INVENTORYADJUSTMENT']));
        $group->post('/customer-payments', [TransactionController::class, 'createCustomerPayment'])->add(new BearerTokenMiddleware(['SA_SALESPAYMNT']));
        $group->post('/supplier-payments', [TransactionController::class, 'createSupplierPayment'])->add(new BearerTokenMiddleware(['SA_SUPPLIERPAYMNT']));
    });
};
