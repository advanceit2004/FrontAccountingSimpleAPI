<?php

declare(strict_types=1);

namespace FAAPI\Http;

use FAAPI\FrontAccounting\Authorization;
use FAAPI\FrontAccounting\Kernel;
use FAAPI\Security\ApiUser;
use FAAPI\Security\TokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class BearerTokenMiddleware implements MiddlewareInterface
{
    /** @param list<string> $requiredAreas */
    public function __construct(private readonly array $requiredAreas = [])
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return JsonResponse::error(new Response(), 'MISSING_TOKEN', 'Authorization Bearer token is required', 401);
        }

        $claims = TokenService::decode($matches[1]);
        if ($claims === null) {
            return JsonResponse::error(new Response(), 'INVALID_TOKEN', 'Bearer token is invalid or expired', 401);
        }

        Kernel::boot();
        Kernel::selectCompany((int) $claims['company']);

        $user = ApiUser::fromClaims($claims);
        if (!Authorization::allows($user, $this->requiredAreas)) {
            return JsonResponse::error(new Response(), 'FORBIDDEN', 'The authenticated user is not allowed to access this resource', 403);
        }

        return $handler->handle($request->withAttribute('apiUser', $user));
    }
}
