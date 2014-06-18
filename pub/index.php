<?php
require("countries.php");
require("../stdlib.php");

$mongo = GMongo::getInstance(GMongo::CLUSTER_PLAYLISTS);
$db = $mongo->selectDB("reportAds");
$coll = $db->selectCollection("counts");
$coll->setSlaveOkay(true);


$date = date("Ymd", strtotime("-4 days"));
$allData = array('countries' => array(), 'placements' => array(), 'types' => array(), 'items' => array());
$days = array();
$dayCounts = array();
$dates = $coll->find(array('_id' => array('$gt' => $date)), array('c' => 1, 'p' => 1, 't' => 1, 'i' => 1));

foreach($dates as $date => $dateData) {
    foreach($dateData as $key => $data) {
        if (!is_array($data)) {
            $days[] = $data;
            $dayCounts[$date] = 0;
            continue;
        }
        foreach ($data as $id => $count) {
            switch ($key) {
                case "c":
                    if ($id == 1) {
                        continue;
                    }
                    if (!isset($allData['countries'][$id])) {
                        $allData['countries'][$id] = array();
                    }
                    $allData['countries'][$id][$date] = $count;
                    $dayCounts[$date] += $count;
                    break;
                case "p":
                    if (!isset($allData['placements'][$id])) {
                        $allData['placements'][$id] = array();
                    }
                    $allData['placements'][$id][$date] = $count;
                    break;
                case "t":
                    if (!isset($allData['types'][$id])) {
                        $allData['types'][$id] = array();
                    }
                    $allData['types'][$id][$date] = $count;
                    break;
                case "i":
                    if (!isset($allData['items'][$id])) {
                        $allData['items'][$id] = array();
                    }
                    $allData['items'][$id][$date] = $count;
                    break;
            }
        }
    }
}
$today = date("Ymd");
function sortByToday($a, $b)
{
    global $today;
    if (!isset($a[$today]) && !isset($b[$today])) {
        return 0;
    } else if (!isset($a[$today])) {
        return 1;
    } else if (!isset($b[$today])) {
        return -1;
    } else {
        return $b[$today] - $a[$today];
    }
}
uasort($allData['countries'], 'sortByToday');
uasort($allData['placements'], 'sortByToday');
uasort($allData['types'], 'sortByToday');
uasort($allData['items'], 'sortByToday');

?>

<h1>Global Counts</h1>
<table>
    <thead>
        <tr>
            <?php
                foreach($days as $day) {
                    echo "<th style='width: 100px;'>" . $day . "</th>";
                }
            ?>
        </tr>
    </thead>
    <tbody>
    <tr>
    <?php
        foreach ($days as $day) {
            echo "<td style='text-align:center'><a href='/date.php?id=$day'>" . (isset($dayCounts[$day]) ? $dayCounts[$day] : '') . "</a></td>";
        }
    ?></tr>
    </tbody>
</table>
<div style="float:left; margin-right:100px;">
    <h1>Per-Country Data</h1>
    <table>
        <thead>
            <tr>
                <th>Country</th>
                <?= "<th style='width: 70px;'>" . implode("</th><th style='width: 70px;'>", $days) . "</th>" ?>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($allData['countries'] as $country => $data) {
                echo "<tr>
                    <td><a href='/country.php?id=$country'>" . $_countries[$country] . "</a></td>";
                    foreach ($days as $day) {
                        echo "<td style='text-align:center;'><a href='/country.php?id=$country&date=$day'>" . (isset($data[$day]) ? $data[$day] : '') . "</a></td>";
                    }
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>
<div style="float:left; margin-right:100px;">
    <h1>Per-Placement Data</h1>
    <table>
        <thead>
            <tr>
                <th>Placement</th>
                <?= "<th style='width: 70px;'>" . implode("</th><th style='width: 70px;'>", $days) . "</th>" ?>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($allData['placements'] as $placement => $data) {
                echo "<tr>
                    <td><a href='/placement.php?id=" . urlencode($placement) . "'>" . htmlspecialchars($placement) . "</a></td>";
                    foreach ($days as $day) {
                        echo "<td style='text-align:center'><a href='/placement.php?id=$placement&date=$day'>" . (isset($data[$day]) ? $data[$day] : '') . "</a></td>";
                    }
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>
<div style="float:left; margin-right:100px;">
    <h1>Per-Type Data</h1>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <?= "<th style='width: 70px;'>" . implode("</th style='width: 70px;'><th>", $days) . "</th>" ?>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($allData['types'] as $type => $data) {
                echo "<tr>
                    <td>" . htmlspecialchars($type) . "</td>";
                    foreach ($days as $day) {
                        echo "<td style='text-align:center'>" . (isset($data[$day]) ? $data[$day] : '') . "</td>";
                    }
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>
<div style="float:left;">
    <h1>The Worst Ad Tags</h1>
    <table>
        <thead>
            <tr>
                <th>Tag Name</th>
                <?= "<th style='width: 70px;'>" . implode("</th><th style='width: 70px;'>", $days) . "</th>" ?>
            </tr>
        </thead>
        <tbody>
        <?php
            $items = array_slice($allData['types'], 0, 20);
            foreach($allData['items'] as $item => $data) {
                echo "<tr>
                    <td><a href='/adTag.php?id=" . urlencode($item) . "'>" . htmlspecialchars($item) . "</a></td>";
                    foreach ($days as $day) {
                        echo "<td style='text-align:center'>" . (isset($data[$day]) ? $data[$day] : '') . "</td>";
                    }
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>
<div style="clear:both"></div>
