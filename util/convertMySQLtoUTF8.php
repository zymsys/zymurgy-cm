<?php
/*
 * How do crazy character sets and collations find their way into my MySQL databases from time to time?
 * I have no idea, but this script sorts out the errors.
 *
 * Back-up first!  This may munge special characters.  Use at your own risk.
 */

require_once '../cmo.php';

Zymurgy::$db->run("ALTER DATABASE " . Zymurgy::$config['mysqldb'] . " CHARACTER SET utf8 COLLATE utf8_general_ci");
$tables = Zymurgy::$db->getAll("show full tables where Table_Type = 'BASE TABLE'");
foreach ($tables as $table) {
    $tableName = array_shift($table);
    Zymurgy::$db->run("ALTER TABLE $tableName CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
}
echo "Done UTF8 Conversion";