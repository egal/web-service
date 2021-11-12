<?php

namespace App\HttpEgalBridge;

use App\Exceptions\IncorrectRouteLineException;
use App\Exceptions\UnsupportedContentTypeException;
use Egal\Core\Communication\Request as EgalRequest;
use Illuminate\Http\Request as HttpRequest;

class Request
{

    public static function fromHttpRequest(string $routeLine, HttpRequest $httpRequest): EgalRequest
    {
        $contentType = $httpRequest->getContentType();

        if ($contentType && $contentType !== 'json') {
            throw UnsupportedContentTypeException::make($contentType);
        }

        $explodedRouteLine = explode('/', $routeLine);

        if (count($explodedRouteLine) > 4 || count($explodedRouteLine) < 3) {
            throw new IncorrectRouteLineException();
        }

        [$serviceName, $modelName, $actionName] = $explodedRouteLine;
        $request = new EgalRequest($serviceName, $modelName, $actionName);
        $request->disableServiceAuthorization();
        $request->setToken($httpRequest->header('Authorization'));
        $request->addParameters(array_merge(
            $httpRequest->query->all(),
            json_decode($httpRequest->getContent(), true) ?? []
        ));

        if (isset($explodedRouteLine[3])) {
            $request->addParameter('id', $explodedRouteLine[3]);
        }

        return $request;
    }

}