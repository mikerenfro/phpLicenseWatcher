<?php
// Config file for Vagrant VM
$lmutil_loc="/opt/flexnetserver/lmutil";
$lmstat_loc="{$lmutil_loc} lmstat";
$cache_dir="/var/cache/phplw/";
$notify_address="";
$lead_time=30;
$disable_autorefresh=0;
$disable_license_removal=1;
$collection_interval=10;

$db_type="mysqli";
$db_hostname="localhost";
$db_username="vagrant";
$db_password="vagrant";
$db_database="vagrant";
$dsn="{$db_type}://{$db_username}:{$db_password}@{$db_hostname}/{$db_database}";

$colors="#ffffdd,#ff9966,#ffffaa,#ccccff,#cccccc,#ffcc66,#99ff99,#eeeeee,#66ffff,#ccffff,#ffff66,#ffccff,#ff66ff,yellow,lightgreen,lightblue";
$smallgraph="100,100";
$largegraph="300,200";
$legendpoints="";

// IMPORTANT: Change this to 0 when used in production!
$debug = 1;

/* List your license servers below.  Don't forget the square braces as these
 * are parallel arrays.  You can list multiple servers this way.
 * $servers[] is a connection string in the form of {port}@{address}
 *     e.g. "20025@licenses.subdomain.myuniversity.edu"
 * $description[] could be used to note what license is being managed.
 * $log_file[] can be left as an empty string if you don't want a log file.
 */
$servers[] = "";
$description[] = "";
$log_file[] = "";

?>