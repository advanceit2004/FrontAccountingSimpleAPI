<?php

declare(strict_types=1);

use Slim\App;
use FAAPI\Controller\AuthController;
use FAAPI\Controller\HealthController;
use FAAPI\Controller\ItemCategoryController;
use FAAPI\Http\BearerTokenMiddleware;

return function (App $app): void {
    $app->get('/v1/health', [HealthController::class, 'show']);
    $app->post('/v1/auth/login', [AuthController::class, 'login']);

    $app->group('/v1', function ($group): void {
        $group->get('/items/categories', [ItemCategoryController::class, 'index']);
    })->add(new BearerTokenMiddleware(['SA_ITEMCATEGORY', 'SA_ITEMSSTATVIEW', 'SA_ITEMSTRANSVIEW']));
};
