<?php

namespace App\Exceptions;

use Exception;

class UnsupportedContentTypeException extends Exception
{

    protected $code = 403;

    protected $message = 'Unsupported content type!';

    public static function make(string $currentContentType): self
    {
        $result = new static();
        $result->message = "Unsupported $currentContentType content type!";

        return $result;
    }

}