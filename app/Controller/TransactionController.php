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



    public function createCustomerPayment(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'customerId' => RequestData::int($b, 'customerId'),
                'branchId' => RequestData::int($b, 'branchId', 0),
                'bankAccount' => RequestData::int($b, 'bankAccount'),
                'date' => RequestData::string($b, 'date'),
                'reference' => RequestData::string($b, 'reference'),
                'amount' => RequestData::float($b, 'amount'),
                'discount' => RequestData::float($b, 'discount', 0),
                'memo' => RequestData::string($b, 'memo', ''),
                'charge' => RequestData::float($b, 'charge', 0),
                'bankAmount' => RequestData::float($b, 'bankAmount', 0),
                'dimension1' => RequestData::int($b, 'dimension1', 0),
                'dimension2' => RequestData::int($b, 'dimension2', 0),
            ];
            return JsonResponse::success($response, $this->service->createCustomerPayment($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function createSupplierPayment(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'supplierId' => RequestData::int($b, 'supplierId'),
                'bankAccount' => RequestData::int($b, 'bankAccount'),
                'date' => RequestData::string($b, 'date'),
                'reference' => RequestData::string($b, 'reference'),
                'amount' => RequestData::float($b, 'amount'),
                'discount' => RequestData::float($b, 'discount', 0),
                'memo' => RequestData::string($b, 'memo', ''),
                'bankCharge' => RequestData::float($b, 'bankCharge', 0),
                'bankAmount' => RequestData::float($b, 'bankAmount', 0),
                'dimension1' => RequestData::int($b, 'dimension1', 0),
                'dimension2' => RequestData::int($b, 'dimension2', 0),
            ];
            return JsonResponse::success($response, $this->service->createSupplierPayment($data), [], 201);
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
