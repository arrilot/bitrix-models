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
    protected $message = 'Model was constructed with invalid id';
}
