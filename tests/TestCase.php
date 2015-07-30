<?php

namespace Arrilot\BitrixModels\Test;

use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        WidgetId::reset();
    }

    public function javascriptDataStub($widgetName, $widgetParams = [], $id = 1)
    {
        return json_encode([
            'id'     => $id,
            'name'   => $widgetName,
            'params' => serialize($widgetParams),
            '_token' => 'token_stub',
        ]);
    }
}
