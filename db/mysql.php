<?
/**
 * Containd Zymrgy_DB wrapper for MySQL.
 *
 * @access private
 * @package
 */

define('ZYMURGY_FETCH_ASSOC',MYSQL_ASSOC);
define('ZYMURGY_FETCH_BOTH',MYSQL_BOTH);
define('ZYMURGY_FETCH_NUM',MYSQL_NUM);

//require_once('../cmo.php');

/**
 * Wrapper for MySQL DB interface.
 *
 * This class will auto-connect to the database specified in config.php using the username and password there.
 *
 * <b>DO NOT create an instance of this class yourself, always use {@link Zumurgy::$db} instead.</b>
 *
 * @package Zymurgy
 * @subpackage base
 */
class Zymurgy_DB
{
	var $link;

	/**
	 * Create a new connection.
	 *
	 * Do not call this, use {@link Zumurgy::$db} instead.
	 *
	 * @ignore
	 */
	public function __construct()
	{
		$this->link = mysql_connect(Zymurgy::$config['mysqlhost'],Zymurgy::$config['mysqluser'],Zymurgy::$config['mysqlpass']);
		mysql_select_db(Zymurgy::$config['mysqldb'],$this->link) or die ("Unable to select the database ".Zymurgy::$config['mysqldb'].".");
	}

	/**
	 * Run an SQL query on the dtatbase and return the result.
	 *
	 * Returns false on error.
	 *
	 * @see PHP_MANUAL#mysql_query
	 *
	 * @param string $sql The query to run
	 * @return resource|false MySQL result set or false on error
	 */
	public function query($sql)
	{
		//echo "<div>[$sql]</div>";
		return mysql_query($sql,$this->link);
	}

	/**
	 * Run query and give an error message if there's a problem.
	 *
	 * Returns a result identifier resource. Script ends on error.
	 *
	 * @todo should this use an exception instead of killing the script?
	 *
	 * @see query()
	 *
	 * @param string $sql The query to run
	 * @param string $errormsg The error message to show in case of error, or false to throw an exception.
	 * @return resource MySQL result set
	 */
	public function run($sql, $errormsg = 'Unable to run query')
	{
		// should this throw an exception instead?
		$ri = $this->query($sql);
		if (!$ri)
		{
			$backtrace = debug_backtrace();
			do 
			{
				$bt = array_shift($backtrace);
			} while ($bt && (substr($bt['file'], -21) == '/zymurgy/db/mysql.php'));
			if ($errormsg === false)
			{
				throw new Exception($this->error()." in ".$bt['file']." on line ".$bt['line'], 0);
			}
			else 
			{
				die ("$errormsg ($sql): ".$this->error()." in ".$bt['file']." on line ".$bt['line']."<!--\n".print_r(debug_backtrace(),true)."\n-->");
			}
		}
		return $ri;
	}
	
	/**
	 * Return value of request named $name, escaped for the character set of the current connection.
	 * If $name isn't in the request then either throw an exception or return the escaped value of
	 * $default if it was provided.
	 * 
	 * @param string $name
	 * @param string $default
	 * @return string
	 * @throws Exception
	 */
	public function request($name, $default = false)
	{
		if (array_key_exists($name, $_REQUEST))
		{
			return $this->escape_string($_REQUEST[$name]);
		}
		else 
		{
			if ($default === false)
			{
				throw new Exception("Can't find $name in request.", 0);
			}
			else 
			{
				return $this->escape_string($default);
			}
		}
	}
	
	/**
	 * Take an SQL template and replace values from $_REQUEST; values are escaped according to the current 
	 * connection's character set.  All values are quoted.
	 * Example: example.php?id=5&name=Foo
	 * sql_template: UPDATE `bar` SET `name`=[name] WHERE `id`=[id]
	 * returns: UPDATE `bar` SET `name`='Foo' WHERE `id`='5'
	 * 
	 * @param string $sql_template
	 * @return string
	 */
	public function requestsql($sql_template)
	{
		$replmap = array();
		foreach(array_keys($_REQUEST) as $key)
		{
			$replmap["[$key]"] = "'".$this->escape_string($_REQUEST[$key])."'";
		}
		return str_replace(array_keys($replmap), array_values($replmap), $sql_template);
	}

	/**
	 * Get a single row or value from a query.
	 *
	 * Run a query and throw an error if there's a problem.  Return the first row as an array or
	 * the value if only one column is returned.  Returns false if no data is returned.
	 *
	 * @see query()
	 *
	 * @param string $sql
	 * @param string $errormsg
	 * @return mixed
	 */
	public function get($sql, $errormsg = 'Unable to run query')
	{
		$ri = $this->run($sql,$errormsg);
		$row = $this->fetch_array($ri);
		if ($this->num_fields($ri)==1)
		{
			return $row[0];
		}
		mysql_free_result($ri);
		return $row;
	}

	/**
	 * Return the number of fields in a result set.
	 *
	 * @see PHP_MANUAL#mysql_num_fields()
	 *
	 * @param resource $ri the result set
	 * @return int
	 */
	public function num_fields($ri)
	{
		return mysql_num_fields($ri);
	}

	/**
	 * Fetch the next row from a result set.
	 *
	 * Returns false if there are no more rows left.
	 * Call this repeatedly until it returns false to fetch the entire result set.
	 *
	 * Example:<code>
	 * $result = Zymurgy::$db->run('SELECT field1,field2 FROM table');
	 * while ($row = Zymurgy::$db->fetch_array($result)){
	 *     // do something with $row
	 * }
	 * Zymurgy::$db->free_result($result);
	 * </code>
	 *
	 * @see PHP_MANUAL#mysql_fetch_array
	 *
	 * @param resource $result MySQL result set
	 * @param int $result_type
	 * @return array|false array(fieldname => value, column# => value )
	 */
	public function fetch_array($result, $result_type = ZYMURGY_FETCH_BOTH)
	{
		if (!is_resource($result))
		{
			echo "<div>[$result] is not a valid mysql resource.</div>";
			debug_print_backtrace();
			exit;
		}
		return mysql_fetch_array($result,$result_type);
	}

	public function fetch_assoc($result)
	{
		return mysql_fetch_assoc($result);
	}

	/**
	 * Fetch the next row from a result set and return a numeric array.
	 *
	 * Equivalant to {@link fetch_array}($result, ZYMURGY_FETCH_NUM)
	 *
	 * @see PHP_MANUAL#mysql_fetch_row
	 *
	 * @param resource $result MySQL result set
	 * @return array
	 */
	public function fetch_row($result)
	{
		return mysql_fetch_row($result);
	}

	/**
	 * Fetch the next row from a result set and return an object.
	 *
	 * @see PHP_MANUAL#mysql_fetch_object
	 *
	 * @param resource $result MySQL result set
	 * @return object
	 */
	public function fetch_object($result)
	{
		return mysql_fetch_object($result);
	}

	/**
	 * Return the number of rows in the a result set
	 *
	 * @see PHP_MANUAL#mysql_num_rows
	 *
	 * @param resource $result MySQL result set
	 * @return int
	 */
	public function num_rows($result)
	{
		return mysql_num_rows($result);
	}

	/**
	 * Delete a result set.  Call this function on a result set when you're done with it.
	 *
	 * @see PHP_MANUAL#mysql_free_result
	 *
	 * @param resource $result MySQL result set
	 */
	public function free_result($result)
	{
		return mysql_free_result($result);
	}

	/**
	 * Return a single cell of a result set.
	 *
	 * @see PHP_MANUAL#mysql_result
	 *
	 * @param resource $result MySQL result set
	 * @param int $row The row munber, starting from 0.
	 * @param mixed $field The field name or coulmn number.
	 * @return mixed
	 */
	public function result($result, $row, $field = null)
	{
		return mysql_result($result,$row,$field);
	}

	/**
	 * Return the AUTO_INCREMENT id generated by the last query
	 *
	 * @see PHP_MANUAL#mysql_insert_id
	 *
	 * @return int
	 */
	public function insert_id()
	{
		return mysql_insert_id($this->link);
	}

	/**
	 * Escape a string for use as a literal in an SQL query.
	 *
	 * @see PHP_MANUAL#mysql_real_escape_string
	 *
	 * @param string $to_be_escaped
	 * @return string SQL escaped
	 */
	public function escape_string($to_be_escaped)
	{
		return mysql_real_escape_string($to_be_escaped);
	}

	/**
	 * Return the error message of the lase function executed
	 *
	 * @see PHP_MANUAL#mysql_error
	 *
	 * @return string
	 */
	public function error()
	{
		return mysql_error($this->link);
	}

	/**
	 * Return the error code of the last function executed.
	 *
	 * @see PHP_MANUAL#mysql_errno
	 *
	 * @return int
	 */
	public function errno()
	{
		return mysql_errno($this->link);
	}

	/**
	 * Get the number of rows changed by the last query.
	 *
	 * @see PHP_MANUAL#mysql_affected_roes
	 *
	 * @return unknown_type
	 */
	public function affected_rows()
	{
		return mysql_affected_rows($this->link);
	}

	public function enumeratetables()
	{
		$tables = array();
		$ri = Zymurgy_DB::run("show tables");
		while (($row = Zymurgy_DB::fetch_row($ri)))
		{
			$tables[] = $row[0];
		}
		Zymurgy_DB::free_result($ri);
		return $tables;
	}
}
?>
