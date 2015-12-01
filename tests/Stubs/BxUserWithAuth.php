<?php

namespace Arrilot\Tests\BitrixModels\Stubs;

class BxUserWithAuth
{
    public function getId()
    {
        return 1;
    }

    public function getUserGroupArray()
    {
        return [1, 2, 3];
    }

    public function isAuthorized()
    {
        return true;
    }
}
