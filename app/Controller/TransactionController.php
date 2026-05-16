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




    public function createSalesOrder(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'customerId' => RequestData::int($b, 'customerId'),
                'branchId' => RequestData::int($b, 'branchId', 0),
                'date' => RequestData::string($b, 'date'),
                'deliveryDate' => RequestData::string($b, 'deliveryDate', ''),
                'reference' => RequestData::string($b, 'reference', ''),
                'customerReference' => RequestData::string($b, 'customerReference', ''),
                'memo' => RequestData::string($b, 'memo', ''),
                'location' => RequestData::string($b, 'location', ''),
                'shipVia' => RequestData::int($b, 'shipVia', 0),
                'deliverTo' => RequestData::string($b, 'deliverTo', ''),
                'deliveryAddress' => RequestData::string($b, 'deliveryAddress', ''),
                'phone' => RequestData::string($b, 'phone', ''),
                'freightCost' => RequestData::float($b, 'freightCost', 0),
                'dimension1' => RequestData::int($b, 'dimension1', 0),
                'dimension2' => RequestData::int($b, 'dimension2', 0),
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createSalesOrder($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function createPurchaseOrder(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'supplierId' => RequestData::int($b, 'supplierId'),
                'date' => RequestData::string($b, 'date'),
                'deliveryDate' => RequestData::string($b, 'deliveryDate', ''),
                'reference' => RequestData::string($b, 'reference', ''),
                'supplierReference' => RequestData::string($b, 'supplierReference', ''),
                'memo' => RequestData::string($b, 'memo', ''),
                'location' => RequestData::string($b, 'location', 'DEF'),
                'deliveryAddress' => RequestData::string($b, 'deliveryAddress', ''),
                'dimension1' => RequestData::int($b, 'dimension1', 0),
                'dimension2' => RequestData::int($b, 'dimension2', 0),
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createPurchaseOrder($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }


    public function createSalesDelivery(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'orderId' => RequestData::int($b, 'orderId'),
                'date' => RequestData::string($b, 'date'),
                'deliveryDate' => RequestData::string($b, 'deliveryDate', ''),
                'reference' => RequestData::string($b, 'reference', ''),
                'memo' => RequestData::string($b, 'memo', ''),
                'location' => RequestData::string($b, 'location', ''),
                'freightCost' => array_key_exists('freightCost', $b) ? RequestData::float($b, 'freightCost') : null,
                'backOrder' => filter_var($b['backOrder'] ?? true, FILTER_VALIDATE_BOOL),
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createSalesDelivery($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function createSalesInvoice(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'deliveryId' => RequestData::int($b, 'deliveryId'),
                'date' => RequestData::string($b, 'date'),
                'dueDate' => RequestData::string($b, 'dueDate', ''),
                'reference' => RequestData::string($b, 'reference', ''),
                'memo' => RequestData::string($b, 'memo', ''),
                'freightCost' => array_key_exists('freightCost', $b) ? RequestData::float($b, 'freightCost') : null,
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createSalesInvoice($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function createPurchaseReceipt(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'orderId' => RequestData::int($b, 'orderId'),
                'date' => RequestData::string($b, 'date'),
                'reference' => RequestData::string($b, 'reference', ''),
                'memo' => RequestData::string($b, 'memo', ''),
                'location' => RequestData::string($b, 'location', ''),
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createPurchaseReceipt($data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }


    public function createSupplierInvoice(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'receiptId' => RequestData::int($b, 'receiptId'),
                'date' => RequestData::string($b, 'date'),
                'dueDate' => RequestData::string($b, 'dueDate', ''),
                'reference' => RequestData::string($b, 'reference', ''),
                'supplierReference' => RequestData::string($b, 'supplierReference'),
                'memo' => RequestData::string($b, 'memo', ''),
                'exchangeRate' => RequestData::float($b, 'exchangeRate', 1),
                'dimension1' => RequestData::int($b, 'dimension1', 0),
                'dimension2' => RequestData::int($b, 'dimension2', 0),
                'lines' => $b['lines'] ?? [],
            ];
            return JsonResponse::success($response, $this->service->createSupplierInvoice($data), [], 201);
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


    public function getCustomerPaymentAllocations(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $payment = $this->service->getCustomerPaymentAllocations((int) $args['id']);
        if ($payment === []) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Customer payment was not found', 404);
        }
        return JsonResponse::success($response, $payment);
    }

    public function allocateCustomerPayment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'targetType' => RequestData::int($b, 'targetType', ST_SALESINVOICE),
                'targetId' => RequestData::int($b, 'targetId'),
                'amount' => RequestData::float($b, 'amount'),
                'date' => RequestData::string($b, 'date', ''),
            ];
            return JsonResponse::success($response, $this->service->allocateCustomerPayment((int) $args['id'], $data), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function getSupplierPaymentAllocations(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $payment = $this->service->getSupplierPaymentAllocations((int) $args['id']);
        if ($payment === []) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Supplier payment was not found', 404);
        }
        return JsonResponse::success($response, $payment);
    }

    public function allocateSupplierPayment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $data = [
                'targetType' => RequestData::int($b, 'targetType', ST_SUPPINVOICE),
                'targetId' => RequestData::int($b, 'targetId'),
                'amount' => RequestData::float($b, 'amount'),
                'date' => RequestData::string($b, 'date', ''),
            ];
            return JsonResponse::success($response, $this->service->allocateSupplierPayment((int) $args['id'], $data), [], 201);
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

    public function voidSalesInvoice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_SALESINVOICE, (int) $args['id']);
    }

    public function voidSalesDelivery(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_CUSTDELIVERY, (int) $args['id']);
    }

    public function voidCustomerPayment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_CUSTPAYMENT, (int) $args['id']);
    }

    public function voidPurchaseReceipt(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_SUPPRECEIVE, (int) $args['id']);
    }

    public function voidSupplierInvoice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_SUPPINVOICE, (int) $args['id']);
    }

    public function voidSupplierPayment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_SUPPAYMENT, (int) $args['id']);
    }

    public function voidJournalEntry(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_JOURNAL, (int) $args['id']);
    }

    public function voidStockAdjustment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->voidDocument($request, $response, ST_INVADJUST, (int) $args['id']);
    }

    private function voidDocument(ServerRequestInterface $request, ResponseInterface $response, int $type, int $id): ResponseInterface
    {
        try {
            $b = RequestData::body($request);
            $date = RequestData::string($b, 'date', date('Y-m-d'));
            $memo = RequestData::string($b, 'memo', 'Voided via API');
            return JsonResponse::success($response, $this->service->voidDocument($type, $id, $date, $memo));
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

}
