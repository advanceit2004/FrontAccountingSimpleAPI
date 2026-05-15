<?php

declare(strict_types=1);

namespace FAAPI\Controller;

use FAAPI\FrontAccounting\Authenticator;
use FAAPI\Http\JsonResponse;
use FAAPI\Security\TokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'JSON request body is required', 422);
        }

        foreach (['company', 'username', 'password'] as $field) {
            if (!array_key_exists($field, $body) || $body[$field] === '') {
                return JsonResponse::error($response, 'VALIDATION_ERROR', $field . ' is required', 422);
            }
        }

        $company = (int) $body['company'];
        $username = (string) $body['username'];
        $password = (string) $body['password'];

        $identity = (new Authenticator())->login($company, $username, $password);
        if ($identity === null) {
            return JsonResponse::error($response, 'BAD_LOGIN', 'Invalid company, username, or password', 401);
        }

        $token = TokenService::issue($identity['company'], $identity['username'], $identity['userId'], $identity['areas']);

        return JsonResponse::success($response, [
            'accessToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => TokenService::TTL_SECONDS,
            'company' => $identity['company'],
            'username' => $identity['username'],
        ]);
    }
}
