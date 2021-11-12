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
        $data = [
            MessageType::ACTION => $response->getActionMessage()->toArray(),
            MessageType::ACTION_RESULT => $response->getActionResultMessage()?->toArray(),
            MessageType::ACTION_ERROR => $response->getActionErrorMessage()?->toArray(),
        ];

        unset($data[MessageType::ACTION_RESULT][MessageType::ACTION]);
        unset($data[MessageType::ACTION_ERROR][MessageType::ACTION]);

        return response(json_encode($data), $response->getStatusCode(), ['Content-Type' => 'application/json']);
    }

}