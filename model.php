<?php 
/**
 * Warning: This feature is still under development and the ZymurgyModelInterface is
 * likely to change.  Use at your own risk - updates are likely to break your own
 * implemtations of ZymurgyModelInterface classes.
 * 
 * @author Vic Metcalfe
 */ 
interface ZymurgyModelInterface
{
	/**
	 * Read row data.  If the model is member data read only rows for that member.
	 * If $rowid is supplied then only read the row with that primary key value.
	 * 
	 * @param string $rowId
	 */
	public function read($rowId = false);
	
	/**
	 * Read row data.  Access all data regardless of ownership, but subject to the acl.
	 * If $rowid is supplied then only read the row with that primary key value.
	 * 
	 * @param string $rowid
	 */
	public function readall($rowid = false);
	
	/**
	 * Write $rowdata to the table behind the model.  If $rowdata contains a value for the
	 * model's primary key then update the row, otherwise do an insert.
	 * 
	 * @param array $rowdata
	 */
	public function write($rowdata);
	
	/**
	 * Delete a row from the table behind the model.  Throws an exception if no filter has been applied.
	 */
	public function delete();
	
	/**
	 * Get the name of the table behind this model.
	 * 
	 * @return string
	 */
	public function getTableName();
	
	/**
	 * Get the ID# from the custom table table for this model.
	 * 
	 * @return int
	 */
	public function getTableId();
	
	/**
	 * Get the row from the custom table table for this model.
	 * 
	 * @return array
	 */
	public function getTableData();
	
	/**
	 * Get the name of the member table which givies this model member ownership (if any).
	 * Ownership is inherited from parent tables from master/detail relationships.
	 * 
	 * @return string
	 */
	public function getMemberTableName();
	
	/**
	 * Get a list of columns which the user has permissions for of the supplied type
	 * (Read/Write/Delete)
	 * 
	 * @param string $permission
	 */
	public function getColumns($permission);

    /**
     * Add an SQL fragment to the query filter.  These are always ANDed with each other and
     * with the member filter.
     *
     * @param $filter ZymurgyModelFilter|string
     */
    public function addFilter($filter);

    /**
     * Just like addFilter() but for OR queries.
     *
     * @param $filter ZymurgyModelFilter|string
     */
    public function addOrFilter($filter);
}

class ZymurgyModelException extends Exception
{
	public static $NO_ACL = 1;
	public static $MEMBER_MISMATCH = 2;
	public static $MISSING_COLUMN = 3;
	public static $ORPHAN = 4;
    public static $MISSING_FILTER = 5;
}

class ZymurgyBaseModel
{
    protected $tableName;
    protected $requestedColumns = array();
    private $_lastSQL;
    /**
     * @var ZymurgyModelFilter[]
     */
    protected $filter = array();

    /**
     * @var ZymurgyModelFilter[]
     */
    protected $orFilter = array();
    protected $sort = array();
    protected $rangeStart;
    protected $rangeLimit;
    protected $memberfilter;

    public function __construct($tableOrViewName)
    {
        $this->tableName = $tableOrViewName;
    }

    public function readcore($aclName, $rowid = false)
    {
        $table = $this->getTableOrViewName();
        if ($aclName == 'acl')
        {
            $filter = array_merge($this->filter,$this->memberfilter);
        }
        else
        {
            $filter = $this->filter;
        }
        $sql = "SELECT ". $this->getColumnsFragment($aclName) ." FROM `$table`";
        if ($rowid)
        {
            $filter[] = "`".Zymurgy::$db->escape_string($this->tabledata['idfieldname'])."`='".
                Zymurgy::$db->escape_string($rowid)."'";
        }
        if ($filter)
        {
            $sql .= ' WHERE ' . $this->buildFilterFragment();
        }
        if ($this->sort)
        {
            $sql .= " ORDER BY " . implode(', ', $this->sort);
        }
        if (isset($this->rangeStart) && isset($this->rangeLimit))
        {
            $sql .= " LIMIT " . $this->rangeStart . ", " . $this->rangeLimit;
        }
        $rows = array();
        $this->_lastSQL = $sql;
        $ri = Zymurgy::$db->run($sql);
        while (($r = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
        {
            //$rows[$r[$this->tabledata['idfieldname']]] = $r;
            $rows[] = $r;
        }
        Zymurgy::$db->free_result($ri);
        return $rows;
    }

    public function getColumnsFragment($aclName)
    {
        $columns = $this->getColumnList($aclName);
        $sqlColumns = array();
        foreach ($columns as $columnName)
        {
            $columnName = Zymurgy::$db->escape_string($columnName);
            $sqlColumns[] = "`$columnName`";
        }
        return implode(',', $sqlColumns);
    }

    protected function getColumnList($aclName)
    {
        if (isset(Zymurgy::$config['PublicData']) && isset(Zymurgy::$config['PublicData'][$this->getTableOrViewName()]))
        {
            if ($this->requestedColumns)
            {
                return array_intersect(Zymurgy::$config['PublicData'][$this->getTableOrViewName()],
                    $this->requestedColumns);
            }
            else
            {
                return Zymurgy::$config['PublicData'][$this->getTableOrViewName()];
            }
        }
        return array();
    }

    public function requestColumns($columns)
    {
        $this->requestedColumns = $columns;
    }

    protected function getTableOrViewName()
    {
        return $this->tableName;
    }

    public function getLastSQL()
    {
        return $this->_lastSQL;
    }

    public function read($rowId = false)
    {
        return $this->readcore('any',$rowId);
    }

    public function readall($rowid = false)
    {
        return $this->readcore('globalacl',$rowid);
    }

    public function count()
    {
        $sql = "SELECT COUNT(*) FROM `" . $this->getTableOrViewName() . "`";
        $filterFragment = $this->buildFilterFragment();
        if ($filterFragment)
        {
            $sql .= ' WHERE ' . $filterFragment;
        }
        return intval(Zymurgy::$db->get($sql));
    }
    public function addFilter($filter)
    {
        $sqlFragment = $this->filterToSQLFragment($filter);
        if (!empty($sqlFragment))
        {
            $this->filter[] = $sqlFragment;
        }
    }

    public function addOrFilter($filter)
    {
        $sqlFragment = $this->filterToSQLFragment($filter);
        if (!empty($sqlFragment))
        {
            $this->orFilter[] = $sqlFragment;
        }
    }

    protected function filterToSQLFragment($filter)
    {
        if (is_object($filter) && is_a($filter, 'ZymurgyModelFilter'))
        {
            $filter = str_replace('?', "'" . Zymurgy::$db->escape_string($filter->getValue()) . "'", $filter->getFilter());
        }
        return $filter;
    }

    protected function buildFilterFragment()
    {
        $fragment = '';
        if ($this->filter)
        {
            $fragment .= '(' . implode(') AND (', $this->filter) . ')';
        }
        if ($this->orFilter)
        {
            if ($this->filter) $fragment .= " AND ";
            $fragment .= '(' . implode(') OR (', $this->orFilter) . ')';
        }
        if ($this->memberfilter)
        {
            $fragment = "($fragment) AND (" . implode(') AND (', $this->memberfilter) . ')';
        }
        return $fragment;
    }

    public function addIdentityFilter($rowId)
    {
        $this->addFilter('`' . $this->tabledata['idfieldname'] . "` = '" . Zymurgy::$db->escape_string($rowId) . "'");
    }

    public function addSort($column, $order = 'ASC')
    {
        $this->sort[] = "`$column` $order";
    }

    public function setRange($start, $limit)
    {
        $this->rangeStart = $start;
        $this->rangeLimit = $limit;
    }

    public function getMemberTableName()
    {
        return false;
    }
}

class ZymurgyModel extends ZymurgyBaseModel implements ZymurgyModelInterface
{
	protected $tabledata;

	protected $membertable;
	protected $tablechain;
	protected $columns;

    /**
	 * Take a table name and construct a ZymurgyModel for that table.
	 * You can define your own models as TablenameCustomModel and the factory will generate those instead.
	 * For example, if the table was 'foo' the custom model would be called FooCustomModel, and it would
	 * still take its table name (foo) as a parameter.  Generally custom models should decend from
	 * ZymurgyModel but as long as they implement ZymurgyModelInterface they should work.
	 * 
	 * @param string $table
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
			'globalacl'=>array(
				'Read'=>array(),
				'Write'=>array(),
				'Delete'=>array()
			),
			'acl'=>array(//Member ACL
				'Read'=>array(),
				'Write'=>array(),
				'Delete'=>array()
			)
		);
		$this->memberfilter = array();
		$this->tabledata = Zymurgy::$db->get("SELECT * FROM `zcm_customtable` WHERE `tname`='".
			Zymurgy::$db->escape_string($table)."'");
        if ($this->tabledata === false)
        {
            throw new ZymurgyModelException("Table [$table] does not exist.");
        }
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
				throw new ZymurgyModelException("Table [$table] is member data through [".$this->membertable['tname'].
					"] but no member has authenticated.", ZymurgyModelException::$MEMBER_MISMATCH);
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
				//Somehow the following returns results in ways so strange I can only imagine there's a bug somewhere in either php or mysql, 
				//so I went i went with the more obviously non matching 1 = 2 filter.  I only observed this problem under phpunit.
				//$this->filter[] = "`".$this->tabledata['tname']."`.`".$this->tabledata['idfieldname']."` IS NULL";
				$this->memberfilter[] = "1 = 2";
			}
			else 
			{
				$this->memberfilter[] = "`".$this->tabledata['tname']."`.`".$this->tabledata['idfieldname']."` IN ('".
					implode("','", $ids)."')";
			}
		}
		//Remove access to all permission types which do not have ACL's defined.
		foreach (array_keys($this->columns) as $permtype)
		{
			foreach (array_keys($this->columns[$permtype]) as $permission)
			{
				if (Zymurgy::checkaclbyid($this->tabledata[$permtype], $permission, false) === false)
				{
					unset($this->columns[$permtype][$permission]);
				}
			}
			if (!$this->columns[$permtype])
			{
				unset($this->columns[$permtype]);
			}
		}
		if ($this->columns)
		{ //We have access to at least some permission types
			$ri = Zymurgy::$db->run("SELECT * FROM `zcm_customfield` WHERE `tableid`=".$this->tabledata['id']);
			$usefieldacl = false;
			$usefieldglobalacl = false;
			while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
			{
				if ($row['acl'] > 0) $usefieldacl = true; //Turn on field level ACL's if any fields have an ACL
				if ($row['globalacl'] > 0) $usefieldglobalacl = true; //Same for global perms
				foreach (array_keys($this->columns) as $permtype)
				{
					foreach (array_keys($this->columns[$permtype]) as $permission)
					{
						$this->columns[$permtype][$permission][$row['cname']] = Zymurgy::checkaclbyid($row[$permtype], $permission, false);
					}
				}
			}
			$builtinfields = array();
			if (!empty($this->tabledata['detailforfield']))
			{
				$builtinfields[] = $this->tabledata['detailforfield'];
			}
			if ($this->tabledata['ismember'] == 1)
			{
				$builtinfields[] = 'member';
			}
			foreach ($builtinfields as $builtinfield)
			{
				foreach (array_keys($this->columns) as $permtype)
				{
					foreach (array_keys($this->columns[$permtype]) as $permission)
					{
						$this->columns[$permtype][$permission][$builtinfield] = true;
					}
				}
			}
			Zymurgy::$db->free_result($ri);
			if ($usefieldacl)
			{
				//Remove all array values that didn't pass the column ACL check
				foreach (array_keys($this->columns) as $permtype)
				{
					foreach ($this->columns[$permtype] as $permission=>$permacl)
					{
						foreach($permacl as $cname=>$passedacl)
						{
							if (!$passedacl) unset($this->columns[$permtype][$permission][$cname]);
						}
					}
				}
			}
			//Always allow read of table ID's if we have any read perms at all
			foreach (array_keys($this->columns) as $permtype)
			{
				if (array_key_exists('Read', $this->columns[$permtype]))
				{
					$this->columns[$permtype]['Read'][$this->tabledata['idfieldname']] = true;
				}
			}
		}
		else 
		{
			throw new ZymurgyModelException("This table [$table] has no ACL, so data services are not available.", ZymurgyModelException::$NO_ACL);
		}
        if ($this->tabledata['hasdisporder'])
        {
            $this->addSort('disporder');
        }
    }

    protected function getTableOrViewName()
    {
        return $this->tabledata['tname'];
    }

    public function getownership($rowdata)
	{
		if ($this->tablechain)
		{
			$chain = $this->tablechain;
			while ($chain)
			{
				$chkparent = array_shift($chain);
				$sql = "SELECT * FROM `".$chkparent['tname']."` WHERE `".
					$chkparent['idfieldname']."` = '".Zymurgy::$db->escape_string($rowdata[$chkparent['tname']])."'";
				$rowdata = Zymurgy::$db->get($sql);
				if ($rowdata === false)
				{
					throw new ZymurgyModelException("Can't validate orphaned row.", ZymurgyModelException::$ORPHAN);
				}
				if (array_key_exists('member', $rowdata))
				{
					break; //Stop following the chain back - this row is the one with the member info.
				}
			}
		}
		if (!array_key_exists('member', $rowdata))
		{
			throw new ZymurgyModelException("Can't get owner of non-member row", ZymurgyModelException::$MEMBER_MISMATCH);
		}
		return $rowdata['member'];
	}
	
	public function validateownership($rowdata)
	{
		$owner = $this->getownership($rowdata);
		$supergroups = array(1,2,3); //ZCM groups with access to CP get super user access to all member data
		$mysuper = array_intersect(array_keys(Zymurgy::$member['groups']), $supergroups);
		if ($mysuper || ($owner == Zymurgy::$member['id']))
		{
			return $owner;
		}
		throw new ZymurgyModelException("Member #".Zymurgy::$member['id']." can't access row owned by member #$owner.", ZymurgyModelException::$MEMBER_MISMATCH);
	}
	
	public function memberOwnsRow($row)
	{
		return (array_key_exists('member', $row) && ($row['member'] == Zymurgy::$member['id']));
	}
	
	public function validaterow($rowdata)
	{
		if ($this->tablechain)
		{//This belongs to another data table; require that the parent column reference is set
			$chain = $this->tablechain;
			$chkparent = $chain[0];
			$chkrow = $rowdata;
			if (!array_key_exists($chkparent['tname'], $chkrow))
			{
				throw new ZymurgyModelException("Can't find required column ".$chkparent['tname']." in row data.", ZymurgyModelException::$MISSING_COLUMN);
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
					throw new ZymurgyModelException("Can't insert for orphaned row.", ZymurgyModelException::$ORPHAN);
				}
			}
			if (!$this->memberOwnsRow($chkrow))
			{
                if (count($this->columns['globalacl']['Write']) == 0)
                {
    				throw new ZymurgyModelException("Can't insert for row owned by member #".$chkrow['member'], ZymurgyModelException::$MEMBER_MISMATCH);
                }
			}
		}
		return $rowdata;
	}
	
	/**
	 * Check the user's permission to interact with a model.  $perm is Read, Write or Delete
	 * and $oftype is any, acl or globalacl.
	 * 
	 * @param string $perm
	 * @param string $ofType
	 * @throws ZymurgyModelException
	 */
	public function checkacl($perm,$ofType = 'any',$dump = false)
	{
		$table = $this->tabledata['tname'];
		$allowedcols = array();
		if ($this->membertable && (($ofType == 'any') || ($ofType == 'acl')))
		{
			if (array_key_exists('acl',$this->columns) && array_key_exists($perm, $this->columns['acl']))
			{ //This table is member data, and the member ACL allows the requested priv.
				$allowedcols = $this->columns['acl'][$perm];
			}
		}
		if (array_key_exists('globalacl',$this->columns) && array_key_exists($perm, $this->columns['globalacl']) 
			&& (($ofType == 'any') || ($ofType == 'globalacl')))
		{ //The global ACL allows the requested priv.
			$allowedcols = array_merge($allowedcols, $this->columns['globalacl'][$perm]);
		}
		if (!$allowedcols)
		{
			throw new ZymurgyModelException("No $perm permission for $table, check the access control list for this table and its columns.", 
				ZymurgyModelException::$NO_ACL);
		}
		return $allowedcols;
	}

	// curl --cookie "ZymurgyAuth=bobotea" -d "eggs=green&spam=vikings" http://hfo.zymurgy2.com/zymurgy/data.php?table=bar
	
	public function write($rowdata)
	{
        $insertId = null;
		$aclcols = $this->checkacl('Write');
		$table = $this->tabledata['tname'];
		$rowdata = $this->validaterow($rowdata);
        $sets = array();
        foreach ($aclcols as $cname=>$permission)
        {
            if (array_key_exists($cname, $rowdata))
            {
                $sets[$cname] = $rowdata[$cname];
            }
        }
		if (array_key_exists($this->tabledata['idfieldname'], $rowdata))
		{//Update
			if (!$sets)
			{
				throw new ZymurgyModelException("Update failed: no row data for any allowed/known columns", ZymurgyModelException::$MISSING_COLUMN);
			}

			$where = "`" . Zymurgy::$db->escape_string($this->tabledata['idfieldname'])."`='".
				Zymurgy::$db->escape_string($rowdata[$this->tabledata['idfieldname']])."'";
			if ($this->memberfilter)
			{
				$where .= ' AND ('.implode(') AND (', $this->memberfilter).')';
			}
            Zymurgy::$db->update($table, $where, $sets);
		}
		else 
		{//Create
            if (isset($this->tablechain[2]))
            {
                $cname = $this->tablechain[0]['tname'];
                $sets[$cname] = $rowdata[$cname]; //Allow parent relationship in insert
            }
			if (!$sets)
			{
				throw new ZymurgyModelException("Insert failed: no row data for any allowed/known columns", ZymurgyModelException::$MISSING_COLUMN);
			}
			if ($this->tabledata['tname'] == $this->membertable['tname'])
			{//This is a member data table; set the member column.
                $sets['member']= Zymurgy::$member['id'];
			}
            $insertId = Zymurgy::$db->insert($table, $sets);
		}
        return $insertId;
	}
	
	//curl -X DELETE --cookie "ZymurgyAuth=bobotea" http://hfo.zymurgy2.com/zymurgy/data.php?table=bar&id=3
	public function delete($rowId = false)
	{
        if ($rowId) $this->addIdentityFilter($rowId);
        if (empty($this->filter))
        {
            throw new ZymurgyModelException("Can't delete without first setting a filter", ZymurgyModelException::$MISSING_FILTER);
        }
		$this->checkacl('Delete');
		$table = $this->tabledata['tname'];
        Zymurgy::$db->delete($table, $this->buildFilterFragment());
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
	
	public function getTableData()
	{
		return $this->tabledata;
	}
	
	public function getColumns($permission)
	{
		if (array_key_exists($permission, $this->columns))
			return array_keys($this->columns[$permission]);
		else
			return false;
	}

    protected function getColumnList($aclName)
    {
        $allowedColumns = array_keys($this->checkacl('Read',$aclName));
        $columns = $this->requestedColumns ? array_intersect($allowedColumns, $this->requestedColumns) : $allowedColumns;
        return $columns;
    }

    private function setValue($str)
    {
        if ($str === null) return 'null';
        return "'" . Zymurgy::$db->escape_string($str) . "'";
    }

}

class ZymurgyModelFilter
{
    private $_operator;
    private $_isOr;
    private $_column;
    private $_value;

    function __construct($operator, $isOr, $column, $value)
    {
        $this->_operator = $operator;
        $this->_isOr = $isOr;
        $this->_column = $column;
        $this->_value = $value;
    }

    function getFilter()
    {
        if (is_null($this->_operator))
        {
            return $this->_value;
        }
        else
        {
            $column = str_replace('_','`.`',$this->_column);
            if (is_null($this->_value))
            {
                return "`$column` " . (($this->_operator == '=') ? 'IS' : 'IS NOT') . " NULL";
            }
            else
            {
                if ($this->_operator == 'N')
                {
                    return ($this->_value) ? "`$column` IS NOT NULL" : null;
                }
                return "`$column` {$this->_operator} ?";
            }
        }
    }

    function isOr()
    {
        return $this->_isOr;
    }

    function getValue()
    {
        if ($this->_operator === 'like')
        {
            return "%".$this->_value."%";
        }
        return $this->_value;
    }

    function getColumn()
    {
        return $this->_column;
    }
}
