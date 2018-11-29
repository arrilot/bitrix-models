<?php

namespace Arrilot\BitrixModels;

use Arrilot\BitrixBlade\BladeProvider;
use Arrilot\BitrixModels\Debug\IlluminateQueryDebugger;
use Arrilot\BitrixModels\Models\BaseBitrixModel;
use Arrilot\BitrixModels\Models\EloquentModel;
use Bitrix\Main\Config\Configuration;
use DB;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Capsule\Manager as Capsule;

class ServiceProvider
{
    public static $illuminateDatabaseIsUsed = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public static function register()
    {
        BaseBitrixModel::setCurrentLanguage(strtoupper(LANGUAGE_ID));
        self::bootstrapIlluminatePagination();
    }

    /**
     * Register eloquent.
     *
     * @return void
     */
    public static function registerEloquent()
    {
        $capsule = self::bootstrapIlluminateDatabase();
        class_alias(Capsule::class, 'DB');

        if ($_COOKIE["show_sql_stat"] == "Y") {
            Capsule::enableQueryLog();

            $em = \Bitrix\Main\EventManager::getInstance();
            $em->addEventHandler('main', 'OnAfterEpilog', [IlluminateQueryDebugger::class, 'onAfterEpilogHandler']);
        }

        static::addEventListenersForHelpersHighloadblockTables($capsule);
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
     * @return Capsule
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

        static::$illuminateDatabaseIsUsed = true;

        return $capsule;
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

    /**
     * Для множественных полей Highload блоков битрикс использует вспомогательные таблицы.
     * Данный метод вешает обработчики на eloquent события добавления и обновления записей которые будут актуализировать и эти таблицы.
     *
     * @param Capsule $capsule
     */
    private static function addEventListenersForHelpersHighloadblockTables(Capsule $capsule)
    {
        $dispatcher = $capsule->getEventDispatcher();
        if (!$dispatcher) {
            return;
        }

        $dispatcher->listen(['eloquent.deleted: *'], function($event, $payload) {
            /** @var EloquentModel $model */
            $model = $payload[0];
            if (empty($model->multipleHighloadBlockFields)) {
                return;
            }

            $modelTable = $model->getTable();
            foreach ($model->multipleHighloadBlockFields as $multipleHighloadBlockField) {
                if (!empty($model['ID'])) {
                    $tableName = $modelTable.'_'.strtolower($multipleHighloadBlockField);
                    DB::table($tableName)->where('ID', $model['ID'])->delete();
                }
            }
        });

        $dispatcher->listen(['eloquent.updated: *', 'eloquent.created: *'], function($event, $payload) {
            /** @var EloquentModel $model */
            $model = $payload[0];
            if (empty($model->multipleHighloadBlockFields)) {
                return;
            }

            $dirty = $model->getDirty();
            $modelTable = $model->getTable();
            foreach ($model->multipleHighloadBlockFields as $multipleHighloadBlockField) {
                if (isset($dirty[$multipleHighloadBlockField]) && !empty($model['ID'])) {
                    $tableName = $modelTable.'_'.strtolower($multipleHighloadBlockField);

                    if (substr($event, 0, 16) === 'eloquent.updated') {
                        DB::table($tableName)->where('ID', $model['ID'])->delete();
                    }

                    $unserializedValues = unserialize($dirty[$multipleHighloadBlockField]);
                    if (!$unserializedValues) {
                        continue;
                    }

                    $newRows = [];
                    foreach ($unserializedValues as $unserializedValue) {
                        $newRows[] = [
                            'ID' => $model['ID'],
                            'VALUE' => $unserializedValue,
                        ];
                    }

                    if ($newRows) {
                        DB::table($tableName)->insert($newRows);
                    }
                }
            }
        });
    }
}
