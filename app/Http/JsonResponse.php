<?php

declare(strict_types=1);

namespace FAAPI\Http;

use Psr\Http\Message\ResponseInterface;

final class JsonResponse
{
    public static function create(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{"success":false,"error":{"code":"JSON_ENCODE_ERROR","message":"Unable to encode response"}}';
            $status = 500;
        }

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    public static function success(ResponseInterface $response, mixed $data = null, array $meta = [], int $status = 200): ResponseInterface
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return self::create($response, $payload, $status);
    }

    public static function error(ResponseInterface $response, string $code, string $message, int $status = 400, array $details = []): ResponseInterface
    {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== []) {
            $payload['error']['details'] = $details;
        }

        return self::create($response, $payload, $status);
    }
}
