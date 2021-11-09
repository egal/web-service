<?php

namespace App\SwooleSymfonyBridge;

use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response
{

    public static function writeSwooleResponse(SwooleResponse $swooleResponse, SymfonyResponse $sfResponse)
    {
        self::writeHeaders($swooleResponse, $sfResponse);
        $swooleResponse->end($sfResponse->getContent());
    }

    protected static function writeHeaders(SwooleResponse $swooleResponse, SymfonyResponse $sfResponse)
    {
        if (headers_sent()) {
            return;
        }

        foreach ($sfResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            foreach ($values as $value) {
                $swooleResponse->header($name, $value);
            }
        }

        $swooleResponse->status($sfResponse->getStatusCode());

        foreach ($sfResponse->headers->getCookies() as $cookie) {
            $swooleResponse->cookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
    }

}
