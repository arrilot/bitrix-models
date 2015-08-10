<?php

namespace Arrilot\Tests\BitrixModels\Stubs;

class BxUserWithoutAuth
{
    public function getId()
    {
        return;
    }

    public function getUserGroupArray()
    {
        return [2];
    }

    public function isAuthorized()
    {
        return false;
    }
}
