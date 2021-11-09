<?php

namespace App\HttpEgalBridge;

use Egal\Core\Communication\Request as EgalRequest;
use Exception;
use Illuminate\Http\Request as HttpRequest;

class Request
{

    public static function fromHttpRequest(string $routeLine, HttpRequest $httpRequest): EgalRequest
    {
        $explodedRouteLine = explode('/', $routeLine);

        if (count($explodedRouteLine) > 4 || count($explodedRouteLine) < 3) {
            throw new Exception('Route line is incorrect!');
        }

        [$serviceName, $modelName, $actionName] = $explodedRouteLine;

        if ($httpRequest->getContentType() && $httpRequest->getContentType() !== 'json') {
            throw new Exception('We don\'t support selected content type! [' . $httpRequest->getContentType() . ']');
        }

        $parameters = array_merge(
            $httpRequest->query->all(),
            json_decode($httpRequest->getContent(), true) ?? []
        );

        $request = new EgalRequest($serviceName, $modelName, $actionName);
        $request->disableServiceAuthorization();
        $request->setToken($httpRequest->header('Authorization'));
        $request->addParameters($parameters);

        if (isset($explodedRouteLine[3])) {
            $request->addParameter('id', $explodedRouteLine[3]);
        }

        return $request;
    }

}