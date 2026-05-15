<?php

declare(strict_types=1);

namespace FAAPI\Security;

use FAAPI\FrontAccounting\Kernel;

final class TokenService
{
    public const TTL_SECONDS = 3600;

    /** @param list<int> $areas */
    public static function issue(int $company, string $username, ?int $userId, array $areas): string
    {
        $now = time();
        $claims = [
            'iss' => 'frontaccounting-api',
            'aud' => 'frontaccounting-api',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + self::TTL_SECONDS,
            'company' => $company,
            'username' => $username,
            'userId' => $userId,
            'areas' => array_values(array_map('intval', $areas)),
        ];

        return self::encode($claims);
    }

    /** @return array<string,mixed>|null */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $expected = self::base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, self::secret(), true));
        if (!hash_equals($expected, $encodedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (!is_array($payload)) {
            return null;
        }

        $now = time();
        if (($payload['nbf'] ?? 0) > $now || ($payload['exp'] ?? 0) < $now) {
            return null;
        }

        if (($payload['iss'] ?? null) !== 'frontaccounting-api' || ($payload['aud'] ?? null) !== 'frontaccounting-api') {
            return null;
        }

        return $payload;
    }

    /** @param array<string,mixed> $claims */
    private static function encode(array $claims): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $encodedHeader = self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = self::base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, self::secret(), true));

        return $encodedHeader . '.' . $encodedPayload . '.' . $signature;
    }

    private static function secret(): string
    {
        $env = getenv('FA_API_JWT_SECRET');
        if (is_string($env) && strlen($env) >= 32) {
            return $env;
        }

        return Kernel::defaultSecret();
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }
}
