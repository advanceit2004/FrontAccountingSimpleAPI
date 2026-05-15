<?php

declare(strict_types=1);

namespace FAAPI\Controller;

use FAAPI\Http\JsonResponse;
use FAAPI\Service\ItemCategoryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ItemCategoryController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $includeInactive = filter_var($query['includeInactive'] ?? false, FILTER_VALIDATE_BOOL);
        $fixedAssets = filter_var($query['fixedAssets'] ?? false, FILTER_VALIDATE_BOOL);

        $categories = (new ItemCategoryService())->list($includeInactive, $fixedAssets);

        return JsonResponse::success($response, $categories, [
            'count' => count($categories),
        ]);
    }
}
