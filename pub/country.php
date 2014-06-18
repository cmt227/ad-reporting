<?php
require("../stdlib.php");

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die("invalid country");
}
$countryID = $_GET['id'];

if (empty($_GET['date']) || !is_numeric($_GET['date'])) {
    $date = date("Ymd");
} else {
    $date = $_GET['date'];
}

if (empty($_GET['endDate']) || !is_numeric($_GET['endDate'])) {
    $endDate = $date;
} else {
    $endDate = $_GET['endDate'];
}

if ($endDate < $date) {
    $tDate = $date;
    $date = $endDate;
    $endDate = $tDate;
}

$mongo = GMongo::getInstance(GMongo::CLUSTER_PLAYLISTS);
$db = $mongo->selectDB("reportAds");
$coll = $db->selectCollection("counts");
$coll->setSlaveOkay(true);

if ($endDate == $date) {
    $date1 = date("Ymd", strtotime("-5 days", strtotime($date)));
    if ($endDate != date("Ymd")) {
        $date2 = date("Ymd", strtotime("+5 days", strtotime($date)));
    } else {
        $date2 = $endDate;
    }
} else {
    $date1 = $date;
    $date2 = $endDate;
}
$placementsData = array();
$typeData = array();
$itemData = array();
$counts = array();
$days = array();

function sortByToday($a, $b)
{
    global $date2;
    if (!isset($a[$date2]) && !isset($b[$date2])) {
        return 0;
    } else if (!isset($a[$date2])) {
        return 1;
    } else if (!isset($b[$date2])) {
        return -1;
    } else {
        return $b[$date2] - $a[$date2];
    }
}

$dates = $coll->find(array('_id' => array('$gt' => $date1, '$lte' => $date2)), array("c.$countryID" => 1, "c:$countryID.p" => 1, "c:$countryID.t" => 1, "c:$countryID.i" => 1)); //this sucks, we have to fetch all the docs, instead of using key regexes

foreach($dates as $dt => $dateData) {
    foreach($dateData as $key => $cData) {
        switch ($key) {
            case "_id":
                continue;
            case "c":
                $counts[$dt] = isset($cData[$countryID]) ? $cData[$countryID] : 0;
                break;
            case "c:$countryID":
                foreach ($cData['p'] as $placement => $count) {
                    if (!isset($placementsData[$placement])) {
                        $placementsData[$placement] = array();
                    }
                    $placementsData[$placement][$dt] = $count;
                }
                if (isset($cData['t'])) {
                    foreach ($cData['t'] as $type => $count) {
                        if (!isset($typeData[$type])) {
                            $typeData[$type] = array();
                        }
                        $typeData[$type][$dt] = $count;
                    }
                }
                if (isset($cData['i'])) {
                    foreach ($cData['i'] as $item => $count) {
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

uasort($placementsData, 'sortByToday');
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
    function inputKeyPress(e) {
        if (e.keyCode == 13) {
            if (e.currentTarget.value == "") {
                window.location.replace("/country.php?id=<?= $countryID ?>&date=<?= $date ?>");
                return;
            }
            var date = parseInt(e.currentTarget.value, 10);
            if (!date || date < 20120101) {
                alert("Invalid date!");
            } else {
                window.location.replace("/country.php?id=<?= $countryID ?>&date=<?= $date ?>&endDate=" + date);
            }
        }
    }
</script>

<a href="/">Homepage</a> | <a href="<?= $_SERVER['REQUEST_URI'] ?>&export">Export</a> | <a href="<?= $_SERVER['REQUEST_URI'] ?>&exportDomains">Export Domains</a> | <a href="<?= $_SERVER['REQUEST_URI'] ?>&exportAdTags">Export Ad Tags</a> | End Date: <input type="text" onkeypress="return inputKeyPress(event)" placeholder="yyyymmdd" value="<?= ($date == $endDate ? '' : $endDate) ?>" />

<h1>Historical Counts</h1>
<table>
    <thead>
        <tr>
            <?= "<th style='width:70px;'>" . implode("</th><th style='width:70px;'>", $days) . "</th>" ?>
        </tr>
    </thead>
    <tbody>
    <tr>
    <?php
        foreach ($days as $day) {
            echo "<td style='text-align:center'><a href='/country.php?id=$countryID&date=$day'>" . (isset($counts[$day]) ? $counts[$day] : 0) . "</a></td>";
        }
    ?></tr>
    </tbody>
</table>

<div style="float:left; margin-right:100px;">
    <h1>Placement Data</h1>
    <table>
        <thead>
            <tr>
                <th>Placement</th>
                <?= "<th style='width:70px;'>" . implode("</th><th style='width:70px;'>", $days) . "</th>" ?>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach($placementsData as $placement => $data) {
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

<h1>User Provided/Click Data</h1>
<?php

$nDate = $date;
$dates = array();
while ($nDate <= $endDate) {
    $dates[] = $nDate;
    $nDate = date("Ymd", strtotime("+1 days", strtotime($nDate)));
}

$lines = array();
foreach ($dates as $ndate) {
    $file = file_get_contents("http://reportadsfiles.in.escapemg.com/adDesc-$ndate");
    $file = str_replace("<br />\n", "<br />", $file); //for for stupid nl2br
    $nLines = explode("\n", $file);
    if (is_array($nLines)) {
        $lines += $nLines;
    }
}
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
        $s = '';
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
    
    if ($c == $countryID) {
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
    die("<h2>No data</h2>");
}
$info = array_reverse($info); //newest first
arsort($domainsToBlock);
arsort($domains);
$allDomains = $domains;
$domains = array_slice($allDomains, 0, 20);
?>
<div style="float:left;margin-right: 100px;">
    <h2>Top 20 Domains/Networks</h2>
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

<div style="float:left;">
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
            <th>Placement</th>
            <th style="width: 800px">Click Info</th>
            <th>Type</th>
            <th>Ad Unit</th>
            <th style="width: 400px">Description</th>
        </tr>
    </thead>
    <tbody>
    <?php
        foreach($info as $data) {
            echo "<tr>
                <td>" . date("Y-m-d H:i", $data['time']) . (!empty($data['user']) ? " - <a style=\"cursor:pointer\" onclick=\"return getUser('" . $data['user'] . "');\"><u>User</u></a></td>" : "") . "
                <td>" . htmlspecialchars($data['placement']) . "</td>
                <td style='font-size:11px; width: 600px; word-wrap:break-word; white-space:normal; word-break:break-all; border-bottom:1px #000 solid;'>" . urldecode( preg_replace("/adurl\=http(?:[^\&]+|$)/", "<b>\\0</b>", str_replace(array(",http:", ",https:"), array("<br>http:", "<br>https:"), htmlspecialchars($data['info'])))) . "</td>
                <td><b>" . $data['type'] . "</b></td>
                <td><b>" . $data['itemID'] . "</b></td>
                <td style='width: 400px;border-left: 1px #000 solid;word-break: break-all;word-wrap: break-word;'><b>" . htmlspecialchars($data['desc']) . "</b></td>
                </tr>";
        }
    ?>
    </tbody>
</table>
<?php
if (!isset($_GET['export']) && !isset($_GET['exportDomains']) && !isset($_GET['exportAdTags'])) {
    ob_end_flush();
    exit;
}

ob_end_clean();

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
header("Content-Description: File Transfer");
if (isset($_GET['exportDomains'])) {
    if ($endDate != $date) {
        header("Content-Disposition: attachment; filename=\"country-$countryID-$date-$endDate-domains.csv\"");
    } else {
        header("Content-Disposition: attachment; filename=\"country-$countryID-$date-domains.csv\"");
    }
} elseif (isset($_GET['exportAdTags'])) {
    if ($endDate != $date) {
        header("Content-Disposition: attachment; filename=\"country-$countryID-$date-$endDate-tags.csv\"");
    } else {
        header("Content-Disposition: attachment; filename=\"country-$countryID-$date-tags.csv\"");
    }
} else {
    if ($endDate != $date) {
        header("Content-Disposition: attachment; filename=\"country-$countryID-$date-$endDate.csv\"");
    } else {
        header("Content-Disposition: attachment; filename=\"country-$countryID-$date.csv\"");
    }
}
header("Content-Type: text/csv");
header("Content-Transfer-Encoding: binary");


if (isset($_GET['exportDomains'])) {
    echo '"Domain","Count"' . "\n";

    foreach($allDomains as $domain => $count) {
        echo '"' . str_replace('"', '""', $domain) . '",' . $count . "\n";
    }
} elseif (isset($_GET['exportAdTags'])) {
    echo '"Ad Unit","Count"' . "\n";

    foreach($itemData as $item => $count) {
        if (!empty($count[$date])) {
            echo '"' . str_replace('"', '""', $item) . '",' . $count[$date] . "\n";
        }
    }

} else {

    echo '"Time","Placement","Click Info","Type","User Description"' . "\n";

    foreach($info as $data) {
        echo '"' . date("Y-m-d H:i", $data['time']) . '","' . str_replace('"', '""', $data['placement']) . '","' . ($data['type'] ? $data['type'] : 'n/a') . '","' . str_replace('"', '""', $data['desc']) . '","' . str_replace('"', '""', str_replace(array(",http:", ",https:"), array("\nhttp:", "\nhttps:"), $data['info'])) . "\"\n";
    }

}

?>
