<?php

namespace Arrilot\BitrixModels;

use Arrilot\BitrixBlade\BladeProvider;
use Arrilot\BitrixModels\Debug\IlluminateQueryDebugger;
use Bitrix\Main\Config\Configuration;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
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
    }

    /**
     * Register eloquent.
     *
     * @return void
     */
    public static function registerEloquent()
    {
        self::bootstrapIlluminateDatabase();
        class_alias(Capsule::class, 'DB');

        if ($_COOKIE["show_sql_stat"] == "Y") {
            Capsule::enableQueryLog();

            $em = \Bitrix\Main\EventManager::getInstance();
            $em->addEventHandler('main', 'OnAfterEpilog', [IlluminateQueryDebugger::class, 'onAfterEpilogHandler']);
        }
    }

    /**
     * Bootstrap illuminate/pagination
     */
    protected static function bootstrapIlluminatePagination()
    {
        if (class_exists(BladeProvider::class)) {
            Paginator::viewFactoryResolver(function () {
                return BladeProvider::getViewFactory();
            });
        }

        Paginator::$defaultView = 'pagination.default';
        Paginator::$defaultSimpleView = 'pagination.simple-default';

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
            'host'      => $config['host'],
            'database'  => $config['database'],
            'username'  => $config['login'],
            'password'  => $config['password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);

        if (class_exists(Dispatcher::class)) {
            $capsule->setEventDispatcher(new Dispatcher());
        }

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
