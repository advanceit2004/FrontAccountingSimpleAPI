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
    public function createSalesDelivery(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/includes/ui/ui_globals.inc';
        require_once Kernel::faRoot() . '/sales/includes/cart_class.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/sales_order_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/sales_delivery_db.inc';
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';

        $order = \get_sales_order_header($d['orderId'], ST_SALESORDER);
        if (!$order) {
            throw new InvalidArgumentException('unknown sales order id: ' . $d['orderId']);
        }

        $cart = new \Cart(ST_SALESORDER, $d['orderId'], ST_CUSTDELIVERY);
        if (count($cart->line_items) === 0) {
            throw new InvalidArgumentException('sales order has no deliverable lines: ' . $d['orderId']);
        }
        $cart->document_date = \sql2date($d['date']);
        $cart->due_date = \sql2date($d['deliveryDate'] ?: $d['date']);
        $cart->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_CUSTDELIVERY, null, ['date' => $cart->document_date, 'customer' => $cart->customer_id, 'branch' => $cart->Branch]);
        $cart->Comments = $d['memo'];
        if ($d['location'] !== '') {
            $cart->Location = $d['location'];
        }
        if ($d['freightCost'] !== null) {
            $cart->freight_cost = $d['freightCost'];
        }

        $this->applySalesLineQuantities($cart, $d['lines'], 'delivery');
        $id = $cart->write($d['backOrder'] ? 1 : 0);
        if ($id === -1) {
            throw new InvalidArgumentException('sales delivery reference is already in use');
        }
        return ['id' => $id, 'reference' => $cart->reference, 'orderId' => $d['orderId']];
    }

    /** @param array<string,mixed> $d */
    public function createSalesInvoice(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/includes/ui/ui_globals.inc';
        require_once Kernel::faRoot() . '/sales/includes/cart_class.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/sales_order_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/sales_invoice_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/sales_delivery_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/cust_trans_db.inc';
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';

        $cart = new \Cart(ST_CUSTDELIVERY, $d['deliveryId'], ST_SALESINVOICE);
        if (count($cart->line_items) === 0) {
            throw new InvalidArgumentException('delivery has no invoiceable lines: ' . $d['deliveryId']);
        }
        $cart->document_date = \sql2date($d['date']);
        $cart->due_date = \sql2date($d['dueDate'] ?: $d['date']);
        $cart->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_SALESINVOICE, null, ['date' => $cart->document_date, 'customer' => $cart->customer_id, 'branch' => $cart->Branch]);
        $cart->Comments = $d['memo'];
        if ($d['freightCost'] !== null) {
            $cart->freight_cost = $d['freightCost'];
        }

        $this->applySalesLineQuantities($cart, $d['lines'], 'invoice');
        $id = $cart->write(0);
        if ($id === -1) {
            throw new InvalidArgumentException('sales invoice reference is already in use');
        }
        return ['id' => $id, 'reference' => $cart->reference, 'deliveryId' => $d['deliveryId']];
    }

    /** @param array<string,mixed> $d */
    public function createPurchaseReceipt(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/po_class.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/po_db.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/grn_db.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';

        $po = new \purch_order();
        \read_po($d['orderId'], $po, true);
        if (!$po->order_no) {
            throw new InvalidArgumentException('unknown purchase order id: ' . $d['orderId']);
        }
        if (count($po->line_items) === 0) {
            throw new InvalidArgumentException('purchase order has no receivable lines: ' . $d['orderId']);
        }
        $po->orig_order_date = \sql2date($d['date']);
        $po->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_SUPPRECEIVE, null, $po->orig_order_date);
        if ($d['location'] !== '') {
            $po->Location = $d['location'];
        }
        $po->Comments = $d['memo'];

        $this->applyPurchaseReceiptQuantities($po, $d['lines']);
        $id = \add_grn($po);
        return ['id' => $id, 'reference' => $po->reference, 'orderId' => $d['orderId']];
    }

    /**
     * @param object $cart
     * @param mixed $lines
     */
    private function applySalesLineQuantities(object $cart, mixed $lines, string $document): void
    {
        if ($lines === [] || $lines === null) {
            $hasQuantity = false;
            foreach ($cart->line_items as $line) {
                if ((float) $line->qty_dispatched > 0) {
                    $hasQuantity = true;
                }
            }
            if (!$hasQuantity) {
                throw new InvalidArgumentException('no quantities available for sales ' . $document);
            }
            return;
        }
        if (!is_array($lines)) {
            throw new InvalidArgumentException('lines must be an array');
        }

        $requested = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException('each sales ' . $document . ' line must be an object');
            }
            $stockId = (string) ($line['stockId'] ?? '');
            $quantity = (float) ($line['quantity'] ?? 0);
            if ($stockId === '' || $quantity <= 0) {
                throw new InvalidArgumentException('each sales ' . $document . ' line requires stockId and positive quantity');
            }
            $requested[$stockId] = ($requested[$stockId] ?? 0) + $quantity;
        }

        foreach ($cart->line_items as $line) {
            $stockId = (string) $line->stock_id;
            if (!array_key_exists($stockId, $requested)) {
                $line->qty_dispatched = 0;
                continue;
            }
            $quantity = (float) $requested[$stockId];
            if ($quantity > (float) $line->quantity) {
                throw new InvalidArgumentException('requested quantity exceeds available quantity for stockId: ' . $stockId);
            }
            $line->qty_dispatched = $quantity;
            unset($requested[$stockId]);
        }
        if ($requested !== []) {
            throw new InvalidArgumentException('requested stockId is not available on source document: ' . implode(', ', array_keys($requested)));
        }
    }

    /**
     * @param object $po
     * @param mixed $lines
     */
    private function applyPurchaseReceiptQuantities(object $po, mixed $lines): void
    {
        if ($lines === [] || $lines === null) {
            $hasQuantity = false;
            foreach ($po->line_items as $line) {
                $line->receive_qty = max(0, (float) $line->quantity - (float) $line->qty_received);
                if ($line->receive_qty > 0) {
                    $hasQuantity = true;
                }
            }
            if (!$hasQuantity) {
                throw new InvalidArgumentException('no quantities available for purchase receipt');
            }
            return;
        }
        if (!is_array($lines)) {
            throw new InvalidArgumentException('lines must be an array');
        }

        $requested = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException('each purchase receipt line must be an object');
            }
            $stockId = (string) ($line['stockId'] ?? '');
            $quantity = (float) ($line['quantity'] ?? 0);
            if ($stockId === '' || $quantity <= 0) {
                throw new InvalidArgumentException('each purchase receipt line requires stockId and positive quantity');
            }
            $requested[$stockId] = ($requested[$stockId] ?? 0) + $quantity;
        }

        foreach ($po->line_items as $line) {
            $stockId = (string) $line->stock_id;
            if (!array_key_exists($stockId, $requested)) {
                $line->receive_qty = 0;
                continue;
            }
            $quantity = (float) $requested[$stockId];
            $available = max(0, (float) $line->quantity - (float) $line->qty_received);
            if ($quantity > $available) {
                throw new InvalidArgumentException('requested quantity exceeds receivable quantity for stockId: ' . $stockId);
            }
            $line->receive_qty = $quantity;
            unset($requested[$stockId]);
        }
        if ($requested !== []) {
            throw new InvalidArgumentException('requested stockId is not available on purchase order: ' . implode(', ', array_keys($requested)));
        }
    }


    /** @param array<string,mixed> $d */
    public function createSupplierInvoice(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/supp_trans_class.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/invoice_db.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/grn_db.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppliers_db.inc';
        require_once Kernel::faRoot() . '/inventory/includes/db/items_db.inc';

        $grnBatch = \get_grn_batch($d['receiptId']);
        if (!$grnBatch) {
            throw new InvalidArgumentException('unknown purchase receipt id: ' . $d['receiptId']);
        }

        $invoice = new \supp_trans(ST_SUPPINVOICE);
        $invoice->supplier_id = (int) $grnBatch['supplier_id'];
        $invoice->tran_date = \sql2date($d['date']);
        \read_supplier_details_to_trans($invoice, $invoice->supplier_id);
        $invoice->tran_date = \sql2date($d['date']);
        $invoice->due_date = \sql2date($d['dueDate'] ?: $d['date']);
        $invoice->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_SUPPINVOICE, null, $invoice->tran_date);
        $invoice->supp_reference = $d['supplierReference'];
        $invoice->Comments = $d['memo'];
        if ($d['dimension1'] !== 0) {
            $invoice->dimension = $d['dimension1'];
        }
        if ($d['dimension2'] !== 0) {
            $invoice->dimension2 = $d['dimension2'];
        }
        $invoice->ov_amount = 0;
        $invoice->ov_gst = 0;
        $invoice->ov_discount = 0;
        $invoice->ex_rate = $d['exchangeRate'];

        $this->addGrnItemsToSupplierInvoice($invoice, $d['receiptId'], $d['lines']);
        if (!$invoice->is_valid_trans_to_post()) {
            throw new InvalidArgumentException('supplier invoice has no invoiceable lines');
        }

        $id = \add_supp_invoice($invoice);
        return ['id' => $id, 'reference' => $invoice->reference, 'supplierReference' => $invoice->supp_reference, 'receiptId' => $d['receiptId']];
    }

    /**
     * @param object $invoice
     * @param mixed $lines
     */
    private function addGrnItemsToSupplierInvoice(object $invoice, int $receiptId, mixed $lines): void
    {
        $requested = null;
        if ($lines !== [] && $lines !== null) {
            if (!is_array($lines)) {
                throw new InvalidArgumentException('lines must be an array');
            }
            $requested = [];
            foreach ($lines as $line) {
                if (!is_array($line)) {
                    throw new InvalidArgumentException('each supplier invoice line must be an object');
                }
                $stockId = (string) ($line['stockId'] ?? '');
                $quantity = (float) ($line['quantity'] ?? 0);
                if ($stockId === '' || $quantity <= 0) {
                    throw new InvalidArgumentException('each supplier invoice line requires stockId and positive quantity');
                }
                $requested[$stockId] = [
                    'quantity' => ($requested[$stockId]['quantity'] ?? 0) + $quantity,
                    'price' => array_key_exists('price', $line) ? (float) $line['price'] : null,
                ];
            }
        }

        $result = \get_grn_items($receiptId, $invoice->supplier_id, true, false, 0);
        $added = false;
        while ($row = \db_fetch_assoc($result)) {
            $stockId = (string) $row['item_code'];
            $available = (float) $row['qty_recd'] - (float) $row['quantity_inv'];
            if ($available <= 0) {
                continue;
            }
            $quantity = $available;
            $price = (float) ($row['act_price'] ?: $row['unit_price']);
            if ($requested !== null) {
                if (!array_key_exists($stockId, $requested)) {
                    continue;
                }
                $quantity = (float) $requested[$stockId]['quantity'];
                if ($quantity > $available) {
                    throw new InvalidArgumentException('requested quantity exceeds invoiceable quantity for stockId: ' . $stockId);
                }
                if ($requested[$stockId]['price'] !== null) {
                    $price = (float) $requested[$stockId]['price'];
                }
                unset($requested[$stockId]);
            }

            $invoice->add_grn_to_trans(
                (int) $row['id'],
                (int) $row['po_detail_item'],
                $stockId,
                (string) $row['description'],
                (float) $row['qty_recd'],
                (float) $row['quantity_inv'],
                $quantity,
                (float) $row['unit_price'],
                $price,
                (float) $row['std_cost_unit'],
                ''
            );
            $added = true;
        }
        if ($requested !== null && $requested !== []) {
            throw new InvalidArgumentException('requested stockId is not available on purchase receipt: ' . implode(', ', array_keys($requested)));
        }
        if (!$added) {
            throw new InvalidArgumentException('purchase receipt has no invoiceable lines: ' . $receiptId);
        }
    }

    /** @param array<string,mixed> $d */
    public function createCustomerPayment(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/db/payment_db.inc';
        require_once Kernel::faRoot() . '/sales/includes/db/branches_db.inc';
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_bank_accounts.inc';
        $branchId = $d['branchId'];
        if (!$branchId) {
            $defaultBranch = \get_default_branch($d['customerId']);
            if (!$defaultBranch) {
                throw new InvalidArgumentException('customer has no branch: ' . $d['customerId']);
            }
            $branchId = (int) $defaultBranch['branch_code'];
        } elseif (!\get_cust_branch($d['customerId'], $branchId)) {
            throw new InvalidArgumentException('branchId does not belong to customer: ' . $branchId);
        }
        $date = \sql2date($d['date']);
        $id = \write_customer_payment(0, $d['customerId'], $branchId, $d['bankAccount'], $date, $d['reference'], $d['amount'], $d['discount'], $d['memo'], 0, $d['charge'], $d['bankAmount'], $d['dimension1'], $d['dimension2']);
        return ['id' => $id, 'reference' => $d['reference'], 'branchId' => $branchId];
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


    /** @return array<string,mixed> */
    public function getCustomerPaymentAllocations(int $paymentId): array
    {
        Kernel::boot();
        $payment = $this->customerTrans(ST_CUSTPAYMENT, $paymentId);
        if ($payment === []) {
            return [];
        }
        $payment['allocations'] = $this->queryRows(
            'SELECT ca.*, dt.reference, dt.tran_date, dt.due_date, '
            . '(dt.ov_amount+dt.ov_gst+dt.ov_freight+dt.ov_freight_tax+dt.ov_discount) AS total, dt.alloc '
            . 'FROM ' . TB_PREF . 'cust_allocations ca '
            . 'LEFT JOIN ' . TB_PREF . 'debtor_trans dt ON dt.type=ca.trans_type_to AND dt.trans_no=ca.trans_no_to '
            . 'WHERE ca.trans_type_from=' . \db_escape(ST_CUSTPAYMENT) . ' AND ca.trans_no_from=' . \db_escape($paymentId) . ' AND ca.person_id=' . \db_escape((int) $payment['debtor_no']) . ' ORDER BY ca.id',
            'could not get customer payment allocations'
        );
        return $payment;
    }

    /** @param array<string,mixed> $d */
    public function allocateCustomerPayment(int $paymentId, array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/db/custalloc_db.inc';
        $payment = $this->customerTrans(ST_CUSTPAYMENT, $paymentId);
        if ($payment === []) {
            throw new InvalidArgumentException('unknown customer payment id: ' . $paymentId);
        }
        $invoice = $this->customerTrans($d['targetType'], $d['targetId']);
        if ($invoice === []) {
            throw new InvalidArgumentException('unknown customer target transaction: ' . $d['targetId']);
        }
        if ((int) $payment['debtor_no'] !== (int) $invoice['debtor_no']) {
            throw new InvalidArgumentException('customer payment and target transaction belong to different customers');
        }
        $amount = $this->validatedAllocationAmount($d['amount'], $payment, $invoice, 'customer');
        $date = \sql2date($d['date'] ?: ($payment['tran_date'] ?? date('Y-m-d')));
        \begin_transaction();
        \add_cust_allocation($amount, ST_CUSTPAYMENT, $paymentId, $d['targetType'], $d['targetId'], (int) $payment['debtor_no'], $date);
        \update_debtor_trans_allocation(ST_CUSTPAYMENT, $paymentId, (int) $payment['debtor_no']);
        \update_debtor_trans_allocation($d['targetType'], $d['targetId'], (int) $payment['debtor_no']);
        \commit_transaction();
        return ['paymentId' => $paymentId, 'targetType' => $d['targetType'], 'targetId' => $d['targetId'], 'amount' => $amount];
    }

    /** @return array<string,mixed> */
    public function getSupplierPaymentAllocations(int $paymentId): array
    {
        Kernel::boot();
        $payment = $this->supplierTrans(ST_SUPPAYMENT, $paymentId);
        if ($payment === []) {
            return [];
        }
        $payment['allocations'] = $this->queryRows(
            'SELECT sa.*, st.reference, st.supp_reference, st.tran_date, st.due_date, '
            . '(st.ov_amount+st.ov_gst+st.ov_discount) AS total, st.alloc '
            . 'FROM ' . TB_PREF . 'supp_allocations sa '
            . 'LEFT JOIN ' . TB_PREF . 'supp_trans st ON st.type=sa.trans_type_to AND st.trans_no=sa.trans_no_to '
            . 'WHERE sa.trans_type_from=' . \db_escape(ST_SUPPAYMENT) . ' AND sa.trans_no_from=' . \db_escape($paymentId) . ' AND sa.person_id=' . \db_escape((int) $payment['supplier_id']) . ' ORDER BY sa.id',
            'could not get supplier payment allocations'
        );
        return $payment;
    }

    /** @param array<string,mixed> $d */
    public function allocateSupplierPayment(int $paymentId, array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/db/suppalloc_db.inc';
        $payment = $this->supplierTrans(ST_SUPPAYMENT, $paymentId);
        if ($payment === []) {
            throw new InvalidArgumentException('unknown supplier payment id: ' . $paymentId);
        }
        $invoice = $this->supplierTrans($d['targetType'], $d['targetId']);
        if ($invoice === []) {
            throw new InvalidArgumentException('unknown supplier target transaction: ' . $d['targetId']);
        }
        if ((int) $payment['supplier_id'] !== (int) $invoice['supplier_id']) {
            throw new InvalidArgumentException('supplier payment and target transaction belong to different suppliers');
        }
        $amount = $this->validatedAllocationAmount($d['amount'], $payment, $invoice, 'supplier');
        $date = \sql2date($d['date'] ?: ($payment['tran_date'] ?? date('Y-m-d')));
        \begin_transaction();
        \add_supp_allocation($amount, ST_SUPPAYMENT, $paymentId, $d['targetType'], $d['targetId'], (int) $payment['supplier_id'], $date);
        \update_supp_trans_allocation(ST_SUPPAYMENT, $paymentId, (int) $payment['supplier_id']);
        \update_supp_trans_allocation($d['targetType'], $d['targetId'], (int) $payment['supplier_id']);
        \commit_transaction();
        return ['paymentId' => $paymentId, 'targetType' => $d['targetType'], 'targetId' => $d['targetId'], 'amount' => $amount];
    }

    /** @return array<string,mixed> */
    private function customerTrans(int $type, int $id): array
    {
        $rows = $this->queryRows(
            'SELECT *, (ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount) AS Total FROM ' . TB_PREF . 'debtor_trans WHERE type=' . \db_escape($type) . ' AND trans_no=' . \db_escape($id) . ' LIMIT 1',
            'could not get customer transaction'
        );
        return $rows[0] ?? [];
    }

    /** @return array<string,mixed> */
    private function supplierTrans(int $type, int $id): array
    {
        $rows = $this->queryRows(
            'SELECT *, (ov_amount+ov_gst+ov_discount) AS Total FROM ' . TB_PREF . 'supp_trans WHERE type=' . \db_escape($type) . ' AND trans_no=' . \db_escape($id) . ' LIMIT 1',
            'could not get supplier transaction'
        );
        return $rows[0] ?? [];
    }

    /** @param array<string,mixed> $from @param array<string,mixed> $to */
    private function validatedAllocationAmount(float $requested, array $from, array $to, string $context): float
    {
        if ($requested <= 0) {
            throw new InvalidArgumentException('allocation amount must be positive');
        }
        $fromAvailable = max(0.0, abs((float) $from['Total']) - abs((float) $from['alloc']));
        $toAvailable = max(0.0, abs((float) $to['Total']) - abs((float) $to['alloc']));
        $limit = min($fromAvailable, $toAvailable);
        if ($requested > $limit + 0.000001) {
            throw new InvalidArgumentException($context . ' allocation amount exceeds available balance');
        }
        return $requested;
    }

    /** @return list<array<string,mixed>> */
    private function queryRows(string $sql, string $message): array
    {
        $rows = [];
        $result = \db_query($sql, $message);
        while ($row = \db_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
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


    /** @param array<string,mixed> $d @return array<string,mixed> */
    public function createCustomerCreditNote(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/includes/ui/ui_globals.inc';
        require_once Kernel::faRoot() . '/sales/includes/cart_class.inc';
        require_once Kernel::faRoot() . '/sales/includes/sales_db.inc';

        $invoiceId = (int) $d['invoiceId'];
        if (!\get_customer_trans($invoiceId, ST_SALESINVOICE)) {
            throw new InvalidArgumentException('unknown sales invoice: ' . $invoiceId);
        }

        $cart = new \Cart(ST_SALESINVOICE, $invoiceId, ST_CUSTCREDIT);
        $cart->document_date = \sql2date($d['date']);
        $cart->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_CUSTCREDIT, null, [
            'date' => $cart->document_date,
            'customer' => $cart->customer_id,
            'branch' => $cart->Branch,
        ]);
        $cart->Comments = $d['memo'];
        $cart->Location = $d['location'] ?: $cart->Location;
        if ((int) $d['shipVia'] > 0) {
            $cart->ship_via = (int) $d['shipVia'];
        }
        if (array_key_exists('freightCost', $d) && $d['freightCost'] !== null) {
            $cart->freight_cost = (float) $d['freightCost'];
        }

        $this->applyCustomerCreditLines($cart, $d['lines']);
        $hasCredit = false;
        foreach ($cart->line_items as $line) {
            if ((float) $line->qty_dispatched > 0) {
                $hasCredit = true;
                break;
            }
        }
        if (!$hasCredit && (float) $cart->freight_cost <= 0.0) {
            throw new InvalidArgumentException('credit note requires at least one credited line or freight amount');
        }

        $writeOffAccount = (string) ($d['writeOffAccount'] ?? '');
        $creditId = $cart->write($writeOffAccount === '' ? 0 : $writeOffAccount);
        if ($creditId === -1) {
            throw new InvalidArgumentException('reference is already in use');
        }
        if (!$creditId) {
            throw new InvalidArgumentException('credit note could not be created');
        }

        return [
            'id' => $creditId,
            'type' => ST_CUSTCREDIT,
            'invoiceId' => $invoiceId,
            'reference' => $cart->reference,
        ];
    }

    /** @param array<int|string,mixed> $lines */
    private function applyCustomerCreditLines(\Cart $cart, array $lines): void
    {
        if ($lines === []) {
            foreach ($cart->line_items as $line) {
                $remaining = (float) $line->quantity - (float) $line->qty_done;
                $line->qty_dispatched = max(0.0, $remaining);
            }
            return;
        }

        foreach ($cart->line_items as $line) {
            $line->qty_dispatched = 0;
        }

        foreach ($lines as $lineData) {
            if (!is_array($lineData)) {
                throw new InvalidArgumentException('each credit line must be an object');
            }
            $lineId = (int) ($lineData['lineId'] ?? 0);
            $stockId = (string) ($lineData['stockId'] ?? '');
            $quantity = (float) ($lineData['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new InvalidArgumentException('credit line quantity must be greater than zero');
            }
            $matched = null;
            foreach ($cart->line_items as $line) {
                if (($lineId > 0 && (int) $line->id === $lineId) || ($lineId === 0 && $stockId !== '' && $line->stock_id === $stockId)) {
                    $matched = $line;
                    break;
                }
            }
            if (!$matched) {
                throw new InvalidArgumentException('credit line does not match invoice: ' . ($stockId ?: (string) $lineId));
            }
            $remaining = (float) $matched->quantity - (float) $matched->qty_done;
            if ($quantity > $remaining) {
                throw new InvalidArgumentException('credit quantity exceeds available invoice quantity for ' . $matched->stock_id);
            }
            $matched->qty_dispatched = $quantity;
            if (isset($lineData['description'])) {
                $matched->item_description = (string) $lineData['description'];
            }
        }
    }

    /** @param array<string,mixed> $d @return array<string,mixed> */
    public function createSupplierCreditNote(array $d): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/purchasing/includes/supp_trans_class.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/purchasing_db.inc';
        require_once Kernel::faRoot() . '/gl/includes/db/gl_db_accounts.inc';

        $invoiceId = (int) $d['invoiceId'];
        if (!\exists_supp_trans(ST_SUPPINVOICE, $invoiceId)) {
            throw new InvalidArgumentException('unknown supplier invoice: ' . $invoiceId);
        }

        $credit = new \supp_trans(ST_SUPPINVOICE, $invoiceId);
        $credit->src_docs = [$invoiceId => $credit->supp_reference];
        $credit->trans_type = ST_SUPPCREDIT;
        $credit->trans_no = 0;
        $credit->supp_reference = $d['supplierReference'];
        $credit->reference = $d['reference'] ?: $GLOBALS['Refs']->get_next(ST_SUPPCREDIT, null, [
            'supplier' => $credit->supplier_id,
            'date' => \sql2date($d['date']),
        ]);
        $credit->tran_date = \sql2date($d['date']);
        $credit->due_date = \sql2date($d['dueDate'] ?: $d['date']);
        $credit->Comments = $d['memo'];
        $credit->dimension = (int) $d['dimension1'];
        $credit->dimension2 = (int) $d['dimension2'];
        $credit->ex_rate = (float) $d['exchangeRate'];
        $credit->ov_amount = $credit->ov_discount = 0;

        $this->applySupplierCreditLines($credit, $d['lines']);
        foreach ($credit->gl_codes as $glLine) {
            if (!\get_gl_account($glLine->gl_code)) {
                throw new InvalidArgumentException('unknown GL account: ' . $glLine->gl_code);
            }
            $credit->ov_amount += round((float) $glLine->amount, user_price_dec());
        }
        foreach ($credit->grn_items as $grn) {
            $credit->ov_amount += round((float) $grn->this_quantity_inv * (float) $grn->chg_price, user_price_dec());
        }
        if (!$credit->is_valid_trans_to_post()) {
            throw new InvalidArgumentException('supplier credit note requires at least one credited line or GL amount');
        }

        $creditId = \add_supp_invoice($credit);
        if (!$creditId) {
            throw new InvalidArgumentException('supplier credit note could not be created');
        }

        return [
            'id' => $creditId,
            'type' => ST_SUPPCREDIT,
            'invoiceId' => $invoiceId,
            'reference' => $credit->reference,
            'supplierReference' => $credit->supp_reference,
        ];
    }

    /** @param array<int|string,mixed> $lines */
    private function applySupplierCreditLines(\supp_trans $credit, array $lines): void
    {
        if ($lines === []) {
            return;
        }

        $availableByKey = [];
        foreach ($credit->grn_items as $key => $grn) {
            $availableByKey[$key] = abs((float) $grn->this_quantity_inv);
            $grn->this_quantity_inv = 0;
        }

        foreach ($lines as $lineData) {
            if (!is_array($lineData)) {
                throw new InvalidArgumentException('each supplier credit line must be an object');
            }
            if (isset($lineData['account'])) {
                $account = (string) $lineData['account'];
                $amount = (float) ($lineData['amount'] ?? 0);
                if ($account === '' || $amount <= 0) {
                    throw new InvalidArgumentException('GL credit lines require account and positive amount');
                }
                $accountInfo = \get_gl_account($account);
                if (!$accountInfo) {
                    throw new InvalidArgumentException('unknown GL account: ' . $account);
                }
                $credit->add_gl_codes_to_trans($account, $accountInfo['account_name'], (int) ($lineData['dimension1'] ?? 0), (int) ($lineData['dimension2'] ?? 0), $amount, (string) ($lineData['memo'] ?? ''));
                continue;
            }

            $grnItemId = (int) ($lineData['grnItemId'] ?? 0);
            $stockId = (string) ($lineData['stockId'] ?? '');
            $quantity = (float) ($lineData['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new InvalidArgumentException('supplier credit line quantity must be greater than zero');
            }
            $matched = null;
            $matchedKey = null;
            foreach ($credit->grn_items as $key => $grn) {
                if (($grnItemId > 0 && (int) $grn->id === $grnItemId) || ($grnItemId === 0 && $stockId !== '' && $grn->item_code === $stockId)) {
                    $matched = $grn;
                    $matchedKey = $key;
                    break;
                }
            }
            if (!$matched || $matchedKey === null) {
                throw new InvalidArgumentException('supplier credit line does not match invoice: ' . ($stockId ?: (string) $grnItemId));
            }
            $available = $availableByKey[$matchedKey] ?? 0.0;
            if ($quantity > $available) {
                throw new InvalidArgumentException('supplier credit quantity exceeds invoice quantity for ' . $matched->item_code);
            }
            $matched->this_quantity_inv = $quantity;
            if (isset($lineData['price'])) {
                $matched->chg_price = (float) $lineData['price'];
            }
        }
    }

    /** @return array<string,mixed> */
    public function voidDocument(int $type, int $id, string $date, string $memo): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/sales/includes/sales_db.inc';
        require_once Kernel::faRoot() . '/purchasing/includes/purchasing_db.inc';
        require_once Kernel::faRoot() . '/gl/includes/db/gl_journal.inc';
        require_once Kernel::faRoot() . '/admin/db/voiding_db.inc';

        $this->assertVoidableSourceExists($type, $id);
        if (\get_voided_entry($type, $id)) {
            throw new InvalidArgumentException('transaction is already voided');
        }

        $voidDate = \sql2date($date);
        $message = \void_transaction($type, $id, $voidDate, $memo);
        if ($message) {
            throw new InvalidArgumentException((string) $message);
        }

        $voided = \get_voided_entry($type, $id);
        return [
            'type' => $type,
            'id' => $id,
            'voided' => $voided ? true : false,
            'voidedEntry' => $voided ?: null,
        ];
    }

    private function assertVoidableSourceExists(int $type, int $id): void
    {
        $map = [
            ST_SALESINVOICE => ['table' => 'debtor_trans', 'where' => 'type=' . \db_escape(ST_SALESINVOICE) . ' AND trans_no=' . \db_escape($id), 'name' => 'sales invoice'],
            ST_CUSTDELIVERY => ['table' => 'debtor_trans', 'where' => 'type=' . \db_escape(ST_CUSTDELIVERY) . ' AND trans_no=' . \db_escape($id), 'name' => 'sales delivery'],
            ST_CUSTPAYMENT => ['table' => 'debtor_trans', 'where' => 'type=' . \db_escape(ST_CUSTPAYMENT) . ' AND trans_no=' . \db_escape($id), 'name' => 'customer payment'],
            ST_CUSTCREDIT => ['table' => 'debtor_trans', 'where' => 'type=' . \db_escape(ST_CUSTCREDIT) . ' AND trans_no=' . \db_escape($id), 'name' => 'customer credit note'],
            ST_SUPPRECEIVE => ['table' => 'grn_batch', 'where' => 'id=' . \db_escape($id), 'name' => 'purchase receipt'],
            ST_SUPPINVOICE => ['table' => 'supp_trans', 'where' => 'type=' . \db_escape(ST_SUPPINVOICE) . ' AND trans_no=' . \db_escape($id), 'name' => 'supplier invoice'],
            ST_SUPPCREDIT => ['table' => 'supp_trans', 'where' => 'type=' . \db_escape(ST_SUPPCREDIT) . ' AND trans_no=' . \db_escape($id), 'name' => 'supplier credit note'],
            ST_SUPPAYMENT => ['table' => 'supp_trans', 'where' => 'type=' . \db_escape(ST_SUPPAYMENT) . ' AND trans_no=' . \db_escape($id), 'name' => 'supplier payment'],
            ST_JOURNAL => ['table' => 'journal', 'where' => 'type=' . \db_escape(ST_JOURNAL) . ' AND trans_no=' . \db_escape($id), 'name' => 'journal entry'],
            ST_INVADJUST => ['table' => 'stock_moves', 'where' => 'type=' . \db_escape(ST_INVADJUST) . ' AND trans_no=' . \db_escape($id), 'name' => 'stock adjustment'],
        ];
        if (!isset($map[$type])) {
            throw new InvalidArgumentException('unsupported void transaction type: ' . $type);
        }
        $m = $map[$type];
        $sql = 'SELECT 1 FROM ' . TB_PREF . $m['table'] . ' WHERE ' . $m['where'] . ' LIMIT 1';
        $result = \db_query($sql, 'could not validate ' . $m['name']);
        if (!\db_fetch($result)) {
            throw new InvalidArgumentException('unknown ' . $m['name'] . ': ' . $id);
        }
    }

}
