<?php

namespace App\SwooleSymfonyBridge;

use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{

    public static function createFromSwooleRequest(SwooleRequest $swooleRequest): SymfonyRequest
    {
        $headers = array_combine(array_map(function ($key) {
            return 'HTTP_' . str_replace('-', '_', $key);
        }, array_keys($swooleRequest->header)), array_values($swooleRequest->header));

        $server = array_change_key_case(array_merge($swooleRequest->server, $headers), CASE_UPPER);

        if ($trustedProxies = $server['TRUSTED_PROXIES'] ?? false) {
            SymfonyRequest::setTrustedProxies(
                explode(',', $trustedProxies),
                SymfonyRequest::HEADER_X_FORWARDED_HOST ^ SymfonyRequest::HEADER_X_FORWARDED_HOST
            );
        }

        if ($trustedHosts = $server['TRUSTED_HOSTS'] ?? false) {
            SymfonyRequest::setTrustedHosts(explode(',', $trustedHosts));
        }

        $symfonyRequest = new SymfonyRequest(
            $swooleRequest->get ?? [],
            $swooleRequest->post ?? [],
            [],
            $swooleRequest->cookie ?? [],
            $swooleRequest->files ?? [],
            $server,
            $swooleRequest->rawContent()
        );

        if (
            str_starts_with($symfonyRequest->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(
                strtoupper($symfonyRequest->server->get('REQUEST_METHOD', 'GET')),
                ['PUT', 'DELETE', 'PATCH'],
                true
            )
        ) {
            parse_str($symfonyRequest->getContent(), $data);
            $symfonyRequest->request = new ParameterBag($data);
        }

        return $symfonyRequest;
    }

}
