<?php

namespace Arrilot\BitrixModels\Debug;

use Illuminate\Database\Capsule\Manager;

class IlluminateQueryDebugger
{
    public static function onAfterEpilogHandler()
    {
        global $DB, $USER;

        $bExcel = isset($_REQUEST["mode"]) && $_REQUEST["mode"] === 'excel';
        if (!defined("ADMIN_AJAX_MODE") && !defined('PUBLIC_AJAX_MODE') && !$bExcel) {
            $bShowStat = ($DB->ShowSqlStat && ($USER->CanDoOperation('edit_php') || $_SESSION["SHOW_SQL_STAT"]=="Y"));
            if ($bShowStat && class_exists(Manager::class) && Manager::logging()) {
                require_once(__DIR__.'/debug_info.php');
            }
        }
    }
    
    public static function interpolateQuery($query, $params)
    {
        $keys = array();

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            $keys[] = is_string($key) ? '/:'.$key.'/' : '/[?]/';
            $params[$key] = "'" . $value . "'";
        }
    
        return preg_replace($keys, $params, $query, 1, $count);
    }
}
