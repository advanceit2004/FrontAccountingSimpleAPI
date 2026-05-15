<?php

declare(strict_types=1);

namespace FAAPI\Controller;

use FAAPI\Http\JsonResponse;
use FAAPI\Http\RequestData;
use FAAPI\Service\TransactionService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TransactionController
{
    private TransactionService $service;

    public function __construct()
    {
        $this->service = new TransactionService();
    }

    public function createJournalEntry(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'date' => RequestData::string($b, 'date'),
                'documentDate' => RequestData::string($b, 'documentDate', ''),
                'eventDate' => RequestData::string($b, 'eventDate', ''),
                'sourceRef' => RequestData::string($b, 'sourceRef', ''),
                'memo' => RequestData::string($b, 'memo', ''),
                'currency' => RequestData::string($b, 'currency', ''),
                'rate' => RequestData::float($b, 'rate', 1),
                'reference' => RequestData::string($b, 'reference', ''),
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createJournalEntry($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }


    public function getJournalEntry(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $entry = $this->service->getJournalEntry((int) $args['id']);
        if ($entry === []) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Journal entry was not found', 404);
        }
        return JsonResponse::success($response, $entry);
    }

    public function createStockAdjustment(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'location' => RequestData::string($b, 'location'),
                'date' => RequestData::string($b, 'date'),
                'reference' => RequestData::string($b, 'reference'),
                'memo' => RequestData::string($b, 'memo', ''),
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createStockAdjustment($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
