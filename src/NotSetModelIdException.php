<?php

namespace Arrilot\BitrixModels;

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
