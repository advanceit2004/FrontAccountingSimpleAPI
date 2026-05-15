<?php

declare(strict_types=1);

namespace FAAPI\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class RequestData
{
    /** @return array<string,mixed> */
    public static function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            throw new InvalidArgumentException('JSON request body is required');
        }
        return $body;
    }

    /** @param array<string,mixed> $data */
    public static function string(array $data, string $key, ?string $default = null): string
    {
        if (!array_key_exists($key, $data) || $data[$key] === '') {
            if ($default !== null) {
                return $default;
            }
            throw new InvalidArgumentException($key . ' is required');
        }
        return (string) $data[$key];
    }

    /** @param array<string,mixed> $data */
    public static function int(array $data, string $key, ?int $default = null): int
    {
        if (!array_key_exists($key, $data) || $data[$key] === '') {
            if ($default !== null) {
                return $default;
            }
            throw new InvalidArgumentException($key . ' is required');
        }
        return (int) $data[$key];
    }

    /** @param array<string,mixed> $data */
    public static function float(array $data, string $key, ?float $default = null): float
    {
        if (!array_key_exists($key, $data) || $data[$key] === '') {
            if ($default !== null) {
                return $default;
            }
            throw new InvalidArgumentException($key . ' is required');
        }
        return (float) $data[$key];
    }
}
