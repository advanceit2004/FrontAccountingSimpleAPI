<?php

declare(strict_types=1);

namespace FAAPI\Service;

use FAAPI\FrontAccounting\Db;
use FAAPI\FrontAccounting\Kernel;

final class ItemCategoryService
{
    /** @return list<array<string,mixed>> */
    public function list(bool $includeInactive = false, bool $fixedAssets = false): array
    {
        Kernel::boot();
        require_once Kernel::faRoot() . '/inventory/includes/db/items_category_db.inc';

        $result = \get_item_categories($includeInactive, $fixedAssets);
        return Db::rows($result);
    }
}
