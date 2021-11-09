<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{

    /**
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        $code = $exception->getCode() !== 0 ? $exception->getCode() : 500;
        $data = ['message' => $exception->getMessage(), 'code' => $code];

        return response(json_encode($data), $code, ['Content-Type' => 'application/json']);
    }

}
