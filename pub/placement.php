<?php
require("countries.php");
require("../stdlib.php");

if (empty($_GET['id'])) {
    die("invalid placement");
}
$placementID = $_GET['id'];

if (empty($_GET['date']) || !is_numeric($_GET['date'])) {
    $date = date("Ymd");
} else {
    $date = $_GET['date'];
}

$mongo = GMongo::getInstance(GMongo::CLUSTER_PLAYLISTS);
$db = $mongo->selectDB("reportAds");
$coll = $db->selectCollection("counts");
$coll->setSlaveOkay(true);

$date1 = date("Ymd", strtotime("-5 days", strtotime($date)));
if ($endDate != date("Ymd")) {
    $date2 = date("Ymd", strtotime("+5 days", strtotime($date)));
} else {
    $date2 = $endDate;
}
$placementsData = array();
$typeData = array();
$itemData = array();
$counts = array();
$days = array();

function sortByToday($a, $b)
{
    global $date;
    if (!isset($a[$date]) && !isset($b[$date])) {
        return 0;
    } else if (!isset($a[$date])) {
        return 1;
    } else if (!isset($b[$date])) {
        return -1;
    } else {
        return $b[$date] - $a[$date];
    }
}

$dates = $coll->find(array('_id' => array('$gt' => $date1, '$lte' => $date2)), array("p.$placementID" => 1, "p:$placementID.t" => 1, "p:$placementID.i" => 1)); //this sucks, we have to fetch all the docs, instead of using key regexes

foreach($dates as $dt => $dateData) {
    foreach($dateData as $key => $pData) {
        switch ($key) {
            case "_id":
                continue;
            case "p":
                $counts[$dt] = isset($pData[$placementID]) ? $pData[$placementID] : 0;
                break;
            case "p:$placementID":
                if (isset($pData['t'])) {
                    foreach ($pData['t'] as $type => $count) {
                        if (!isset($typeData[$type])) {
                            $typeData[$type] = array();
                        }
                        $typeData[$type][$dt] = $count;
                    }
                }
                if (isset($pData['i'])) {
                    foreach ($pData['i'] as $item => $count) {
                        if (!isset($itemData[$item])) {
                            $itemData[$item] = array();
                        }
                        $itemData[$item][$dt] = $count;
                    }
                }
                break;
        }
    }
    $days[] = $dt;
}

uasort($typeData, 'sortByToday');
uasort($itemData, 'sortByToday');
$topItemData = array_slice($itemData, 0, 20);

ob_start();
?>
<script type="text/javascript">
    function getUser(userID) {
        var xmlhttp =  new XMLHttpRequest();
        xmlhttp.open('GET', 'getUser.php?u=' + userID, true);
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
                if (xmlhttp.status == 200) {
                    alert(xmlhttp.responseText);
                }
            }
        }
        xmlhttp.send();
        return false;
    }
</script>

<a href="/">Homepage</a> | <a href="<?= $_SERVER['REQUEST_URI'] ?>&export">Export</a> | <a href="<?= $_SERVER['REQUEST_URI'] ?>&exportAdUnits">Export Ad Tags</a>

<h1>Historical Counts</h1>
<table>
    <thead>
        <tr>
            <?= "<th style='width: 100px;'>" . implode("</th><th>", $days) . "</th>" ?>
        </tr>
    </thead>
    <tbody>
    <tr>
    <?php
        foreach ($days as $day) {
            echo "<td style='text-align:center'>" . (isset($counts[$day]) ? $counts[$day] : 0) . "</td>";
        }
    ?></tr>
    </tbody>
</table>


<div style="float:left; margin-right:100px;">
    <h1>Type Data</h1>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <?= "<th style='width:70px;'>" . implode("</th><th style='width:70px;'>", $days) . "</th>" ?>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($typeData as $type => $data) {
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
<div style="float:left; margin-right:100px;">
    <h1>The 20 Worst Ad Tags</h1>
    <table>
        <thead>
            <tr>
                <th>Ad Tag</th>
                <?= "<th style='width:70px;'>" . implode("</th><th style='width:70px;'>", $days) . "</th>" ?>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($topItemData as $item => $data) {
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
<div style="clear: both;"></div>

<?php

$file = file_get_contents("http://reportadsfiles.in.escapemg.com/adDesc-$date");
$file = str_replace("<br />\n", "<br />", $file); //for for stupid nl2br
$lines = explode("\n", $file);
$info = array();
$domains = array();
$domainsToBlock = array();
$sessions = array();
if (empty($lines)) {
    die("<h1>No data</h1>");
}
foreach ($lines as $line) {
    $count = substr($line, 0, 1); //todo: support for double digits
    if (!$count) {
        continue;
    }
    $line = substr($line, 2);
    if ($count == 5) {
        list($t, $c, $p, $i, $d) = explode("|", $line);
        $y = null;
        $s = null;
        $u = null;
        $item = null;
    } elseif ($count == 6) {
        list($t, $c, $p, $i, $s, $d) = explode("|", $line);
        $y = null;
        $u = null;
        $item = null;
    } elseif ($count == 7) {
        list($t, $c, $p, $i, $s, $y, $d) = explode("|", $line);
        $u = null;
        $item = null;
    } elseif ($count == 9) {
        list($t, $c, $p, $i, $s, $y, $u, $item, $d) = explode("|", $line);
    } else {
        if (count($info)) {
            $info[count($info)-1]['desc'] .= "<br>$line";
        }
        continue;
    }

    if ($p == $placementID) {
        if (!empty($s)) {
            $hash = md5($i . $s);
            if (isset($sessions[$hash])) {
                $sessions[$hash]++;
            } else {
                $sessions[$hash] = 1;
            }
            if ($sessions[$hash] > 1) {
                continue;
            }
        }
         $info[] = array('time' => $t, 'placement' => $p, 'info' => $i, 'desc' => $d, 'type' => $y, 'session' => $s, 'user' => $u, 'itemID' => $item);
        $links = explode(",http", $i);
        if (strpos($links[0], "http://grooveshark.com/dfpAds.html") === 0) {
            continue;
        }
        if (count($links) == 1 && stripos($links[0], "adurl=ht") !== false) {
            if (!preg_match("/adurl\=(http(?:[^\&]+|$))/", $links[0], $matches)) {
                $matches = array('', $links[0]);
            }
            $host = parse_url($matches[1], PHP_URL_HOST);
            if ($host && isset($domains[$host])) {
                $domains[$host]++;
            } else if ($host) {
                $domains[$host] = 1;
            }
            if (!empty($matches[0])) {
                if ($host && isset($domainsToBlock[$host])) {
                    $domainsToBlock[$host]++;
                } else if ($host) {
                    $domainsToBlock[$host] = 1;
                }
            }
        } else {
            $linksReported = array();
            foreach ($links as $i => $link) {
                if ($i > 0) {
                    $link = "http$link";
                }
                $host = parse_url($link, PHP_URL_HOST);
                if (isset($linksReported[$host])) {
                    continue;
                }
                $linksReported[$host] = 1;
                if ($host && isset($domains[$host])) {
                    $domains[$host]++;
                } else if ($host) {
                    $domains[$host] = 1;
                }
            }
        }
    }

}
if (empty($info)) {
    die("<h1>No data</h1>");
}
$info = array_reverse($info); //newest first
arsort($domainsToBlock);
arsort($domains);
$domains = array_splice($domains, 0, 10);
?>
<div style="float:left;margin-right: 100px;">
    <h2>Top 10 Domains/Networks</h2>
    <table>
        <thead>
            <tr>
                <th>Domain</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($domains as $domain => $count) {
                echo "<tr>
                    <td>$domain</td>
                    <td>$count</td>
                    </tr>";
            }
        ?>
        </tbody>
    </table>
</div>

<div style="float:left; display:none;">
    <h2>Domains to Block</h2>
    <table>
        <thead>
            <tr>
                <th>Domain</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($domainsToBlock as $domain => $count) {
                echo "<tr>
                    <td>$domain</td>
                    <td>$count</td>
                    </tr>";
            }
        ?>
        </tbody>
    </table>
</div>

<div style="clear: both;"></div>

<h2>All Data</h2>
<table>
    <thead>
        <tr>
            <th style="width: 120px;">Time</th>
            <th>Country</th>
            <th style="width: 800px">Click Info</th>
            <th>Type</th>
            <th>Ad Tag</th>
            <th style="width: 400px">Description</th>
        </tr>
    </thead>
    <tbody>
    <?php
        foreach($info as $data) {
            echo "<tr>
                <td>" . date("Y-m-d H:i", $data['time']) . (!empty($data['user']) ? " - <a style=\"cursor:pointer\" onclick=\"return getUser('" . $data['user'] . "');\"><u>User</u></a></td>" : "") . "
                <td>" . $_countries[$data['country']] . "</td>
                <td style='font-size:11px; width: 600px; word-wrap:break-word; white-space:normal; word-break:break-all; border-bottom:1px #000 solid;'>" .urldecode( preg_replace("/adurl\=http(?:[^\&]+|$)/", "<b>\\0</b>", str_replace(array(",http:", ",https:"), array("<br>http:", "<br>https:"),  htmlspecialchars($data['info'])))) . "</td>
                <td><b>" . $data['type'] . "</b></td>
                <td>" . $data['itemID'] . "</td>
                <td style='width: 400px;border-left: 1px #000 solid;word-break: break-all;word-wrap: break-word;'><b>" . htmlspecialchars($data['desc']) . "</b></td>
                </tr>";
        }
    ?>
    </tbody>
</table>
<?php
if (!isset($_GET['export']) && !isset($_GET['exportAdTags'])) {
    ob_end_flush();
    exit;
}

ob_end_clean();

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
header("Content-Description: File Transfer");
if (isset($_GET['exportAdTags'])) {
    header("Content-Disposition: attachment; filename=\"placement-$placementID-$date-tags.csv\"");
} else {
    header("Content-Disposition: attachment; filename=\"placement-$placementID-$date.csv\"");
}
header("Content-Type: text/csv");
header("Content-Transfer-Encoding: binary");

if (isset($_GET['exportAdTags'])) {
    echo '"Ad Unit","Count"' . "\n";

    foreach($itemData as $item => $count) {
        if (!empty($count[$date])) {
            echo '"' . str_replace('"', '""', $item) . '",' . $count[$date] . "\n";
        }
    }

} else {

    echo '"Time","Country","Click Info","Type","User Description"' . "\n";

    foreach($info as $data) {
        echo '"' . date("Y-m-d H:i", $data['time']) . '","' . $_countries[$data['country']] . '","' . ($data['type'] ? $data['type'] : 'n/a') . '","' . str_replace('"', '""', $data['desc']) . '","' . str_replace('"', '""', str_replace(array(",http:", ",https:"), array("\nhttp:", "\nhttps:"), $data['info'])) . "\"\n";
    }

}

?>