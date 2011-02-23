<?php 
interface ZymurgyModelInterface
{
	public function read($rowid = false);
	public function write($rowdata);
	public function delete($rowid);
	public function getTableName();
	public function getTableId();
	public function getMemberTableName();
	public function getColumns($permission);
}

class ZymurgyModel implements ZymurgyModelInterface
{
	protected $tabledata;
	protected $filter;
	protected $membertable;
	protected $tablechain;
	protected $columns;
	
	/**
	 * Take a table name and construct a ZymurgyModel for that table.
	 * You can define your own models as TablenameCustomModel and the factory will generate those instead.
	 * For example, if the table was 'foo' the custom model would be called FooCustomModel, and it would
	 * still take its table name (foo) as a parameter.  Generally custom models should decend from
	 * ZymurgyModel but as long as they implement 
	 * 
	 * @param unknown_type $table
	 */
	public static function factory($table)
	{
		$camelname = strtoupper($table[0]).strtolower(substr($table,1)).'CustomModel';
		if (class_exists($camelname))
		{
			$m = new $camelname($table);
		}
		else if (class_exists('CustomModel'))
		{
			$m = new CustomModel($table);
		}
		else
		{
			$m = new ZymurgyModel($table);
		}
		return $m;
	}
	
	function __construct($table)
	{
		$this->columns = array(
			'Read'=>array(),
			'Write'=>array(),
			'Delete'=>array()
		);
		$this->filter = array();
		$this->tabledata = Zymurgy::$db->get("SELECT * FROM `zcm_customtable` WHERE `tname`='".
			Zymurgy::$db->escape_string($table)."'");
		$this->membertable = $this->tabledata;
		$this->tablechain = array();
		$datamember = false; //If $table is member data, or belongs to a member data limit data funtions to the member's rows
		while (($this->membertable['ismember'] == 0) && ($this->membertable['detailfor'] > 0))
		{
			$this->membertable = Zymurgy::$db->get("SELECT * FROM `zcm_customtable` WHERE `".
				$this->membertable['idfieldname']."`=".$this->membertable['detailfor']);
			if ($this->membertable)
			{
				$this->tablechain[] = $this->membertable;
			}
		}
		if ($this->membertable['ismember'] == 0)
		{
			$this->membertable = false;
		}
		else 
		{
			//The request is for member data.  Only allow it to be filled if a member is logged in.
			if (!Zymurgy::memberauthenticate())
			{
				throw new Exception("Table [$table] is member data through [".$this->membertable['tname'].
					"] but no member has authenticated.", 0);
			}
			$filterparts = array("SELECT `$table`.`".$this->tabledata['idfieldname']."` FROM `$table`");
			$lasttable = $this->tabledata;
			foreach ($this->tablechain as $chaintable)
			{
				$filterparts[] = "`".$chaintable['tname']."` ON `".$lasttable['tname']."`.`".
					$chaintable['tname']."`=`".$chaintable['tname']."`.`".$chaintable['idfieldname']."`";
				$lasttable = $chaintable;
			}
			//We execute this and fetch the results instead of using a sub-select as a filter
			//because you can't update with a sub-select on the same table you're updating.
			$ri = Zymurgy::$db->run(implode(' LEFT JOIN ', $filterparts).
				" WHERE `".$this->membertable['tname']."`.`member`=".Zymurgy::$member['id']);
			$ids = array();
			while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_NUM))!==false)
			{
				$ids[] = $row[0];
			}
			Zymurgy::$db->free_result($ri);
			if (!$ids)
			{//None found, build a filter that returns no rows assuming no nulls in the primary key column
				$this->filter[] = "`".$this->tabledata['tname']."`.`".$this->tabledata['idfieldname']."` IS NULL";
			}
			else 
			{
				$this->filter[] = "`".$this->tabledata['tname']."`.`".$this->tabledata['idfieldname']."` IN ('".
					implode("','", $ids)."')";
			}
		}
		//Remove access to all permission types which do not have ACL's defined.
		foreach (array_keys($this->columns) as $permission)
		{
			if (Zymurgy::checkaclbyid($this->tabledata['acl'], $permission, false) === false)
			{
				unset($this->columns[$permission]);
			}
		}
		if ($this->columns)
		{ //We have access to at least some permission types
			$ri = Zymurgy::$db->run("SELECT * FROM `zcm_customfield` WHERE `tableid`=".$this->tabledata['id']);
			$usefieldacl = false;
			while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
			{
				if ($row['acl'] > 0) $usefieldacl = true; //Turn on field level ACL's if any fields have an ACL
				foreach (array_keys($this->columns) as $permission)
				{
					$this->columns[$permission][$row['cname']] = Zymurgy::checkaclbyid($row['acl'], $permission, false);
				}
			}
			Zymurgy::$db->free_result($ri);
			if ($usefieldacl)
			{
				//Remove all array values that didn't pass the column ACL check
				foreach ($this->columns as $permission=>$permacl)
				{
					foreach($permacl as $cname=>$passedacl)
					{
						if (!$passedacl) unset($this->columns[$permission][$cname]);
					}
				}
			}
			//Always allow read of table ID's if we have any read perms at all
			if (array_key_exists('Read', $this->columns))
			{
				$this->columns['Read'][$this->tabledata['idfieldname']] = true;
			}
		}
		else 
		{
			throw new Exception("This table has no ACL, so data services are not available.", 0);
		}
	}
	
	public function read($rowid = false)
	{
		$table = $this->tabledata['tname'];
		if (!array_key_exists('Read', $this->columns))
		{
			throw new Exception("No read permission for $table, check ACL.", 0);
		}
		$sqlcols = array();
		foreach ($this->columns['Read'] as $cname=>$permission) 
		{
			$sqlcols[] = "`$cname`";
		}
		$filter = $this->filter;
		$sql = "SELECT ".implode(',', $sqlcols)." FROM `$table`";
		if ($rowid)
		{
			$filter[] = "`".Zymurgy::$db->escape_string($this->tabledata['idfieldname'])."`='".
				Zymurgy::$db->escape_string($rowid)."'";
		}
		if ($filter)
		{
			$sql .= ' WHERE ('.implode(') AND (', $filter).')';
		}
		if ($this->tabledata['hasdisporder'])
		{
			$sql .= " ORDER BY `disporder`";
		}
		$rows = array();
		$ri = Zymurgy::$db->run($sql);
		while (($r = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
		{
			$rows[$r[$this->tabledata['idfieldname']]] = $r;
		}
		Zymurgy::$db->free_result($ri);
		return $rows;
	}
	
	public function validate($rowdata)
	{
		if ($this->tablechain)
		{//This belongs to another data table; require that the parent column reference is set
			$chain = $this->tablechain;
			$chkparent = $chain[0];
			$chkrow = $rowdata;
			if (!array_key_exists($chkparent['tname'], $chkrow))
			{
				throw new Exception("Can't find required column ".$chkparent['tname']." in row data.", 0);
			}
			else 
			{//Set the col/val pair
				$this->columns['Write'][$chkparent['tname']] = true;
			}
			//Now make sure that our liniage goes back to a row that we own.
			while ($chain)
			{
				$chkparent = array_shift($chain);
				$sql = "SELECT * FROM `".$chkparent['tname']."` WHERE `".
					$chkparent['idfieldname']."` = '".Zymurgy::$db->escape_string($chkrow[$chkparent['tname']])."'";
				$chkrow = Zymurgy::$db->get($sql);
				if ($chkrow === false)
				{
					throw new Exception("Can't insert for orphaned row.", 0);
				}
			}
			if (array_key_exists('member', $chkrow) && ($chkrow['member'] != Zymurgy::$member['id']))
			{
				throw new Exception("Can't insert for row owned by member #".$chkrow['member'], 0);
			}
		}
		return $rowdata;
	}
	
	// curl --cookie "ZymurgyAuth=bobotea" -d "eggs=green&spam=vikings" http://hfo.zymurgy2.com/zymurgy/data.php?table=bar
	
	public function write($rowdata)
	{
		$table = $this->tabledata['tname'];
		if (!array_key_exists('Write', $this->columns))
		{
			throw new Exception("No write permission for $table, check ACL.", 0);
		}
		$rowdata = $this->validate($rowdata);
		if (array_key_exists($this->tabledata['idfieldname'], $rowdata))
		{//Update
			$sets = array();
			foreach ($this->columns['Write'] as $cname=>$permission)
			{
				if (array_key_exists($cname, $rowdata))
				{
					$sets[] = "`$cname`='".Zymurgy::$db->escape_string($rowdata[$cname])."'";
				}
			}
			if (!$sets) 
			{
				throw new Exception("Update failed: no row data for any allowed/known columns", 0);
			}
			$sql = "UPDATE `$table` SET ".implode(',', $sets)." WHERE `".
				Zymurgy::$db->escape_string($this->tabledata['idfieldname'])."`='".
				Zymurgy::$db->escape_string($rowdata[$this->tabledata['idfieldname']])."'";
			if ($this->filter)
			{
				$sql .= ' AND ('.implode(') AND (', $this->filter).')';
			}
		}
		else 
		{//Create
			$cols = array();
			$vals = array();
			foreach ($this->columns['Write'] as $cname=>$permission)
			{
				if (array_key_exists($cname, $rowdata))
				{
					$cols[] = "`$cname`";
					$vals[] = "'".Zymurgy::$db->escape_string($rowdata[$cname])."'";
				}
			}
			if (!$cols) 
			{
				throw new Exception("Insert failed: no row data for any allowed/known columns", 0);
			}
			if ($this->tabledata['tname'] == $this->membertable['tname'])
			{//This is a member data table; set the member column.
				$cols[] = "`member`";
				$vals[] = Zymurgy::$member['id'];
			}
			$sql = "INSERT INTO `$table` (".
				implode(',', $cols).") VALUES (".
				implode(',', $vals).")";
		}
		return Zymurgy::$db->run($sql);
	}
	
	//curl -X DELETE --cookie "ZymurgyAuth=bobotea" http://hfo.zymurgy2.com/zymurgy/data.php?table=bar&id=3
	public function delete($rowid)
	{
		$table = $this->tabledata['tname'];
		if (!array_key_exists('Delete', $this->columns))
		{
			throw new Exception("No delete permission for $table, check ACL.", 0);
		}
		$sql = "DELETE FROM `$table` WHERE (`".
			Zymurgy::$db->escape_string($this->tabledata['idfieldname'])."`='".
			Zymurgy::$db->escape_string($rowid)."')";
		if ($this->filter)
		{
			$sql .= ' AND ('.implode(') AND (', $this->filter).')';
		}
		return Zymurgy::$db->run($sql);
	}
	
	public function getTableName()
	{
		return $this->tabledata['tname'];
	}
	
	public function getTableId()
	{
		return $this->tabledata['id'];
	}
	
	public function getMemberTableName()
	{
		return $this->membertable ? $this->membertable['tname'] : false;
	}
	
	public function getColumns($permission)
	{
		if (array_key_exists($permission, $this->columns))
			return array_keys($this->columns[$permission]);
		else
			return false;
	}
}
?>