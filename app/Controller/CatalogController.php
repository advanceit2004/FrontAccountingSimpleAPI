<?php

declare(strict_types=1);

namespace FAAPI\Controller;

use FAAPI\Http\JsonResponse;
use FAAPI\Service\CatalogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CatalogController
{
    private CatalogService $service;

    public function __construct()
    {
        $this->service = new CatalogService();
    }

    public function companies(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($request, $response, $this->service->companies());
    }

    public function items(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->items($this->bool($q, 'includeInactive'), $this->bool($q, 'fixedAssets')));
    }

    public function customers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($request, $response, $this->service->customers($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function suppliers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($request, $response, $this->service->suppliers($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function currencies(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($request, $response, $this->service->currencies($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function exchangeRates(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->exchangeRates(isset($q['currency']) ? (string) $q['currency'] : null));
    }

    public function bankAccounts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($request, $response, $this->service->bankAccounts($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function glAccounts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->glAccounts($q['from'] ?? null, $q['to'] ?? null, $q['type'] ?? null));
    }

    public function taxTypes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($request, $response, $this->service->taxTypes($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function taxGroups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($request, $response, $this->service->taxGroups($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function locations(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->locations($this->bool($q, 'includeInactive'), $this->bool($q, 'fixedAssets')));
    }


    public function item(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->item((string) $args['stockId']), 'Item was not found');
    }

    public function itemCategory(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->itemCategory((int) $args['id']), 'Item category was not found');
    }

    public function customer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->customer((int) $args['id']), 'Customer was not found');
    }

    public function supplier(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->supplier((int) $args['id']), 'Supplier was not found');
    }

    public function bankAccount(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->bankAccount((int) $args['id']), 'Bank account was not found');
    }

    public function glAccount(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->glAccount((string) $args['code']), 'GL account was not found');
    }

    public function setItemInactive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->inactive($request, $response, 'item', (string) $args['stockId']);
    }

    public function setCustomerInactive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->inactive($request, $response, 'customer', (string) $args['id']);
    }

    public function setSupplierInactive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->inactive($request, $response, 'supplier', (string) $args['id']);
    }

    public function setCategoryInactive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->inactive($request, $response, 'category', (string) $args['id']);
    }

    public function salesOrders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->salesOrders(isset($q['customerId']) ? (int) $q['customerId'] : null));
    }

    public function salesInvoices(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->salesInvoices(isset($q['customerId']) ? (int) $q['customerId'] : null));
    }

    public function purchaseOrders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->purchaseOrders(isset($q['supplierId']) ? (int) $q['supplierId'] : null));
    }


    public function salesDelivery(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->salesDelivery((int) $args['id']), 'Sales delivery was not found');
    }

    public function salesInvoice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->salesInvoice((int) $args['id']), 'Sales invoice was not found');
    }

    public function purchaseReceipt(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->purchaseReceipt((int) $args['id']), 'Purchase receipt was not found');
    }

    public function supplierInvoice(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->single($response, $this->service->supplierInvoice((int) $args['id']), 'Supplier invoice was not found');
    }

    public function customerPayments(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->customerPayments(isset($q['customerId']) ? (int) $q['customerId'] : null));
    }

    public function supplierPayments(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($request, $response, $this->service->supplierPayments(isset($q['supplierId']) ? (int) $q['supplierId'] : null));
    }

    /** @param list<array<string,mixed>> $rows */
    private function list(ServerRequestInterface $request, ResponseInterface $response, array $rows): ResponseInterface
    {
        $query = $request->getQueryParams();
        $total = count($rows);
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = max(1, min(500, (int) ($query['perPage'] ?? $total ?: 50)));
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($rows, $offset, $perPage);
        return JsonResponse::success($response, $paged, [
            'count' => count($paged),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    /** @param array<string,mixed> $row */
    private function single(ResponseInterface $response, array $row, string $message): ResponseInterface
    {
        if ($row === []) {
            return JsonResponse::error($response, 'NOT_FOUND', $message, 404);
        }
        return JsonResponse::success($response, $row);
    }

    private function inactive(ServerRequestInterface $request, ResponseInterface $response, string $resource, string $id): ResponseInterface
    {
        $body = $request->getParsedBody();
        $inactive = is_array($body) ? filter_var($body['inactive'] ?? true, FILTER_VALIDATE_BOOL) : true;
        $this->service->setInactive($resource, $id, $inactive);
        return JsonResponse::success($response, ['id' => $id, 'inactive' => $inactive]);
    }

    /** @param array<string,mixed> $query */
    private function bool(array $query, string $key): bool
    {
        return filter_var($query[$key] ?? false, FILTER_VALIDATE_BOOL);
    }
}
