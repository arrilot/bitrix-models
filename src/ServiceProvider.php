<?php

namespace Arrilot\BitrixModels;

use Bitrix\Main\Config\Configuration;
use Illuminate\Container\Container;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Capsule\Manager as Capsule;

class ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public static function register()
    {
        self::bootstrapIlluminatePagination();
        self::bootstrapIlluminateDatabase();
    }
    
    /**
     * Bootstrap illuminate/pagination
     */
    protected static function bootstrapIlluminatePagination()
    {
        Paginator::currentPathResolver(function () {
            return $GLOBALS['APPLICATION']->getCurPage();
        });
        
        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = $_GET[$pageName];
            
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return $page;
            }
            
            return 1;
        });
    }

    /**
     * Bootstrap illuminate/database
     */
    protected static function bootstrapIlluminateDatabase()
    {
        $config = self::getBitrixDbConfig();
        
        $capsule = new Capsule(self::instantiateServiceContainer());
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'orm_test',
            'username'  => 'homestead',
            'password'  => 'secret',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    /**
     * Instantiate service container if it's not instantiated yet.
     */
    protected static function instantiateServiceContainer()
    {
        $container = Container::getInstance();
        
        if (!$container) {
            $container = new Container();
            Container::setInstance($container);
        }
        
        return $container;
    }
    
    /**
     * Get bitrix database configuration array.
     *
     * @return array
     */
    protected static function getBitrixDbConfig()
    {
        $config = Configuration::getInstance();
        $connections = $config->get('connections');

        return $connections['default'];
    }
}
