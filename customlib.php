<?
/**
 * Provides common items for customtable.php and customfield.php
 */

$reserved = array('ADD','ANALYZE','ASC','BETWEEN','BLOB','CALL','CHANGE','CHECK','CONDITION','CONVERT','CURRENT_DATE','CURRENT_USER','DATABASES','DAY_MINUTE',
	'DECIMAL','DELAYED','DESCRIBE','DISTINCTROW','DROP','ELSE','ESCAPED','EXPLAIN','FLOAT','FOR','FROM','GROUP','HOUR_MICROSECOND','IF','INDEX','INOUT','INT',
	'INT3','INTEGER','IS','KEY','LEADING','LIKE','LOAD','LOCK','LONGTEXT','MATCH','MEDIUMTEXT','MINUTE_SECOND','NATURAL','NULL','OPTIMIZE','OR','OUTER','PRIMARY',
	'READ','REFERENCES','RENAME','REQUIRE','REVOKE','SCHEMA','SELECT','SET','SONAME','SQL','SQLWARNING','SQL_SMALL_RESULT','STRAIGHT_JOIN','THEN','TINYTEXT','TRIGGER',
	'UNION','UNSIGNED','USE','UTC_TIME','VARBINARY','VARYING','WHILE','XOR','ASENSITIVE','CONTINUE','DETERMINISTIC','EXIT','INSENSITIVE','LOOP','READS','RETURN',
	'SENSITIVE','SQLEXCEPTION','TRIGGER','ALL','AND','ASENSITIVE','BIGINT','BOTH','CASCADE','CHAR','COLLATE','CONSTRAINT','CREATE','CURRENT_TIME','CURSOR','DAY_HOUR',
	'DAY_SECOND','DECLARE','DELETE','DETERMINISTIC','DIV','DUAL','ELSEIF','EXISTS','FALSE','FLOAT4','FORCE','FULLTEXT','HAVING','HOUR_MINUTE','IGNORE','INFILE',
	'INSENSITIVE','INT1','INT4','INTERVAL','ITERATE','KEYS','LEAVE','LIMIT','LOCALTIME','LONG','LOOP','MEDIUMBLOB','MIDDLEINT','MOD','NOT','NUMERIC','OPTION','ORDER',
	'OUTFILE','PROCEDURE','READS','REGEXP','REPEAT','RESTRICT','RIGHT','SCHEMAS','SENSITIVE','SHOW','SPATIAL','SQLEXCEPTION','SQL_BIG_RESULT','SSL','TABLE','TINYBLOB',
	'TO','TRUE','UNIQUE','UPDATE','USING','UTC_TIMESTAMP','VARCHAR','WHEN','WITH','YEAR_MONTH','CALL','CURSOR','EACH','FETCH','ITERATE','MODIFIES','RELEASE','SCHEMA',
	'SPECIFIC','SQLSTATE','UNDO','ALTER','AS','BEFORE','BINARY','BY','CASE','CHARACTER','COLUMN','CONTINUE','CROSS','CURRENT_TIMESTAMP','DATABASE','DAY_MICROSECOND',
	'DEC','DEFAULT','DESC','DISTINCT','DOUBLE','EACH','ENCLOSED','EXIT','FETCH','FLOAT8','FOREIGN','GRANT','HIGH_PRIORITY','HOUR_SECOND','IN','INNER','INSERT','INT2',
	'INT8','INTO','JOIN','KILL','LEFT','LINES','LOCALTIMESTAMP','LONGBLOB','LOW_PRIORITY','MEDIUMINT','MINUTE_MICROSECOND','MODIFIES','NO_WRITE_TO_BINLOG','ON',
	'OPTIONALLY','OUT','PRECISION','PURGE','REAL','RELEASE','REPLACE','RETURN','RLIKE','SECOND_MICROSECOND','SEPARATOR','SMALLINT','SPECIFIC','SQLSTATE',
	'SQL_CALC_FOUND_ROWS','STARTING','TERMINATED','TINYINT','TRAILING','UNDO','UNLOCK','USAGE','UTC_DATE','VALUES','VARCHARACTER','WHERE','WRITE','ZEROFILL',
	'CONDITION','DECLARE','ELSEIF','INOUT','LEAVE','OUT','REPEAT','SCHEMAS','SQL','SQLWARNING','WHILE','ID');

function tableparents($tid)
{
	$r = array();
	do
	{
		$tbl = Zymurgy::$db->get("select * from zcm_customtable where id=$tid");
		$r[$tbl['id']] = empty($tbl['navname']) ? $tbl['tname'] : $tbl['navname'];
		$tid = $tbl['detailfor'];
	}
	while ($tid > 0);
	return $r;
}

function tablecrumbs($tid)
{
	global $crumbs;

	$parents = tableparents($tid);
	$tids = array_keys($parents);
	while (count($parents) > 0)
	{
		$tid = array_pop($tids);
		$crumb = array_pop($parents).' Detail Tables';
		$crumbs["customtable.php?d=$tid"] = htmlspecialchars($crumb);
	}	
}
	
function okname($name)
{
	global $reserved;
	$name = strtoupper($name);
	$r = array_search($name,$reserved);
	if ($r===true)
		return "That name is a reserved word.  Please choose a different table name.";
	$m = preg_match("/^[A-Za-z][\w]*\$/",$name);
	//$m = preg_match("/./",$name);
	if ($m==0)
		return "Please use only alphanumeric characters and only alpha for the first character.";
	/*Gave up on making the datagrid component compatible with stupid table/column names.  Use above alphanumeric test instead.
	if (strpos($name,'`')!==false)
		return "That name contains a back-tick (`).  Please choose a different table name.";*/
	return true;
}

function gettable($t)
{
	$sql = "select * from zcm_customtable where id=$t";
	$ri = mysql_query($sql) or die("Can't get table ($sql): ".mysql_error());
	$tbl = mysql_fetch_array($ri);
	if (!is_array($tbl)) 
		die("No such table ($t)");
	return $tbl;
}

function inputspec2sqltype($inputspec)
{
	list($type,$params) = explode('.',$inputspec,2);
	$pp = explode('.',$params);
	switch($type)
	{
		case "datetime":
		case "date":
			return $type;
			break;
		case "money":
		case "unixdatetime":
		case "unixdate":
			return 'int unsigned';
			break;
		case "numeric":
		case "lookup":
			return 'bigint unsigned';
			break;
		case "float":
			return 'float';
			break;	
		case "attachment":
		case "image":
			return 'varchar(60)';
			break;
		case "drop":
		case "radio":
			$ritems = ZIW_RadioDrop::HackedUnserialize($params);
			$maxsz = 0;
			foreach ($ritems as $value)
			{
				if (strlen($value) > $maxsz)
					$maxsz = strlen($value);
			}
			return "varchar($maxsz)";
			break;
		case "input":
		case "password":
			return "varchar({$pp[1]})";
			break;
		case "textarea":
		case "html":
			return 'longtext';
			break;
		case "colour":
		case "color":
			return 'varchar(6)';
			break;
		default:
			return 'text';
			break;
	}
}

?>