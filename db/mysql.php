<?
define('ZYMURGY_FETCH_ASSOC',MYSQL_ASSOC);
define('ZYMURGY_FETCH_BOTH',MYSQL_BOTH);
define('ZYMURGY_FETCH_NUM',MYSQL_NUM);

class Zymurgy_DB
{
	var $link;
	
	function Zymurgy_DB()
	{
		$this->link = mysql_connect(Zymurgy::$config['mysqlhost'],Zymurgy::$config['mysqluser'],Zymurgy::$config['mysqlpass']);
		mysql_select_db(Zymurgy::$config['mysqldb'],$this->link) or die ("Unable to select the database ".Zymurgy::$config['mysqldb'].".");
	}
	
	function query($sql)
	{
		//echo "<div>[$sql]</div>";
		return mysql_query($sql,$this->link);
	}

	/**
	 * Run query and give an error message if there's a problem.  Returns a result identifier resource.
	 *
	 * @param string $sql
	 * @param string $errormsg
	 * @return resource
	 */
	function run($sql, $errormsg = 'Unable to run query')
	{
		$ri = $this->query($sql) or die ("$errormsg ($sql): ".$this->error());
		return $ri;
	}
	
	/**
	 * Run a query and throw an error if there's a problem.  Return the first row as an array or
	 * the value if only one column is returned.  Returns false if no data is returned.
	 *
	 * @param string $sql
	 * @param string $errormsg
	 * @return mixed
	 */
	function get($sql, $errormsg = 'Unable to run query')
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
	
	function num_fields($ri)
	{
		return mysql_num_fields($ri);
	}
	
	function fetch_array($result, $result_type = ZYMURGY_FETCH_BOTH)
	{
		if (!is_resource($result))
		{
			echo "<div>[$result] is not a valid mysql resource.</div>";
			debug_print_backtrace();
			exit;
		}
		return mysql_fetch_array($result,$result_type);
	}
	
	function fetch_row($result)
	{
		return mysql_fetch_row($result);
	}
	
	function num_rows($result)
	{
		return mysql_num_rows($result);
	}
	
	function free_result($result)
	{
		return mysql_free_result($result);
	}
	
	function result($result, $row, $field = null)
	{
		return mysql_result($result,$row,$field);
	}
	
	function insert_id()
	{
		return mysql_insert_id($this->link);
	}
	
	function escape_string($to_be_escaped)
	{
		return mysql_escape_string($to_be_escaped);
	}
	
	function error()
	{
		return mysql_error($this->link);
	}
	
	function errno()
	{
		return mysql_errno($this->link);
	}

	function affected_rows()
	{
		return mysql_affected_rows($this->link);
	}
}
?>
