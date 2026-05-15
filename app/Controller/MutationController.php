<?php

declare(strict_types=1);

namespace FAAPI\Controller;

use FAAPI\Http\JsonResponse;
use FAAPI\Http\RequestData;
use FAAPI\Service\MutationService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MutationController
{
    private MutationService $service;

    public function __construct()
    {
        $this->service = new MutationService();
    }

    public function createItemCategory(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->createItemCategory($this->itemCategoryData($request)), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function updateItemCategory(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->updateItemCategory((int) $args['id'], $this->itemCategoryData($request)));
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function createItem(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->createItem($this->itemData($request)), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function updateItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->updateItem((string) $args['stockId'], $this->itemData($request, false)));
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function createCustomer(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->createCustomer($this->customerData($request)), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function updateCustomer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->updateCustomer((int) $args['id'], $this->customerData($request)));
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function createSupplier(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->createSupplier($this->supplierData($request)), [], 201);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function updateSupplier(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            return JsonResponse::success($response, $this->service->updateSupplier((int) $args['id'], $this->supplierData($request)));
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    /** @return array<string,mixed> */
    private function itemCategoryData(ServerRequestInterface $request): array
    {
        $b = RequestData::body($request);
        return [
            'description' => RequestData::string($b, 'description'),
            'taxTypeId' => RequestData::int($b, 'taxTypeId'),
            'salesAccount' => RequestData::string($b, 'salesAccount'),
            'cogsAccount' => RequestData::string($b, 'cogsAccount'),
            'inventoryAccount' => RequestData::string($b, 'inventoryAccount'),
            'adjustmentAccount' => RequestData::string($b, 'adjustmentAccount'),
            'wipAccount' => RequestData::string($b, 'wipAccount'),
            'units' => RequestData::string($b, 'units', 'each'),
            'mbFlag' => RequestData::string($b, 'mbFlag', 'B'),
            'dimension1' => RequestData::int($b, 'dimension1', 0),
            'dimension2' => RequestData::int($b, 'dimension2', 0),
            'noSale' => RequestData::int($b, 'noSale', 0),
            'noPurchase' => RequestData::int($b, 'noPurchase', 0),
        ];
    }

    /** @return array<string,mixed> */
    private function itemData(ServerRequestInterface $request, bool $requireStockId = true): array
    {
        $b = RequestData::body($request);
        $stockId = $requireStockId ? RequestData::string($b, 'stockId') : '';
        return [
            'stockId' => $stockId,
            'description' => RequestData::string($b, 'description'),
            'longDescription' => RequestData::string($b, 'longDescription', RequestData::string($b, 'description')),
            'categoryId' => RequestData::int($b, 'categoryId'),
            'taxTypeId' => RequestData::int($b, 'taxTypeId'),
            'units' => RequestData::string($b, 'units', 'each'),
            'mbFlag' => RequestData::string($b, 'mbFlag', 'B'),
            'salesAccount' => RequestData::string($b, 'salesAccount'),
            'inventoryAccount' => RequestData::string($b, 'inventoryAccount'),
            'cogsAccount' => RequestData::string($b, 'cogsAccount'),
            'adjustmentAccount' => RequestData::string($b, 'adjustmentAccount'),
            'wipAccount' => RequestData::string($b, 'wipAccount'),
            'dimension1' => RequestData::int($b, 'dimension1', 0),
            'dimension2' => RequestData::int($b, 'dimension2', 0),
            'noSale' => RequestData::int($b, 'noSale', 0),
            'editable' => RequestData::int($b, 'editable', 1),
            'noPurchase' => RequestData::int($b, 'noPurchase', 0),
        ];
    }

    /** @return array<string,mixed> */
    private function customerData(ServerRequestInterface $request): array
    {
        $b = RequestData::body($request);
        return [
            'name' => RequestData::string($b, 'name'),
            'reference' => RequestData::string($b, 'reference'),
            'address' => RequestData::string($b, 'address', ''),
            'taxId' => RequestData::string($b, 'taxId', ''),
            'currency' => RequestData::string($b, 'currency'),
            'dimension1' => RequestData::int($b, 'dimension1', 0),
            'dimension2' => RequestData::int($b, 'dimension2', 0),
            'creditStatus' => RequestData::int($b, 'creditStatus', 1),
            'paymentTerms' => RequestData::int($b, 'paymentTerms', 4),
            'discount' => RequestData::float($b, 'discount', 0),
            'paymentDiscount' => RequestData::float($b, 'paymentDiscount', 0),
            'creditLimit' => RequestData::float($b, 'creditLimit', 0),
            'salesType' => RequestData::int($b, 'salesType', 1),
            'notes' => RequestData::string($b, 'notes', ''),
            'createDefaultBranch' => filter_var($b['createDefaultBranch'] ?? true, FILTER_VALIDATE_BOOL),
            'branchName' => RequestData::string($b, 'branchName', ''),
            'branchReference' => RequestData::string($b, 'branchReference', ''),
            'branchAddress' => RequestData::string($b, 'branchAddress', ''),
            'branchPostAddress' => RequestData::string($b, 'branchPostAddress', ''),
            'salesman' => RequestData::int($b, 'salesman', 1),
            'area' => RequestData::int($b, 'area', 1),
            'taxGroupId' => RequestData::int($b, 'taxGroupId', 1),
            'branchSalesAccount' => RequestData::string($b, 'branchSalesAccount', '4050'),
            'branchSalesDiscountAccount' => RequestData::string($b, 'branchSalesDiscountAccount', '4060'),
            'branchReceivablesAccount' => RequestData::string($b, 'branchReceivablesAccount', '1800'),
            'branchPaymentDiscountAccount' => RequestData::string($b, 'branchPaymentDiscountAccount', '5060'),
            'defaultLocation' => RequestData::string($b, 'defaultLocation', 'MEL'),
            'defaultShipVia' => RequestData::int($b, 'defaultShipVia', 1),
        ];
    }

    /** @return array<string,mixed> */
    private function supplierData(ServerRequestInterface $request): array
    {
        $b = RequestData::body($request);
        return [
            'name' => RequestData::string($b, 'name'),
            'reference' => RequestData::string($b, 'reference'),
            'address' => RequestData::string($b, 'address', ''),
            'supplierAddress' => RequestData::string($b, 'supplierAddress', ''),
            'taxId' => RequestData::string($b, 'taxId', ''),
            'website' => RequestData::string($b, 'website', ''),
            'accountNumber' => RequestData::string($b, 'accountNumber', ''),
            'bankAccount' => RequestData::string($b, 'bankAccount', ''),
            'creditLimit' => RequestData::float($b, 'creditLimit', 0),
            'dimension1' => RequestData::int($b, 'dimension1', 0),
            'dimension2' => RequestData::int($b, 'dimension2', 0),
            'currency' => RequestData::string($b, 'currency'),
            'paymentTerms' => RequestData::int($b, 'paymentTerms', 4),
            'payableAccount' => RequestData::string($b, 'payableAccount'),
            'purchaseAccount' => RequestData::string($b, 'purchaseAccount'),
            'paymentDiscountAccount' => RequestData::string($b, 'paymentDiscountAccount'),
            'notes' => RequestData::string($b, 'notes', ''),
            'taxGroupId' => RequestData::int($b, 'taxGroupId', 1),
            'taxIncluded' => RequestData::int($b, 'taxIncluded', 0),
        ];
    }
}
