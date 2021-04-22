<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/tools.php";

db_connect($db);

// This may take a long time depending on the size of your log files,
// so we tell PHP not to limit the time of execution.
set_time_limit(0);

$myadate = array();
$mydate[] = date("Y-m-d", mktime (0,0,0,date("m"),date("d"),  date("Y")));
$mydate[] = date("Y-m-d", mktime (0,0,0,date("m"),date("d")-1,  date("Y")));

// Loop through the log files specified in config.php
// TO DO: (there are no log files specified in config.php anymore)
for ($k = 0; $k < count($log_file); $k++) {
    // Open the log file
    $file = fopen ( $log_file[$k], "r");
    if (!$file) {
        die ("Unable to open file {$log_file[$k]}\nExiting ............");
    } else {
        echo "Processing file {$log_file[$k]}\n-------------------------\n";
    }

    // Loop through the file
    while (!feof ($file)) {
        $line = fgets ($file, 1024);

        // Look for the time stamp. Time stamp is actually current date
        // which is logged only when FlexLM starts the log or at midnight.
        if (preg_match ("/TIMESTAMP (.*)/i", $line, $out2)) {
            $timestamp_date =  convert_to_mysql_date($out2[1]);
        }

        # if ($timestamp_date && in_array($timestamp_date, $mydate) && eregi('(.*) \((.*)\) DENIED: "(.*)" (.*)  (.*)', $line, $out2)) {
        if (isset($timestamp_date) && preg_match('/(.*) \((.*)\) (IN:|OUT:|DENIED:) "(.*)" (.*)/i', $line, $out2)) {

            // Strip :
            $log_event = substr($out2[3],0, strpos($out2[3],":"));
            // Strip username from the username@hostname
            $username = substr($out2[5],0, strpos($out2[5],"@"));

            preg_match('/(.*) \((.*)\) (OUT:|DENIED:) "(.*)" (.*)  (.*)/i', $line, $out3);

            if (!isset($out3[6]))
                $out2[6] = "";
            else
                $out2[6] = $out3[6];
            unset($out3);

            $log_reason = trim($out2[6]);
            $feature = trim($out2[4]);
            $sql = <<<SQL
INSERT IGNORE INTO `events` (`license_id`, `time`, `user`, `type`, `reason`)
SELECT `licenses`.`id`, {$timestamp_date}, '{$username}', '{$log_event}', '{$log_reason}')
FROM `licenses`
JOIN `features` ON `licenses`.`feature_id`=`features`.`id`
WHERE `features`.`name`='{$feature}';
SQL;

            unset($out2);

            if (isset($debug) && $debug == 1)
            	print_sql ($sql);

            $result = $db->query($sql);

            if (!$result) {
                die ($db->error);
            }
        }
    }

    // Close the log file
    fclose($file);
}

$db->close();

?>