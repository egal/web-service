<?php

namespace App\Http\Controllers;

use Egal\Core\Messages\MessageType;
use Exception;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Arr;

class LatestController extends BaseController
{

    public function getParameters(): array
    {
        $queryParams = $this->illuminateRequest->query->all();
        unset($queryParams['version']);

        $requestContentParams = $this->getParametersFromRequestContent();

        return array_merge(
            $queryParams,
            $requestContentParams
        );
    }

    /**
     * Возвращается массивом наполнение запроса,
     * указанное в body являющимся JSON,
     *
     * @return array
     * @throws Exception
     */
    public function getParametersFromRequestContent(): array
    {
        if ($this->illuminateRequest->getContentType() && $this->illuminateRequest->getContentType() !== 'json') {
            throw new Exception("We don't support content type!");
        }
        return json_decode($this->illuminateRequest->getContent(), true) ?? [];
    }

    public function generateIlluminateResponse(): IlluminateResponse
    {
        $response = $this->egalRequest->getResponse();
        $result = [
            MessageType::ACTION => $response->getActionMessage()->toArray(),
            MessageType::START_PROCESSING => is_null($response->getStartProcessingMessage())
                ? null
                : $response->getStartProcessingMessage()->toArray(),
            MessageType::ACTION_RESULT => is_null($response->getActionResultMessage())
                ? null
                : $response->getActionResultMessage()->toArray(),
            MessageType::ACTION_ERROR => is_null($response->getActionErrorMessage())
                ? null
                : $response->getActionErrorMessage()->toArray()
        ];

        if ($response->getStatusCode() !== 200) {
            $result = Arr::add($result, 'error_message', $response->getErrorMessage());
        }

        return response(json_encode($result), $response->getStatusCode(), [
            'Content-Type' => 'application/json'
        ]);
    }

}
