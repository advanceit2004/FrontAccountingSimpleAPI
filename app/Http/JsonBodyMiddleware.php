<?php

declare(strict_types=1);

namespace FAAPI\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class JsonBodyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        if (str_contains($contentType, 'application/json')) {
            $body = (string) $request->getBody();
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    return JsonResponse::error(new Response(), 'INVALID_JSON', 'Request body must be a valid JSON object', 400, [
                        'jsonError' => json_last_error_msg(),
                    ]);
                }
                $request = $request->withParsedBody($decoded);
            }
        }

        return $handler->handle($request);
    }
}
