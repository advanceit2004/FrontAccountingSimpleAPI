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
        return $this->list($response, $this->service->companies());
    }

    public function items(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($response, $this->service->items($this->bool($q, 'includeInactive'), $this->bool($q, 'fixedAssets')));
    }

    public function customers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($response, $this->service->customers($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function suppliers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($response, $this->service->suppliers($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function currencies(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($response, $this->service->currencies($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function exchangeRates(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($response, $this->service->exchangeRates(isset($q['currency']) ? (string) $q['currency'] : null));
    }

    public function bankAccounts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($response, $this->service->bankAccounts($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function glAccounts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($response, $this->service->glAccounts($q['from'] ?? null, $q['to'] ?? null, $q['type'] ?? null));
    }

    public function taxTypes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($response, $this->service->taxTypes($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function taxGroups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->list($response, $this->service->taxGroups($this->bool($request->getQueryParams(), 'includeInactive')));
    }

    public function locations(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        return $this->list($response, $this->service->locations($this->bool($q, 'includeInactive'), $this->bool($q, 'fixedAssets')));
    }

    /** @param list<array<string,mixed>> $rows */
    private function list(ResponseInterface $response, array $rows): ResponseInterface
    {
        return JsonResponse::success($response, $rows, ['count' => count($rows)]);
    }

    /** @param array<string,mixed> $query */
    private function bool(array $query, string $key): bool
    {
        return filter_var($query[$key] ?? false, FILTER_VALIDATE_BOOL);
    }
}
