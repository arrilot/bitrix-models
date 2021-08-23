<?php

namespace Arrilot\BitrixModels;


class BitrixWrapper
{
    protected static $configProvider;
    protected static $cacheManagerProvider;
    
    public static function registerConfigProvider($provider)
    {
        static::$configProvider = $provider;
    }
    
    /**
     * @return \COption
     */
    public static function configProvider()
    {
        if (!static::$configProvider) {
            static::$configProvider = new \COption();
        }
        
        return static::$configProvider;
    }
    
    public static function registerCacheManagerProvider($provider)
    {
        static::$cacheManagerProvider = $provider;
    }
    
    /**
     * @return \CCacheManager
     */
    public static function cacheManagerProvider()
    {
        if (!static::$cacheManagerProvider) {
            global $CACHE_MANAGER;
            static::$cacheManagerProvider = $CACHE_MANAGER;
        }
        
        return static::$cacheManagerProvider;
    }
}