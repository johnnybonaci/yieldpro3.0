<?php

namespace App\Exceptions;

use Exception;

class RecordNotFoundException extends Exception
{
    protected $message = 'Record not found';

    protected $code = 404;
}
