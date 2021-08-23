<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\BitrixModels\BitrixWrapper;
use Arrilot\Tests\BitrixModels\Stubs\CacheManager;
use Arrilot\Tests\BitrixModels\Stubs\COption;
use Mockery;
use PHPUnit_Framework_TestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        BitrixWrapper::registerConfigProvider(new COption());
        BitrixWrapper::registerCacheManagerProvider(new CacheManager());
    }
    public function tearDown()
    {
        Mockery::close();
    }
}
