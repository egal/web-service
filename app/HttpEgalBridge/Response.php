<?php

namespace App\HttpEgalBridge;

use Egal\Core\Communication\Response as EgalResponse;
use Egal\Core\Messages\MessageType;
use Illuminate\Http\Response as HttpResponse;
use Laravel\Lumen\Http\ResponseFactory as HttpResponseFactory;

class Response
{

    public static function toHttpResponse(EgalResponse $response): HttpResponse|HttpResponseFactory
    {
        $data = [];
        $data[MessageType::ACTION] = $response->getActionMessage()->toArray();
        $actionResult = $response->getActionResultMessage()?->toArray();
        $actionError = $response->getActionErrorMessage()?->toArray();

        if ($actionResult !== null) {
            $data[MessageType::ACTION_RESULT] = $actionResult;
            unset($data[MessageType::ACTION_RESULT][MessageType::ACTION]);
        }

        if ($actionError !== null) {
            $data[MessageType::ACTION_ERROR] = $actionResult;
            unset($data[MessageType::ACTION_ERROR][MessageType::ACTION]);
        }

        return response(json_encode($data), $response->getStatusCode(), ['Content-Type' => 'application/json']);
    }

}