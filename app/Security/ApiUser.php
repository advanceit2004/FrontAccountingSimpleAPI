<?php

declare(strict_types=1);

namespace FAAPI\Security;

final class ApiUser
{
    /** @param list<int> $areas */
    public function __construct(
        public readonly int $company,
        public readonly string $username,
        public readonly ?int $userId,
        public readonly array $areas,
        public readonly int $issuedAt,
        public readonly int $expiresAt,
    ) {
    }

    /** @param array<string,mixed> $claims */
    public static function fromClaims(array $claims): self
    {
        return new self(
            (int) $claims['company'],
            (string) $claims['username'],
            isset($claims['userId']) ? (int) $claims['userId'] : null,
            array_map('intval', $claims['areas'] ?? []),
            (int) $claims['iat'],
            (int) $claims['exp'],
        );
    }
}
