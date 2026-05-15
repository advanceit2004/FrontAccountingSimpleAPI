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

        $cart = new \items_cart(ST_JOURNAL);
        $cart->tran_date = $d['date'];
        $cart->doc_date = $d['documentDate'] ?: $d['date'];
        $cart->event_date = $d['eventDate'] ?: $d['date'];
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
            $total += $amount;
            $cart->add_gl_item($account, (int) ($line['dimension1'] ?? 0), (int) ($line['dimension2'] ?? 0), $amount, (string) ($line['memo'] ?? ''));
        }

        if (round($total, 2) !== 0.0) {
            throw new InvalidArgumentException('journal lines must balance to zero');
        }

        $id = \write_journal_entries($cart);
        return ['id' => $id, 'reference' => $cart->reference];
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

        $id = \add_stock_adjustment($cart->line_items, $d['location'], $d['date'], $d['reference'], $d['memo']);
        return ['id' => $id, 'reference' => $d['reference']];
    }
}
