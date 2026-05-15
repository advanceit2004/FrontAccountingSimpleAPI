<?php

declare(strict_types=1);

namespace FAAPI\FrontAccounting;

final class Authenticator
{
    /** @return array{company:int,username:string,userId:?int,areas:list<int>}|null */
    public function login(int $company, string $username, string $password): ?array
    {
        Kernel::boot();

        if (!isset($GLOBALS['db_connections'][$company])) {
            return null;
        }

        $user = $_SESSION['wa_current_user'];
        $success = $user->login($company, $username, $password);
        if (!$success) {
            return null;
        }

        return [
            'company' => $company,
            'username' => $user->username ?: $username,
            'userId' => isset($user->user) ? (int) $user->user : null,
            'areas' => array_values(array_map('intval', $user->role_set ?: [])),
        ];
    }
}
