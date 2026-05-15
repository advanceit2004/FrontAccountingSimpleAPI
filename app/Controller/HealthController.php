<?php

declare(strict_types=1);

namespace FAAPI\Controller;

use FAAPI\FrontAccounting\Kernel;
use FAAPI\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HealthController
{
    public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return JsonResponse::success($response, [
            'api' => 'frontaccounting-slim4',
            'php' => PHP_VERSION,
            'frontAccounting' => Kernel::version(),
        ]);
    }
}
