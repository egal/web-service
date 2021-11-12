<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{

    /**
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();

        if ($exception instanceof NotFoundHttpException) {
            $message = 'Not Found!';
            $code = 404;
        }

        $code = $code !== 0 ? $code : 500;

        return response(
            json_encode(['message' => $message, 'code' => $code]),
            $code,
            ['Content-Type' => 'application/json']
        );
    }

}
