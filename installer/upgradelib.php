<?php
$sql = "show tables";
$ri = mysql_query($sql) or die("Unable to show tables ($sql): ".mysql_error());
$etables = array();
while (($row = mysql_fetch_array($ri,MYSQL_NUM))!==false)
{
	$etables[$row[0]] = $row[0];
}

foreach($tables as $tname=>$sql)
{
	if (!array_key_exists($tname,$etables))
	{
		mysql_query($sql) or die("Unable to create $tname ($sql): ".mysql_error());
	}
}

/**
 * Rename keys of a named plugin
 *
 * @param string $plugin Name of plugin
 * @param array $keynames Keys are old names, values are new names
 */
function RenamePluginKeys($plugin,$keynames)
{
	$sql = "select id from plugin where name='".
		mysql_escape_string($plugin)."'";
	$ri = mysql_query($sql) or die("Can't get plugin ID ($sql): ".mysql_error());
	if (mysql_num_rows($ri)>0)
	{
		$pid = mysql_result($ri,0,0);
		mysql_free_result($ri);
		if ($pid === false) return; //Plugin not installed.  No problem, don't do anything.
		foreach($keynames as $oldname=>$newname)
		{
			$sql = "update pluginconfig set `key`='".
				mysql_escape_string($newname)."' where `key`='".
				mysql_escape_string($oldname)."' and plugin=$pid";
			mysql_query($sql) or die("Unable to rename key ($sql): ".mysql_error());
		}
	}
}

/**
 * Check that columns exist by looking at the keys of $columns.  If a column
 * is found to be missing, execute the SQL statement(s) of the value.  The
 * value may be a string (SQL statement) or an array of SQL statement strings.
 *
 * @param string $table
 * @param array $columns
 */
function CheckColumns($table,$columns)
{
	$sql = "show columns from $table";
	$ri = mysql_query($sql) or die ("No such table: $table");
	$cols = array();
	while (($row = mysql_fetch_array($ri))!==false)
	{
		$cols[$row['Field']] = $row['Type'];
	}
	mysql_free_result($ri);
	foreach ($columns as $colname=>$colsql)
	{
		if (!array_key_exists($colname,$cols))
		{
			//Create new column
			if (is_array($colsql))
			{
				foreach($colsql as $sql)
					mysql_query($sql);
			}
			else 
				mysql_query($colsql);
			//Special cases
			if (($table=='sitetext') && ($colname=='plainbody'))
			{
				$sql = "select id,body from sitetext";
				$ri = mysql_query($sql) or die("Can't get sitetext ($sql): ".mysql_error());
				while (($row = mysql_fetch_array($ri))!==false)
				{
					$sql = "update sitetext set plainbody='".
						mysql_escape_string(strip_tags($row['body']))."' where id={$row['id']}";
					mysql_query($sql) or die("Can't set plain text ($sql): ".mysql_error());
				}
			}
		}
	}
}
?>