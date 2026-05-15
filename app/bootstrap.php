<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use FAAPI\Http\JsonBodyMiddleware;
use FAAPI\Http\JsonResponse;
use FAAPI\FrontAccounting\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->setBasePath(getenv('FA_API_BASE_PATH') ?: '/modules/api/public/index.php');

$app->addRoutingMiddleware();
$app->add(new JsonBodyMiddleware());

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(function ($request, Throwable $exception, bool $displayErrorDetails) use ($app) {
    $payload = [
        'success' => false,
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => $displayErrorDetails ? $exception->getMessage() : 'Internal server error',
        ],
    ];

    if ($displayErrorDetails) {
        $payload['error']['type'] = get_class($exception);
        $payload['error']['file'] = $exception->getFile();
        $payload['error']['line'] = $exception->getLine();
    }

    return JsonResponse::create($app->getResponseFactory()->createResponse(500), $payload, 500);
});

Kernel::configure(dirname(__DIR__));

(require __DIR__ . '/routes.php')($app);

return $app;
