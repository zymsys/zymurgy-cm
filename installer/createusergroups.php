<?php
	/**
	 * Creates a group for each member in the Membership database.
	 *
	 * @package Zymurgy
	 * @subpackage installer
	 */

	include("../cmo.php");

	$memberProvider = Zymurgy::initializemembership();

	$sql = "SELECT `id`, `username` FROM `zcm_member`";
	$ri = Zymurgy::$db->query($sql)
		or die("Could not retrieve list of users: ".Zymurgy::$db->error().", $sql");

	while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
	{
		echo($row["username"]."<br>");

		$sql = "SELECT `id` FROM `zcm_groups` WHERE `name` = '".
			Zymurgy::$db->escape_string($row["username"]).
			"'";
		$groupID = Zymurgy::$db->get($sql);

		if($groupID > 0)
		{
			echo("-- Group for user found.<br>");
		}
		else
		{
			echo("-- Group for user not found. Creating.<br>");
		}
	}

	Zymurgy::$db->free_result($ri);
?>