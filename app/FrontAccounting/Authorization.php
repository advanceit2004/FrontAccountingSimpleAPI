<?php

declare(strict_types=1);

namespace FAAPI\FrontAccounting;

use FAAPI\Security\ApiUser;

final class Authorization
{
    /** @param list<string> $requiredAreas */
    public static function allows(ApiUser $user, array $requiredAreas): bool
    {
        Kernel::boot();
        if ($requiredAreas === []) {
            return true;
        }

        foreach ($requiredAreas as $area) {
            if ($area === 'SA_OPEN') {
                return true;
            }
            $code = $GLOBALS['security_areas'][$area][0] ?? null;
            if ($code !== null && in_array((int) $code, $user->areas, true)) {
                return true;
            }
        }

        return false;
    }
}
