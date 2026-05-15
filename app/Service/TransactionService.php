<?php

declare(strict_types=1);

namespace FAAPI\Service;

use FAAPI\FrontAccounting\Kernel;
use InvalidArgumentException;

final class TransactionService
{
    /** @param array<string,mixed> $d */
    public function createJournalEntry(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/includes/ui/items_cart.inc';
        require_once Kernel::faRoot() . '/gl/includes/db/gl_journal.inc';
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_accounts.inc';

        if (empty($d['lines']) || !is_array($d['lines'])) {
            throw new InvalidArgumentException('lines must be a non-empty array');
        }

        $date = \sql2date($d['date']);
        $documentDate = $d['documentDate'] ? \sql2date($d['documentDate']) : $date;
        $eventDate = $d['eventDate'] ? \sql2date($d['eventDate']) : $date;

        $cart = new \items_cart(ST_JOURNAL);
        $cart->tran_date = $date;
        $cart->doc_date = $documentDate;
        $cart->event_date = $eventDate;
        $cart->source_ref = $d['sourceRef'];
        $cart->memo_ = $d['memo'];
        $cart->currency = $d['currency'] ?: \get_company_pref('curr_default');
        $cart->rate = $d['rate'];
        $cart->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_JOURNAL, null, $cart->tran_date);

        $total = 0.0;
        foreach ($d['lines'] as $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException('each journal line must be an object');
            }
            $account = (string) ($line['account'] ?? '');
            $amount = (float) ($line['amount'] ?? 0);
            if ($account === '' || $amount == 0.0) {
                throw new InvalidArgumentException('each journal line requires account and non-zero amount');
            }
            if (!\get_gl_account($account)) {
                throw new InvalidArgumentException('unknown GL account: ' . $account);
            }
            $total += $amount;
            $cart->add_gl_item($account, (int) ($line['dimension1'] ?? 0), (int) ($line['dimension2'] ?? 0), $amount, (string) ($line['memo'] ?? ''));
        }

        if (round($total, 2) !== 0.0) {
            throw new InvalidArgumentException('journal lines must balance to zero');
        }

        $id = \write_journal_entries($cart);
        return ['id' => $id, 'reference' => $cart->reference];
    }




    /** @param array<string,mixed> $d */
    public function createSalesOrder(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/includes/ui/ui_globals.inc';
        require_once Kernel::faRoot() . '/sales/includes/cart_class.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/sales_order_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/branches_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/sales_types_db.inc';
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';

        if (empty($d['lines']) || !is_array($d['lines'])) {
            throw new InvalidArgumentException('lines must be a non-empty array');
        }

        $customer = \get_customer_to_order($d['customerId']);
        if (!$customer) {
            throw new InvalidArgumentException('unknown customerId: ' . $d['customerId']);
        }
        $branchId = $d['branchId'];
        if (!$branchId) {
            $defaultBranch = \get_default_branch($d['customerId']);
            if (!$defaultBranch) {
                throw new InvalidArgumentException('customer has no branch: ' . $d['customerId']);
            }
            $branchId = (int) $defaultBranch['branch_code'];
        }
        $branchResult = \get_branch_to_order($d['customerId'], $branchId);
        $branch = \db_fetch_assoc($branchResult);
        if (!$branch) {
            throw new InvalidArgumentException('unknown branchId: ' . $branchId);
        }

        $cart = new \Cart(ST_SALESORDER, 0);
        $cart->set_customer($d['customerId'], $customer['name'], $customer['curr_code'], (float) $customer['discount'], (int) $customer['payment_terms'], (float) $customer['pymt_discount']);
        $cart->set_branch($branchId, (int) $branch['tax_group_id'], $branch['tax_group_name'], $d['phone']);
        $cart->set_sales_type((int) $customer['salestype'], $customer['sales_type'], (int) $customer['tax_included'], (float) $customer['factor']);
        $cart->set_location($d['location'] ?: $branch['default_location'], $branch['location_name']);
        $cart->set_delivery((int) ($d['shipVia'] ?: $branch['default_ship_via']), $d['deliverTo'] ?: $branch['br_name'], $d['deliveryAddress'] ?: $branch['br_address'], $d['freightCost']);
        $cart->document_date = \sql2date($d['date']);
        $cart->due_date = \sql2date($d['deliveryDate'] ?: $d['date']);
        $cart->cust_ref = $d['customerReference'];
        $cart->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_SALESORDER, null, ['date' => $cart->document_date, 'customer' => $d['customerId']]);
        $cart->Comments = $d['memo'];
        $cart->dimension_id = $d['dimension1'];
        $cart->dimension2_id = $d['dimension2'];

        foreach (array_values($d['lines']) as $i => $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException('each sales order line must be an object');
            }
            $stockId = (string) ($line['stockId'] ?? '');
            $item = $stockId !== '' ? \get_item($stockId) : null;
            if (!$item) {
                throw new InvalidArgumentException('unknown stockId: ' . $stockId);
            }
            $quantity = (float) ($line['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new InvalidArgumentException('sales order line quantity must be positive');
            }
            $cart->add_to_cart($i, $stockId, $quantity, (float) ($line['price'] ?? 0), (float) ($line['discount'] ?? 0), 0, 0, (string) ($line['description'] ?? $item['description']));
        }

        $id = \add_sales_order($cart);
        return ['id' => $id, 'reference' => $cart->reference];
    }

    /** @param array<string,mixed> $d */
    public function createPurchaseOrder(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/po_class.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/po_db.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';
        require_once Kernel::faRoot() . '/taxes/db/tax_groups_db.inc';

        if (empty($d['lines']) || !is_array($d['lines'])) {
            throw new InvalidArgumentException('lines must be a non-empty array');
        }

        $po = new \purch_order();
        \get_supplier_details_to_order($po, $d['supplierId']);
        if (!$po->supplier_id) {
            throw new InvalidArgumentException('unknown supplierId: ' . $d['supplierId']);
        }
        $po->trans_type = ST_PURCHORDER;
        $po->orig_order_date = \sql2date($d['date']);
        $po->due_date = \sql2date($d['deliveryDate'] ?: $d['date']);
        $po->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_PURCHORDER, null, $po->orig_order_date);
        $po->supp_ref = $d['supplierReference'];
        $po->Location = $d['location'];
        $po->delivery_address = $d['deliveryAddress'];
        $po->Comments = $d['memo'];
        $po->dimension = $d['dimension1'];
        $po->dimension2 = $d['dimension2'];

        foreach (array_values($d['lines']) as $i => $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException('each purchase order line must be an object');
            }
            $stockId = (string) ($line['stockId'] ?? '');
            $item = $stockId !== '' ? \get_item($stockId) : null;
            if (!$item) {
                throw new InvalidArgumentException('unknown stockId: ' . $stockId);
            }
            $quantity = (float) ($line['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new InvalidArgumentException('purchase order line quantity must be positive');
            }
            $po->add_to_order($i, $stockId, $quantity, (string) ($line['description'] ?? $item['description']), (float) ($line['price'] ?? 0), $item['units'] ?? '', \sql2date($line['deliveryDate'] ?? $d['deliveryDate'] ?: $d['date']), 0, 0);
        }

        $id = \add_po($po);
        return ['id' => $id, 'reference' => $po->reference];
    }

    /** @param array<string,mixed> $d */
    public function createCustomerPayment(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/db/payment_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/branches_db.inc';
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_bank_accounts.inc';
        $date = \sql2date($d['date']);
        $id = \write_customer_payment(0, $d['customerId'], $d['branchId'], $d['bankAccount'], $date, $d['reference'], $d['amount'], $d['discount'], $d['memo'], 0, $d['charge'], $d['bankAmount'], $d['dimension1'], $d['dimension2']);
        return ['id' => $id, 'reference' => $d['reference']];
    }

    /** @param array<string,mixed> $d */
    public function createSupplierPayment(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/db/supp_payment_db.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_bank_accounts.inc';
        $date = \sql2date($d['date']);
        $id = \write_supp_payment(0, $d['supplierId'], $d['bankAccount'], $date, $d['reference'], $d['amount'], $d['discount'], $d['memo'], $d['bankCharge'], $d['bankAmount'], $d['dimension1'], $d['dimension2']);
        return ['id' => $id, 'reference' => $d['reference']];
    }

    public function getJournalEntry(int $id): array
    {
        Kernel::boot();
        $journalResult = \db_query(
            'SELECT * FROM ' . TB_PREF . 'journal WHERE type=' . \db_escape(ST_JOURNAL) . ' AND trans_no=' . \db_escape($id),
            'could not get journal entry'
        );
        $journal = \db_fetch_assoc($journalResult);
        if (!$journal) {
            return [];
        }

        $lines = [];
        $lineResult = \db_query(
            'SELECT * FROM ' . TB_PREF . 'gl_trans WHERE type=' . \db_escape(ST_JOURNAL) . ' AND type_no=' . \db_escape($id) . ' ORDER BY counter',
            'could not get journal lines'
        );
        while ($line = \db_fetch_assoc($lineResult)) {
            $lines[] = $line;
        }

        $journal['lines'] = $lines;
        return $journal;
    }

    /** @param array<string,mixed> $d */
    public function createStockAdjustment(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/includes/ui/items_cart.inc';
        require_once Kernel::faRoot() . '/inventory/includes/db/items_adjust_db.inc';

        if (empty($d['lines']) || !is_array($d['lines'])) {
            throw new InvalidArgumentException('lines must be a non-empty array');
        }

        $cart = new \items_cart(ST_INVADJUST);
        $lineNo = 0;
        foreach ($d['lines'] as $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException('each stock adjustment line must be an object');
            }
            $stockId = (string) ($line['stockId'] ?? '');
            $quantity = (float) ($line['quantity'] ?? 0);
            if ($stockId === '' || $quantity == 0.0) {
                throw new InvalidArgumentException('each stock adjustment line requires stockId and non-zero quantity');
            }
            $cart->add_to_cart($lineNo++, $stockId, $quantity, (float) ($line['standardCost'] ?? 0), $line['description'] ?? null);
        }

        $id = \add_stock_adjustment($cart->line_items, $d['location'], \sql2date($d['date']), $d['reference'], $d['memo']);
        return ['id' => $id, 'reference' => $d['reference']];
    }
}
