<?
use Arrilot\BitrixModels\Debug\IlluminateQueryDebugger;
use Illuminate\Database\Capsule\Manager;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!$bShowStat) {
    return;
}

$totalQueryCount = 0;
$totalQueryTime = 0.0;

$queryLog = Manager::getQueryLog();
foreach($queryLog as $loggedQuery) {
    $totalQueryCount++;
    $totalQueryTime += $loggedQuery['time'] / 1000;
}

echo '<div class="bx-component-debug bx-debug-summary" style="bottom: 80px;">';
echo 'Статистика SQL запросов illuminate/database<br>';
echo '<a title="Посмотреть подробную статистику по запросам" href="javascript:BX_DEBUG_INFO_ILLUMINATE.Show(); BX_DEBUG_INFO_ILLUMINATE.ShowDetails(\'BX_DEBUG_INFO_ILLUMINATE_1\');">'.'Всего SQL запросов: '."</a> ".intval($totalQueryCount)."<br>";
echo "Время исполнения запросов: ".round($totalQueryTime, 4)." сек.<br>";
echo '</div><div class="empty"></div>';

//CJSPopup
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
?>
<script type="text/javascript">
    BX_DEBUG_INFO_ILLUMINATE = new BX.CDebugDialog();
</script>
<?
$obJSPopup = new CJSPopupOnPage('', array());
$obJSPopup->jsPopup = 'BX_DEBUG_INFO_ILLUMINATE';
$obJSPopup->StartDescription('bx-core-debug-info');
?>
<p>Всего запросов: <?= $totalQueryCount ?>, время: <?= round($totalQueryTime, 4) ?> сек.</p>
<p>Поиск: <input type="text" style="height:16px" onkeydown="filterTable(this, 'queryDebugIlluminate', 1)" onpaste="filterTable(this, 'queryDebugIlluminate', 1)" oninput="filterTable(this, 'queryDebugIlluminate', 1)"></p>
<?
$obJSPopup->StartContent(['buffer' => true]);
if(count($queryLog) > 0) {
    ?><div class="bx-debug-content bx-debug-content-table"><?
        $arQueries = [];
        foreach ($queryLog as $j => $arQueryDebug) {
            $strSql = $arQueryDebug["query"];
            $arQueries[$strSql]["COUNT"]++;
            $arQueries[$strSql]["CALLS"][] = [
                "QUERY" => $strSql,
                "BINDINGS" => $arQueryDebug['bindings'],
                "TIME"=> $arQueryDebug["time"] / 1000,
            ];
        }
        ?><table id="queryDebugIlluminate" cellpadding="0" cellspacing="0" border="0"><?
            $j = 1;
            foreach($arQueries as $strSql => $query) {
                ?><tr>
                    <td class="number" valign="top"><?= $j ?></td>
                    <td><a href="javascript:BX_DEBUG_INFO_ILLUMINATE.ShowDetails('BX_DEBUG_INFO_ILLUMINATE_<?= $j ?>')"><?= htmlspecialcharsbx(substr($strSql, 0, 100))."..."?></a>&nbsp;(<?= $query["COUNT"] ?>) </td>
                    <td class="number" valign="top"><?
                        $t = 0.0;
                        foreach($query["CALLS"] as $call) {
                            $t += $call["TIME"];
                        }
                        echo number_format($t / $query["COUNT"], 5);
                    ?></td>
                </tr><?
                $j++;
            }
        ?></table>
    </div>#DIVIDER#<div class="bx-debug-content bx-debug-content-details">
    <?
        $j = 1;
        foreach($arQueries as $strSql => $query) {
            ?><div id="BX_DEBUG_INFO_ILLUMINATE_<?= $j ?>" style="display:none">
            <b>Запрос № <?= $j ?>:</b>
            <br /><br />
            <?
            $strSql = preg_replace("/[\\n\\r\\t\\s ]+/", " ", $strSql);
            $strSql = preg_replace("/^ +/", "", $strSql);
            $strSql = preg_replace("/ (INNER JOIN|OUTER JOIN|LEFT JOIN|SET|LIMIT) /i", "\n\\1 ", $strSql);
            $strSql = preg_replace("/(INSERT INTO [A-Z_0-1]+?)\\s/i", "\\1\n", $strSql);
            $strSql = preg_replace("/(INSERT INTO [A-Z_0-1]+?)([(])/i", "\\1\n\\2", $strSql);
            $strSql = preg_replace("/([\\s)])(VALUES)([\\s(])/i", "\\1\n\\2\n\\3", $strSql);
            $strSql = preg_replace("/ (FROM|WHERE|ORDER BY|GROUP BY|HAVING) /i", "\n\\1\n", $strSql);
            echo str_replace(["\n"], ["<br />"], htmlspecialcharsbx($strSql));
            ?>
            <br /><br />
            <?
            $k = 1;
            foreach($query["CALLS"] as $call) {
                ?>
                <br />
                <b>Экземпляр № <?= $k ?>:</b><br>
                <?= htmlspecialcharsbx(IlluminateQueryDebugger::interpolateQuery($call['QUERY'], $call['BINDINGS'])) ?>
                <br /><br />
                Время выполнения: <?= round($call["TIME"], 5) ?> сек.
                <?
                $k++;
            }
            ?></div>
            <?
            $j++;
        };
        ?>
    </div>
    <?
}
$obJSPopup->StartButtons();
$obJSPopup->ShowStandardButtons(array('close'));
