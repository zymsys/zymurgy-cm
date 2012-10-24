<?php
/**
 * The zcm_flavourtextitem has been missing a unique contraint on the zcm_flavourtext and flavour columns,
 * and without it the INSERT / ON DUPLICATE KEY statement in ZIW_Base::StoreFlavouredValue function stores
 * duplicate rows instead of updating existing rows.  Then only one row is used, so often it appears that
 * flavoured text isn't updating on save at all.
 *
 * This script reads the zcm_flavourtextitem table and deletes the duplicate rows.  The installer/upgrade.php
 * script should add the missing constraint in corrected versions of Zymurgy:CM.
 */

require_once '../cmo.php';

$keys = array();
$duplicates = array();
$ri = Zymurgy::$db->run("SELECT * FROM `zcm_flavourtextitem` ORDER BY `id` DESC");
while (($row = Zymurgy::$db->fetch_array($ri))!==false)
{
    $uniqueKey = $row['zcm_flavourtext'] . ':' . $row['flavour'];
    if (isset($keys[$uniqueKey]))
    {
        $duplicates[] = $row['id'];
    }
    else
    {
        $keys[$uniqueKey] = $row['id'];
    }
}
Zymurgy::$db->free_result($ri);

$sql = "DELETE FROM `zcm_flavourtextitem` WHERE `id` IN (" . implode(', ', $duplicates) . ")";
Zymurgy::$db->run($sql);

