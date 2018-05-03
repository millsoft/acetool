<?php
namespace Millsoft\AceTool;


/**
 * Test Date Time
 */

require_once(__DIR__ . "/../vendor/autoload.php");

function tzList()
{
    $tzMap = array();
    $zones = \DateTimeZone::listIdentifiers();
    foreach ($zones as $zone) {
        $tz = new \DateTimeZone($zone);
        $now = new \DateTime("utc", $tz);
        $diffInSeconds = $tz->getOffset($now);
        $hours = floor($diffInSeconds / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);
        $tzMap[$zone] = sprintf("%+d", $hours) . ":" . sprintf("%02d", $minutes);
    }
    return $tzMap;
}


$tzlist = tzList();
//print_r($tzlist);
//die();


$v = '/Date(1525389502970)/';
$v = Helper::getDate($v, true);



$t = 1525389502970;
//$t = ($t / 1000)-(3600*6);
//$t = ($t / 1000);
$t = substr($t,0, 10);

$d = date("Y-m-d H:i:s", $t);

$timezone = new \DateTimeZone("Asia/Omsk");
$local_time = new \DateTime($d, null);

//$company_time = $local_time->setTimezone(new \DateTimeZone("Asia/Aqtau"));

$company_time = $local_time->sub(new \DateInterval('PT6H'));

//$company_time = $local_time->setTimezone(new \DateTimeZone("Asia/Aqtau"));

print_r($company_time);
die();

$ist_date = DateTime::createFromFormat(
    '"Y/m/d g:i A"',
    $current_date,
    new DateTimeZone('Asia/Calcutta')
);

print_r($d);
