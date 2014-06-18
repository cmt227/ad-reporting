<?php
require("countries.php");
require("../stdlib.php");

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    $date = date("Ymd");
}
$date = $_GET['id'];

$mongo = GMongo::getInstance(GMongo::CLUSTER_PLAYLISTS);
$db = $mongo->selectDB("reportAds");
$coll = $db->selectCollection("counts");
$coll->setSlaveOkay(true);

$date1 = date("Ymd", strtotime("-5 days", strtotime($date)));
$date2 = date("Ymd", strtotime("+5 days", strtotime($date)));
$allData = array('countries' => array(), 'placements' => array(), 'types' => array(), 'items' => array());
$typeData = array();
$counts = array();
$days = array();
$dates = $coll->find(array('_id' => array('$gt' => $date1, '$lt' => $date2)), array('c' => 1, 'p' => 1, 't' => 1, 'i' => 1));

foreach($dates as $dt => $dateData) {
    foreach($dateData as $key => $data) {
        if (!is_array($data)) {
            $days[] = $data;
            $counts[$dt] = 0;
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
                    $allData['countries'][$id][$dt] = $count;
                    $counts[$dt] += $count;
                    break;
                case "p":
                    if (!isset($allData['placements'][$id])) {
                        $allData['placements'][$id] = array();
                    }
                    $allData['placements'][$id][$dt] = $count;
                    break;
                case "t":
                    if (!isset($allData['types'][$id])) {
                        $allData['types'][$id] = array();
                    }
                    $allData['types'][$id][$dt] = $count;
                    break;
                case "i":
                    if (!isset($allData['items'][$id])) {
                        $allData['items'][$id] = array();
                    }
                    $allData['items'][$id][$dt] = $count;
                    break;
            }
        }
    }
}
$today = $date;
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
$itemData = $allData['items'];
uasort($itemData, 'sortByToday');
$topItemData = array_slice($itemData, 0, 20);

ob_start();
?>
<script type="text/javascript">
    function getUser(session) {
        var xmlhttp =  new XMLHttpRequest();
        xmlhttp.open('GET', 'getUser.php?s=' + session, true);
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

<a href="/">Homepage</a> | <a href="<?= $_SERVER['REQUEST_URI'] ?>&export">Export</a> | <a href="<?= $_SERVER['REQUEST_URI'] ?>&exportAdTags">Export Ad Tags</a>

<h1>Historical Counts</h1>
<table>
    <thead>
        <tr>
            <?php
                foreach($days as $day) {
                    if ($day == $today) {
                        echo "<th style='width:100px;background:#ccc;'>" . $day . "</th>";
                    } else {
                        echo "<th style='width:100px;'>" . $day . "</th>";
                    }
                }
            ?>
        </tr>
    </thead>
    <tbody>
    <tr>
    <?php
        foreach ($days as $day) {
            echo "<td style='text-align:center'><a href='/date.php?id=$day'>" . (isset($counts[$day]) ? $counts[$day] : '') . "</a></td>";
        }
    ?></tr>
    </tbody>
</table>
<div style="float:left; margin-right:100px;">
    <h1>Country Data</h1>
    <table>
        <thead>
            <tr>
                <th>Country</th>
                <?php
                    foreach($days as $day) {
                        if ($day == $today) {
                            echo "<th style='width:60px;background:#ccc;'>" . $day . "</th>";
                        } else {
                            echo "<th style='width:60px;'>" . $day . "</th>";
                        }
                    }
                ?>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($allData['countries'] as $country => $data) {
                if (empty($data[$today])) {
                    continue;
                }
                echo "<tr>
                    <td><a href='/country.php?id=$country&date=$date'>" . $_countries[$country] . "</a></td>";
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
    <h1>Placement Data</h1>
    <table>
        <thead>
            <tr>
                <th>Placement</th>
                <?php
                    foreach($days as $day) {
                        if ($day == $today) {
                            echo "<th style='width:60px;background:#ccc;'>" . $day . "</th>";
                        } else {
                            echo "<th style='width:60px;'>" . $day . "</th>";
                        }
                    }
                ?>
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
    <h1>Type Data</h1>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <?php
                    foreach($days as $day) {
                        if ($day == $today) {
                            echo "<th style='width:60px;background:#ccc;'>" . $day . "</th>";
                        } else {
                            echo "<th style='width:60px;'>" . $day . "</th>";
                        }
                    }
                ?>
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
    <h1>The 20 Worst Ad Tags</h1>
    <table>
        <thead>
            <tr>
                <th>Ad Unit</th>
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

<div style="clear:both"></div>
<h1>User Provided/Click Data</h1>
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

    if ($c > 1) {
        if (!empty($s)) {
            $hash = md5($i . $s);
            if (isset($sessions[$hash])) {
                $sessions[$hash]++;
            } else {
                $sessions[$hash] = 1;
            }
            if ($sessions[$hash] > 2) {
                continue;
            }
        }
        $info[] = array('time' => $t, 'placement' => $p, 'country' => $c, 'info' => $i, 'desc' => $d, 'type' => $y, 'session' => $s, 'user' => $u, 'itemID' => $item);
        $links = explode(",http:", $i);
        if (stripos($links[0], "http://grooveshark.com/dfpads.html") === 0) {
            continue;
        }
        if (count($links) == 1 && stripos($links[0], "adurl=ht") !== false) {
            $links[0] = urldecode($links[0]);
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
                $link = urldecode($link);
                if ($i > 0) {
                    $link = "http:$link";
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
            <th style="width: 200px;">Time</th>
            <th>Country</th>
            <th>Placement</th>
            <th style="width: 800px">Click Info</th>
            <th>Type</th>
            <th style="width: 400px">Description</th>
        </tr>
    </thead>
    <tbody>
    <?php
        foreach($info as $data) {
            echo "<tr>
                <td>" . date("Y-m-d H:i", $data['time']) . (!empty($data['user']) ? " - <a style=\"cursor:pointer\" onclick=\"return getUser('" . $data['user'] . "');\"><u>User</u></a></td>" : "") . "
                <td>" . $_countries[$data['country']] . "</td>
                <td>" . htmlspecialchars($data['placement']) . "</td>
                <td style='font-size:11px; width: 600px; word-wrap:break-word; white-space:normal; word-break:break-all; border-bottom:1px #000 solid;'>" . urldecode( preg_replace("/adurl\=http(?:[^\&]+|$)/", "<b>\\0</b>", str_replace(array(",http:", ",https:"), array("<br>http:", "<br>https:"), htmlspecialchars($data['info'])))) . "</td>
                <td><b>" . $data['type'] . "</b></td>
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
    header("Content-Disposition: attachment; filename=\"date-$date-tags.csv\"");
} else {
    header("Content-Disposition: attachment; filename=\"date-$date.csv\"");
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

    echo '"Time","Country","Placement","Click Info","Type","User Description"' . "\n";

    foreach($info as $data) {
        echo '"' . date("Y-m-d H:i", $data['time']) . '","' . $_countries[$data['country']] . '","' . str_replace('"', '""', $data['placement']) . '","' . ($data['type'] ? $data['type'] : 'n/a') . '","' . str_replace('"', '""', $data['desc']) . '","' . str_replace('"', '""', str_replace(array(",http:", ",https:"), array("\nhttp:", "\nhttps:"), $data['info'])) . "\"\n";
    }

}

?>