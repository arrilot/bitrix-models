<?php

namespace Arrilot\BitrixModels;

use Exception;

class InvalidModelIdException extends Exception
{
    /**
     * Exception message.
     *
     * @var string
     */
    protected $message = 'Model id is not set';
}
