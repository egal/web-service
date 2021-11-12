<?php

namespace App\HttpEgalBridge;

use App\Exceptions\UnsupportedContentTypeException;
use Egal\Core\Communication\Request as EgalRequest;
use Illuminate\Http\Request as HttpRequest;

class Request
{

    public static function fromHttpRequest(
        HttpRequest $httpRequest,
        string      $service,
        string      $model,
        string      $action,
        ?string     $id = null
    ): EgalRequest
    {
        $contentType = $httpRequest->getContentType();

        if ($contentType && $contentType !== 'json') {
            throw UnsupportedContentTypeException::make($contentType);
        }

        $request = new EgalRequest($service, $model, $action);
        $request->disableServiceAuthorization();
        $request->setToken($httpRequest->header('Authorization'));
        $request->addParameters(array_merge(
            $httpRequest->query->all(),
            json_decode($httpRequest->getContent(), true) ?? []
        ));

        if ($id) {
            $request->addParameter('id', $id);
        }

        return $request;
    }

}