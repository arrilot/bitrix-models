<?php

namespace Arrilot\BitrixModels;

use Illuminate\Pagination\Paginator;

class ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public static function register()
    {
        Paginator::currentPathResolver(function () {
            return $GLOBALS['APPLICATION']->getCurPage();
        });

        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = $_GET[$pageName];

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return $page;
            }

            return 1;
        });
    }
}
