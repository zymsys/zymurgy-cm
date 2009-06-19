<?php
// ini_set("display_errors", 1);

$sql = "show tables";
$ri = mysql_query($sql) or die("Unable to show tables ($sql): ".mysql_error());
$etables = array();
while (($row = mysql_fetch_array($ri,MYSQL_NUM))!==false)
{
	$etables[$row[0]] = $row[0];
}

function CreateMissingTables()
{
	global $etables,$tables;
	$created = array();
	foreach($tables as $tname=>$sql)
	{
		if (!array_key_exists($tname,$etables))
		{
			$created[] = $tname;
			mysql_query($sql) or die("Unable to create $tname ($sql): ".mysql_error());
		}
	}
	return $created;
}

function RenameOldTables()
{
	global $etables,$tables;

	/* If we don't have zcm_passwd and we're trying to upgrade, we probably need to rename all the old tables. */
	if (!array_key_exists('zcm_passwd',$etables))
	{
		$map = array(
			'passwd'=>'zcm_passwd',
			'meta'=>'zcm_meta',
			'sitetext'=>'zcm_sitetext',
			'stcategory'=>'zcm_stcategory',
			'textpage'=>'zcm_textpage',
			'config'=>'zcm_config',
			'plugin'=>'zcm_plugin',
			'pluginconfig'=>'zcm_pluginconfig',
			'plugininstance'=>'zcm_plugininstance',
			'member'=>'zcm_member',
			'memberaudit'=>'zcm_memberaudit',
			'membergroup'=>'zcm_membergroup',
			'groups'=>'zcm_groups',
			'customtable'=>'zcm_customtable',
			'customfield'=>'zcm_customfield',
			'help'=>'zcm_help',
			'helpalso'=>'zcm_helpalso',
			'helpindex'=>'zcm_helpindex',
			'helpindexphrase'=>'zcm_helpindexphrase');
		foreach ($map as $oldname=>$newname)
		{
			if (array_key_exists($oldname,$etables))
			{
				$sql = "rename table $oldname to $newname";
				mysql_query($sql) or die("Can't rename table ($sql): ".mysql_error());
			}
		}
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
	$sql = "select id from zcm_plugin where name='".
		mysql_escape_string($plugin)."'";
	$ri = mysql_query($sql) or die("Can't get plugin ID ($sql): ".mysql_error());
	if (mysql_num_rows($ri)>0)
	{
		$pid = mysql_result($ri,0,0);
		mysql_free_result($ri);
		if ($pid === false) return; //Plugin not installed.  No problem, don't do anything.
		foreach($keynames as $oldname=>$newname)
		{
			$sql = "update zcm_pluginconfig set `key`='".
				mysql_escape_string($newname)."' where `key`='".
				mysql_escape_string($oldname)."' and plugin=$pid";
			mysql_query($sql) or die("Unable to rename key ($sql): ".mysql_error());
		}
	}
}

/**
 * Check $table to make sure there are indexes on all the columns listed in $indexes.
 *
 * @param string $table
 * @param string $indexes
 */
function CheckIndexes(
	$table,
	$indexes,
	$unique = false,
	$indexType = "")
{
	$existing = array();
	$sql = "show index from $table";
	$ri = mysql_query($sql) or die ("No such table: $table");

	while (($row = mysql_fetch_array($ri))!==FALSE)
	{
		$existing[$row['Key_name']] = isset($existing[$row["Key_name"]])
			? $existing[$row["Key_name"]].",".$row["Column_name"]
			: $row["Column_name"];
	}
	mysql_free_result($ri);

	// print_r($existing);

	foreach($indexes as $column)
	{
		$column = str_replace(" ", "", $column);
		// echo("------ $column<br>");

		if (!in_array($column,$existing))
		{
			if ($unique)
			{
				$sql = "alter table $table add unique($column)";
			}
			else
			{
				$sql = "alter table $table add $indexType index($column)";
			}
			mysql_query($sql) or die("Unable to index $column in $table: ".mysql_error().", $sql");
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
					mysql_query($sql) or die("Can't create column ($sql): ".mysql_error());
			}
			else
				mysql_query($colsql) or die("Can't create column ($colsql): ".mysql_error());
			//Special cases
			if (($table=='zcm_sitetext') && ($colname=='plainbody'))
			{
				$sql = "select id,body from zcm_sitetext";
				$ri = mysql_query($sql) or die("Can't get sitetext ($sql): ".mysql_error());
				while (($row = mysql_fetch_array($ri))!==false)
				{
					$sql = "update zcm_sitetext set plainbody='".
						mysql_escape_string(strip_tags($row['body']))."' where id={$row['id']}";
					mysql_query($sql) or die("Can't set plain text ($sql): ".mysql_error());
				}
			}
		}
	}
	return $cols;
}

/**
 * Check that columns exist by looking at the keys of $columns.
 *
 * ZK: This function performs the same function as CheckColumns(), but does not
 * require full ALTER TABLE statements to be passed in as parameters, and only
 * works on one column at a time. This makes the code calling this function much
 * easier to read.
 *
 * @param string $table
 * @param string $name
 * @param string $type
 * @param string $params
 */
function VerifyColumnExists(
	$table,
	$name,
	$type,
	$params)
{
	$fieldExists = false;

	$sql = "SHOW COLUMNS FROM `$table` LIKE '$name'";
	$ri = mysql_query($sql)
		or die("Table $table does not exist.");

	if(mysql_num_rows($ri) > 0)
	{
		$row = mysql_fetch_array($ri);

		if($row["Field"] == $name)
		{
			$fieldExists = true;
		}
	}

	if(!$fieldExists)
	{
		$sql = "ALTER TABLE `$table` ADD COLUMN `$name` $type $params";
		mysql_query($sql)
			or die("Could not add $name to $table: ".mysql_error().", $sql");
	}
	else
	{
		$existingFieldType = explode(" ", $row["Type"]);

		if(NormalizeFieldType($existingFieldType[0]) !== NormalizeFieldType($type))
		{
			$sql = "ALTER TABLE `$table` MODIFY COLUMN `$name` $type $params";
			mysql_query($sql)
				or die("Could not change $table.$name column type: ".mysql_error().", $sql");
		}
	}
}

function NormalizeFieldType($type)
{
	$type = strtoupper($type);

	$type = str_replace("INTEGER", "INT(10)", $type);

	return $type;
}

/**
 * Create a table field definition array. Used in the code that defines
 * the table structure of Zymurgy:CM components
 *
 * @param string $name
 * @param string $type
 * @param string $params
 * @return array
 */
function DefineTableField(
	$name,
	$type,
	$params = '')
{
	return array(
		"name" => $name,
		"type" => $type,
		"params" => $params);
}

/**
 * Create an index definition array. Used in the code that defines
 * the table structure of Zymurgy:CM components
 *
 * @param string $columns
 * @param boolean $unique
 * @param string $itype
 * @return array
 */
function DefineIndexField($columns,$unique = false,$itype='')
{
	return array(
		"columns" => $columns,
		"unique" => $unique,
		"type" => $itype
	);
}

/**
 * Create/alter the table structure in the database to match the given table
 * definitions.
 *
 * @param array $tableDefinitions
 */
function ProcessTableDefinitions(
	$tableDefinitions)
{
	if(!AreTableDefinitionsValid($tableDefinitions))
	{
		die("Errors found in table definition. Aborting.");
	}

	foreach($tableDefinitions as $tableDefinition)
	{
		if(strpos($_SERVER['REQUEST_URI'], "installer/upgrade.php") !== FALSE)
		{
			echo("---- ".$tableDefinition["name"]."<br>");
		}

		// If the table does not exist yet, generate and run the
		// CREATE TABLE statement for it.
		$createSQL = "CREATE TABLE IF NOT EXISTS `{0}` ( {1}, PRIMARY KEY ( {2} ) ) ENGINE = {3};";

		$createSQL = str_replace("{0}", $tableDefinition["name"], $createSQL);
		$createSQL = str_replace("{1}", GetAllColumnSQL($tableDefinition), $createSQL);
		$createSQL = str_replace("{2}", $tableDefinition["primarykey"], $createSQL);
		$createSQL = str_replace("{3}", $tableDefinition["engine"], $createSQL);

		mysql_query($createSQL)
			or die("Could not create table {$tableDefinition["name"]}: ".mysql_error().", $createSQL");

		// If the table already exists, make sure any fields that
		// have been defined since the last upgrade are properly
		// added
		foreach($tableDefinition["columns"] as $column)
		{
			VerifyColumnExists(
				$tableDefinition["name"],
				$column["name"],
				$column["type"],
				$column["params"]);
		}

		// Make sure any indexs that have been defined since the
		// last upgrade are properly added
		foreach($tableDefinition["indexes"] as $index)
		{
			CheckIndexes(
				$tableDefinition["name"],
				array($index["columns"]),
				$index["unique"],
				$index["type"]);
		}
	}
}

/**
 * Validate the given table definitions.
 *
 * @param array $tableDefinitions
 * @return boolean
 */
function AreTableDefinitionsValid(
	$tableDefinitions)
{
	$tableIndex = 0;
	$isValid = true;

	foreach($tableDefinitions as $tableDefinition)
	{
		if(!array_key_exists("name", $tableDefinition))
		{
			echo("-- name key missing for Table Definition $tableIndex<br>");
			$isValid = false;
		}

		if(!array_key_exists("columns", $tableDefinition))
		{
			echo("-- columns key missing for Table Definition $tableIndex<br>");
			$isValid = false;
		}
		else if(!is_array($tableDefinition["columns"]))
		{
			echo("-- columns key must be array for Table Definition $tableIndex<br>");
			$isValid = false;
		}
		else
		{
			$columnIndex = 0;

			foreach($tableDefinition["columns"] as $column)
			{
				if(!array_key_exists("name", $column))
				{
					echo("-- name key missing for Column $columnIndex in Table Definition $tableIndex<br>");
					$isValid = false;
				}

				if(!array_key_exists("type", $column))
				{
					echo("-- type key missing for Column $columnIndex in Table Definition $tableIndex<br>");
					$isValid = false;
				}

				if(!array_key_exists("params", $column))
				{
					echo("-- params key missing for Column $columnIndex in Table Definition $tableIndex<br>");
				}

				$columnIndex++;
			}
		}

		if(!array_key_exists("indexes", $tableDefinition))
		{
			echo("-- indexes key missing for Table Definition $tableIndex<br>");
			$isValid = false;
		}
		else if(!is_array($tableDefinition["indexes"]))
		{
			echo("-- indexes key must be an array for Table Definition $tableIndex<br>");
			$isValid = false;
		}
		else
		{
			$indexIndex = 0;

			foreach($tableDefinition["indexes"] as $index)
			{
				if(!array_key_exists("columns", $index))
				{
					echo("-- columns key missing for Index $indexIndex in Table Definition $tableIndex<br>");
					$isValid = false;
				}

				if(!array_key_exists("unique", $index))
				{
					echo("-- unique key missing for Index $indexIndex in Table Definition $tableIndex<br>");
					$isValid = false;
				}

				if(!array_key_exists("type", $index))
				{
					echo("-- type key missing for Index $indexIndex in Table Definition $tableIndex<br>");
					$isValid = false;
				}

				$indexIndex++;
			}
		}

		if(!array_key_exists("primarykey", $tableDefinition))
		{
			echo("-- primarykey key missing for Table Definition $tableIndex<br>");
			$isValid = false;
		}

		if(!array_key_exists("engine", $tableDefinition))
		{
			echo("-- engine key missing for Table Definition $tableIndex<br>");
			$isValid = false;
		}

		$tableIndex++;
	}

	return $isValid;
}

/**
 * Get the full SQL for the column definitions in the given table definition.
 * This is used by ProcessTableDefinitions() to build the CREATE TABLE statement.
 *
 * @param array $tableDefinition
 * @return string
 */
function GetAllColumnSQL(
	$tableDefinition)
{
	$columnSQL = array();

	foreach($tableDefinition["columns"] as $column)
	{
		$columnSQL[] = "`{$column["name"]}` {$column["type"]} {$column["params"]}";
	}

	return implode(", ", $columnSQL);
}

?>