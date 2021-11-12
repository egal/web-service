<?php

namespace App\Exceptions;

use Exception;

class IncorrectRouteLineException extends Exception
{

    protected $code = 403;

    protected $message = 'Route line is incorrect!';

}