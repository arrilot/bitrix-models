<?php

namespace Arrilot\BitrixModels\Exceptions;

use Exception;

class NotSetModelIdException extends Exception
{
    /**
     * Exception message.
     *
     * @var string
     */
    protected $message = 'Model id is not set';
}
