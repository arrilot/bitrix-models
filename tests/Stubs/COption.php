<?php

namespace Arrilot\Tests\BitrixModels\Stubs;


class COption
{
    public static $config = [
        'main' => [
            'component_managed_cache_on' => 'Y',
        ]
    ];
    
    public static function GetOptionInt($module_id, $name, $def = "", $site = false)
    {
        return intval(static::GetOptionString($module_id, $name, $def, $site));
    }
    
    public static function GetOptionString($module_id, $name, $def = "", $site = false)
    {
        return static::$config[$module_id][$name] ?: $def;
    }
}