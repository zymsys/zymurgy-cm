<?
/**
 * DataGrid classes
 *
 * Some of the GET variables used by the grid include:
 *	- sortcolumn: Data column name for the sort column
 *	- sortorder: ASC or DESC
 *	- page: Current page of datagrid
 *  - editkey: Key of the column being edited, -1 for insert (not set when not in edit mode)
 *
 * @package Zymurgy
 * @subpackage backend-base
*/

//TODO: a replcement datagrid.php using http://wiki.github.com/mleibman/SlickGrid/
// Also see: http://jquery.malsup.com/block/#demos

$datagridexpertmode = false;

$ZymurgyRoot = Zymurgy::$root;
$ZymurgyConfig = Zymurgy::$config;

if (isset($_GET['mime']))
{
	if ($_GET['mime'] == '')
	{
		header("Content-type: image/gif");
		echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x40\x02\x01\x44\x00\x3b";
		exit;
	}
	else
	{
		header("Content-type: {$_GET['mime']}");
		$fn = '';
		$safefn = "{$_GET['dataset']}.{$_GET['datacolumn']}.{$_GET['id']}";
		while ($fn != $safefn)
		{
			$fn = $safefn;
			$safefn = str_replace('..','.',$fn);
		}
		$safefn = "uploads/$safefn";
		echo file_get_contents($safefn);
		exit;
	}
}

require_once(Zymurgy::getFilePath("~InputWidget.php"));

if (!function_exists('getapplpath'))
{
	/**
	 * Returns absolute path from document root of the supplied script's file name.  Use __FILE__ to get your own path.
	 * Returns false if the supplied script isn't within the application root.
	 *
	 * @param string $scriptname Script to figure out the path of
	 */
	function getapplpath($scriptname)
	{
		global $ZymurgyRoot;

		if (substr($scriptname,0,strlen($ZymurgyRoot)) != $ZymurgyRoot)
			return false;
		$lastslash = strrpos($scriptname,'/');
		$start = strlen($ZymurgyRoot)-1;
		$lenth = $lastslash-$start;
		return substr($scriptname,$start+1,$lenth);
	}
}

//var_dump($GLOBALS);
global $suppressDatagridJavascript;

if(!isset($GLOBALS["suppressDatagridJavascript"]))
{
//	print_r(debug_backtrace());
//	die();

?>
<script language="JavaScript">
<!--
		function confirm_delete()
		{
		if (confirm("Are you sure you want to delete this record?")==true)
			return true;
		else
			return false;
		}

		/*
		ds = dataset; table.field
		d = requested dimensions WIDTHxHEIGHT
		id = row ID in the datagrid
		imgid = element ID of the image to update after thumbing
		fixedratio = is this a fixed aspect ratio?
		gd = grid image dimensions WIDTHxHEIGHT to support small images in grid
		*/
		function aspectcrop_popup(ds, d, id, imgid, fixedratio, gd)
		{
			var fixedar,
                cropService = '<?php echo Zymurgy::getUrlPath('~aspectcrop.php'); ?>';
			if (fixedratio)
				fixedar = '&fixedar=1';
			else
				fixedar = '';
			window.open(cropService + '?ds='+ds+'&d='+d+'&gd='+gd+'&id='+id+'&imgid='+imgid+fixedar,'','scrollbars=no,width=780,height=500');
		}
//-->
</script>
<?
}

if (get_magic_quotes_gpc()) {
   function stripslashes_deep($value)
   {
       $value = is_array($value) ?
                   array_map('stripslashes_deep', $value) :
                   stripslashes($value);

       return $value;
   }

   $_POST = array_map('stripslashes_deep', $_POST);
   $_GET = array_map('stripslashes_deep', $_GET);
   $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}

/**
 * Represents a single column within a DataSet.
 *
 */
class DataColumn
{
	/**
	 * The name of the column
	 *
	 * @var string
	 */
	var $name;

	/**
	 * When true, the data within the column is to be wrapped in quotes during
	 * inserts and deletes.
	 *
	 * @var boolean
	 */
	var $quoted;

	/**
	 * Constructor.
	 *
	 * @param string $name The name of the column
	 * @param boolean $quoted When true, the data within the column is to be
	 * wrapped in quotes during inserts and deletes.
	 * @return DataColumn
	 */
	function DataColumn($name,$quoted)
	{
		$this->name = $name;
		$this->quoted = $quoted;
	}
}

/**
 * Represents a single row within a DataSet.
 *
 */
class DataSetRow
{
	/**
	 * The list of values in the row, before changes made in the form are
	 * applied.
	 *
	 * @var mixed
	 */
	var $originalvalues;

	/**
	 * The list of values in the row, after changes in the form are applied.
	 *
	 * @var mixed
	 */
	var $values;

	/**
	 * If true, at least one of the values in the row has been changed.
	 *
	 * @var mixed
	 */
	var $dirty;

	/**
	 * DataSet which owns this DataSetRow
	 *
	 * @var DataSet
	 */
	var $DataSet;
	var $state; //NORMAL or EDITING
	var $edittype; //Blank, INSERT or UPDATE
	var $invalidmsg; //Set when OnBeforeInsert or OnBeforeUpdate fail with an error or validation message

	/**
	 * Constructor.
	 *
	 * @return DataSetRow
	 */
	public function DataSetRow()
	{
		$this->dirty = false;
		$this->values = array();
		$this->originalvalues = array();
		$this->state = 'NORMAL';
		$this->edittype = '';
	}

	/**
	 * Set the value of a column to a new value.
	 *
	 * @param string $columnname
	 * @param mixed $value
	 */
	public function SetValue($columnname,$value)
	{
		if ($this->state != 'EDITING')
		{
			echo "<hr>Attempt to edit DataSetRow that isn't in EDITING state.<hr>";
			return;
		}
		$this->values[$columnname] = $value;
		$this->dirty = true;
	}

	/**
	 * Revert any edits made to the record.
	 *
	 */
	public function Cancel()
	{
		$this->values = $this->originalvalues;
		$this->state = 'NORMAL';
		$this->edittype = '';
	}

	/**
	 * Allow changes to be made to the record.
	 *
	 */
	public function Edit()
	{
		$this->state = 'EDITING';
		$this->edittype = 'UPDATE';
	}

	/**
	 * Flag the record for insertion into the database.
	 *
	 */
	public function Insert()
	{
		$this->state = 'EDITING';
		$this->edittype = 'INSERT';
	}

	/**
	 * Return a list of the table the record belongs to, as well as any child
	 * tables with records associated with the base record.
	 *
	 * @return mixed
	 */
	function GetMyTables()
	{
		$tables = array();
		$tablekeys = array();
		foreach ($this->values as $key=>$val)
		{
			//echo "gmt[$key,$val]<br>";
			list($tname,$column) = explode('.',$key,2);
			if (!isset($tables[$tname]))
			{
				$tables[$tname] = array();
				if (isset($this->DataSet->relationships[$tname]))
					$tablekeys[$tname] = $this->DataSet->relationships[$tname]->detailcolumn;
				else
					$tablekeys[$tname] = substr($this->DataSet->masterkey,strpos($this->DataSet->masterkey,'.')+1);
			}
			$tables[$tname][$column] = $val;
		}
		return array($tables,$tablekeys);
	}

	/**
	 * Delete the record from the database.
	 *
	 * @return The recordset resulting in the final delete.
	 */
	function Delete()
	{
		// ZK: Added OnBeforeDelete event to allow for customization on the
		// Delete event without having to override the existing OnDelete call
		// in customedit.php each time
		if (isset($this->DataSet->OnBeforeDelete))
		{
			if (call_user_func($this->DataSet->OnBeforeDelete,$this->values) === false)
				return false; //Callback can abort delete
		}

		if (isset($this->DataSet->OnDelete))
		{
			if (call_user_func($this->DataSet->OnDelete,$this->values) === false)
				return false; //Callback can abort delete
		}

		$deletekey = intval($_GET['deletekey']);
		//Delete relevant flavoured text
		foreach($this->DataSet->DataGrid->columns as $column)
		{
			$iw = InputWidget::GetFromInputSpec($column->editor);
			if ($iw->SupportsFlavours())
			{
				$cname = $column->datacolumn;
				Zymurgy::$db->run("delete from zcm_flavourtextitem where zcm_flavourtext=".$this->values[$cname]);
				Zymurgy::$db->run("delete from zcm_flavourtext where id=".$this->values[$cname]);
			}
		}
		list($tables,$tablekeys) = $this->GetMyTables();
		foreach ($tables as $tname=>$values)
		{
            $ri = Zymurgy::$db->delete($tname, "{$tablekeys[$tname]}=$deletekey");
		}
		return $ri;
	}

	/**
	 * Update the record in the database.
	 *
	 * @return int The ID of the updated record.
	 */
	function Update()
	{
		$rid = 0;
		if ($this->state != 'EDITING')
		{
			echo "<hr>Attempt to update DataSetRow that isn't in EDITING state.<hr>";
			return;
		}
		$newvalues = $this->values;
		if ($this->edittype == 'UPDATE')
		{
			if (isset($this->DataSet->OnBeforeUpdate))
				$newvalues = call_user_func($this->DataSet->OnBeforeUpdate,$this->values);
		}
		else
		{
			if (isset($this->DataSet->OnBeforeInsert))
				$newvalues = call_user_func($this->DataSet->OnBeforeInsert,$this->values);
		}
		if ($newvalues===false)
			return true; //Allow before events to abort the insert or update; true to indicate success - the success
                         //is the event handler's success.
		if (is_array($newvalues)) //Allow updates to content of data before edit/insert
			$this->values = $newvalues;
		else
		{
			$this->invalidmsg = $newvalues;
			return false;
		}
		if (!$this->dirty) return; //Nothing to do
		list($tables,$tablekeys) = $this->GetMyTables();
		//Now create seperate update/inserts for each table
		foreach ($tables as $tname=>$values)
		{
			$vlist = array();
			$alist = array();

			foreach ($values as $cname=>$val)
			{

				// If the user clicked on the Clear button for an attachment,
				// delete the attachment from the filesystem and clear
				// the field in the table.
				if(isset($_POST["clear{$tname}_{$cname}"]) && $_POST["clear{$tname}_{$cname}"] == "1")
				{
					// delete files using the "attachment" inputspec
					$uploadfolder = Zymurgy::getFilePath("~uploads/");
					$filepath = $uploadfolder."$tname.$cname.".$this->values["$tname.{$tablekeys[$tname]}"];
					@unlink($filepath);

					// delete files using the "image" inputspec
					$datagridfolder = Zymurgy::$root."/UserFiles/DataGrid/$tname.$cname/";
					$filepath = $datagridfolder.$this->values["$tname.{$tablekeys[$tname]}"]."*.*";
					$files = glob($filepath);
					foreach($files as $file)
					{
						unlink($file);
					}

					// clear the table value
					$val = "";
				}

				if (!key_exists("$tname.$cname",$this->DataSet->columns))
					$this->DataSet->AddColumn($cname,true); //Auto create missing columns

                $vlist[$cname] = $val;
			}

			// -----
			// Store flavoured values and tags
			foreach($this->DataSet->DataGrid->columns as $column)
			{
				$iw = InputWidget::GetFromInputSpec($column->editor);

				if ($iw->SupportsFlavours())
				{
					$cname = str_replace("`", "",array_pop(explode('.',$column->datacolumn)));
					$realFieldName = $tname.".".$cname;
					$flavourTextID = $iw->StoreFlavouredValueFromPost($tname.'_'.$cname,$this->values[$realFieldName]);
					$vlist[$cname] = $flavourTextID;
					$alist[$cname] = "`$cname`=$flavourTextID";
					$this->values[$realFieldName] = $flavourTextID;
				}
			}

			// die("<pre>".print_r($alist, true)."</pre>");

			// -----
			// If the member is empty (due to inserting into a member table
			// directly through Zymurgy:CM), assign the record to the user
			// currently logged in.
			if(array_key_exists("member", $vlist) && empty($vlist["member"]))
			{
				Zymurgy::memberauthenticate();
				$vlist["member"] = Zymurgy::$member["id"];
			}

			if ($this->edittype == 'UPDATE')
			{
				if ($this->DataSet->columns["$tname.{$tablekeys[$tname]}"]->quoted)
					$keyval = "'".Zymurgy::$db->escape_string($this->values["$tname.{$tablekeys[$tname]}"])."'";
				else
					$keyval = $this->values["$tname.{$tablekeys[$tname]}"];
                Zymurgy::$db->update($tname, "{$tablekeys[$tname]}=$keyval", $vlist);
				if ($rid==0)
					$rid = $this->values["$tname.{$tablekeys[$tname]}"];
			}
			else
			{
                $rid = Zymurgy::$db->insert($tname, $vlist);
			}

			$this->values[$this->DataSet->masterkey] = $rid;
			//echo "[rid:$rid][key:{$this->DataSet->masterkey}][sql:$sql]";exit;
		}

		foreach($this->DataSet->DataGrid->columns as $column)
		{
//			die("Tag cloud loop entered");

			$iw = InputWidget::GetFromInputSpec($column->editor);

			if($iw instanceof PIW_CloudTagInput)
			{
				$inputspec = explode(".", $column->editor);

				$pi = Zymurgy::mkplugin('TagCloud', $inputspec[1],'',0);
				$instanceID = $pi->iid;
//				die($instanceID);

				$sql = "DELETE FROM `zcm_tagcloudrelatedrow` WHERE `instance` = '".
					Zymurgy::$db->escape_string($instanceID).
					"' AND `relatedrow` = '".
					Zymurgy::$db->escape_string($tname.".".$this->values[$this->DataSet->masterkey]).
					"'";
				Zymurgy::$db->query($sql)
					or die("Could not clear old tag list: ".Zymurgy::$db->error().", $sql");


//				die("$tname.$cname: ".$column->editor);
//				die(print_r($this->values, true));
				$tagstrings = explode(",", $this->values[$column->datacolumn]);
				$tags = array();
				foreach ($tagstrings as $tag)
				{
					$flavourid = $pi->TagNameToFlavourID($tag);
					if (!$flavourid)
					{
						$flavourid = ZIW_Base::StoreFlavouredValue(0, $tag, array());
					}
					$tags[$tag] = $flavourid;
				}

				foreach($tags as $tag)
				{
					$sql = "SELECT `id` FROM `zcm_tagcloudtag` WHERE `instance` = '".
						Zymurgy::$db->escape_string($instanceID).
						"' AND `name` = '".
						Zymurgy::$db->escape_string($tag).
						"'";
//					die($sql);
					$tagID = Zymurgy::$db->get($sql);

					if($tagID > 0)
					{
						// do nothing
					}
					else
					{
						$sql = "INSERT INTO `zcm_tagcloudtag` ( `instance`, `name` ) VALUES ( '".
							Zymurgy::$db->escape_string($instanceID).
							"', '".
							Zymurgy::$db->escape_string($tag).
							"' )";
						Zymurgy::$db->query($sql)
							or die("Could not add tag to list: ".Zymurgy::$db->error().", $sql");

						$tagID = Zymurgy::$db->insert_id();
					}

					$sql = "INSERT INTO `zcm_tagcloudrelatedrow` ( `instance`, `tag`, `relatedrow` ) VALUES ( '".
						Zymurgy::$db->escape_string($instanceID).
						"', '".
						Zymurgy::$db->escape_string($tagID).
						"', '".
						Zymurgy::$db->escape_string($tname.".".$this->values[$this->DataSet->masterkey]).
						"' )";
//					die($sql);
					Zymurgy::$db->query($sql)
						or die("Could not assign tag to row: ".Zymurgy::$db->error().", $sql");
				}
			}
		}

		//Check for DisplayOrder update required
		if ($ri && ($this->DataSet->DisplayOrder!='') && ($this->edittype=='INSERT'))
		{
			list($tname,$column) = explode(".",$this->DataSet->DisplayOrder,2);
			$id = Zymurgy::$db->insert_id();
			$sql = "update $tname set $column=$id where {$tablekeys[$tname]}=$id";
			$ri = Zymurgy::$db->query($sql);
			$this->values[$this->DataSet->masterkey] = $id;
		}
		if (!$ri)
		{
			echo "<hr>".Zymurgy::$db->error()."<p>$sql<hr>";
		}
		if ($this->edittype == 'UPDATE')
		{
			if (isset($this->DataSet->OnUpdate))
				call_user_func($this->DataSet->OnUpdate,@$this->values,@$this->originalvalues);
		}
		else
		{
			if (isset($this->DataSet->OnInsert))
				call_user_func($this->DataSet->OnInsert,@$this->values);
		}
		$this->originalvalues = $this->values;
		$this->state = 'NORMAL';
		return $rid;
	}
}

/**
 * Describes the relationship between a parent/master table, and one of its
 * child/detail tables.
 *
 */
class DataRelationship
{
	/**
	 * The name of the parent/master table.
	 *
	 * @var string
	 */
	var $mastertable;

	/**
	 * The name of the parent/master table's primary key column.
	 *
	 * @var string
	 */
	var $mastercolumn;

	/**
	 * The name of the child/detail table.
	 *
	 * @var string
	 */
	var $detailtable;

	/**
	 * The name of the child/detail table's foriegn key column.
	 *
	 * @var unknown_type
	 */
	var $detailcolumn;

	/**
	 * Constructor.
	 *
	 * @param string $mastertable The name of the parent/master table.
	 * @param string $mastercolumn The name of the parent/master table's
	 * primary key column.
	 * @param string $detailtable The name of the child/detail table.
	 * @param string $detailcolumn The name of the child/detail table's foriegn
	 * key column.
	 * @return DataRelationship
	 */
	function DataRelationship($mastertable,$mastercolumn,$detailtable,$detailcolumn)
	{
		$this->mastertable = $mastertable;
		$this->mastercolumn = $mastercolumn;
		$this->detailtable = $detailtable;
		$this->detailcolumn = $detailcolumn;
	}
}

/**
 * Describes a filter to apply to the records returned by a DataSet.
 *
 */
class DataSetFilter
{
	/**
	 * The name of the column to filter.
	 *
	 * @var string
	 */
	var $columnname;

	/**
	 * The value to filter the column against.
	 *
	 * @var string
	 */
	var $value;

	/**
	 * The operator to use to perform the comparison. Corresponds to the
	 * operator used in the corresponding SQL WHERE clause.
	 *
	 * @var string
	 */
	var $operator;

	/**
	 * Constructor.
	 *
	 * @param string $columnname The name of the column to filter.
	 * @param string $value The value to filter the column against.
	 * @param string $operator The operator to use to perform the comparison.
	 *  Corresponds to the operator used in the corresponding SQL WHERE clause.
	 * @return DataSetFilter
	 */
	function DataSetFilter($columnname,$value,$operator="=")
	{
		$this->columnname = $columnname;
		$this->value = $value;
		$this->operator = $operator;
	}
}

class DataSet
{
	var $masterkey;

	/**
	 * Array of tables used by this dataset
	 *
	 * @var array
	 */
	var $tables;

	/**
	 * List of columns used by this dataset.
	 *
	 * @var mixed
	 */
	var $columns;

	/**
	 * List of rows returned by this dataset.
	 *
	 * @var mixed
	 */
	var $rows;

	/**
	 * The ID of the row being edited in this dataset.
	 *
	 * @var int
	 */
	var $editrow;

	/**
	 * The list of relationships between the master and detail tables being
	 * maintained by this dataset.
	 *
	 * @var mixed
	 */
	var $relationships;

	/**
	 * The maximum number of rows to return when the dataset is queried.
	 *
	 * @var int
	 */
	var $fetchrows;

	/**
	 * Number used to sort for display purposes.  Can be changed by the user to change the order of items.
	 *
	 * @var unknown_type
	 */
	var $DisplayOrder;

	/**
	 * List of filters to apply to the returned dataset.
	 *
	 * @var mixed
	 */
	var $Filters;

	/**
	 * Extra SQL parameters to apply to the query. Used primarily on tables
	 * that use Full Text Search.
	 *
	 * @var string
	 */
	var $ExtraSQL;

	/**
	 * DataGrid (if any) which owns this DataSet
	 *
	 * @var DataGrid
	 */
	var $DataGrid;

	//Define these events to hook into them
	//var $OnInsert($dataset)
	//var $OnUpdate($dataset)

	/**
	 * Constructor
	 *
	 * @param string $mastertable The name of the master table
	 * @param string $masterkey The name of the primary key for the master table
	 * @return DataSet
	 */
	public function DataSet($mastertable,$masterkey)
	{
		$this->tables = array($mastertable);
		$this->relationships = array();
		$this->masterkey = "$mastertable.$masterkey";
		$this->columns = array();
		$this->rows = array();
		$this->Filters = array();
		$this->fetchrows = 20;
		$this->DisplayOrder = '';
	}

	/**
	 * Apply a filter to a dataset.
	 *
	 * @param string $columnname The name of the column to filter.
	 * @param string $value The value to filter the column against.
	 * @param string $operator The operator to use to perform the comparison.
	 * Corresponds to the operator used in the corresponding SQL WHERE clause.
	 */
	public function AddDataFilter($columnname,$value,$operator="=")
	{
		if (!(strpos($columnname,'.')!==false))
			$columnname = $this->tables[0].".$columnname";
		$this->Filters[] = new DataSetFilter($columnname,$value,$operator);
	}

	/**
	 * Remove all of the rows from the dataset.
	 *
	 */
	public function Clear()
	{
		//$this->columns = array();
		$this->rows = array();
	}

	/**
	 * Add a detail table to the dataset.
	 *
	 * @param string $detailtable The name of the child/detail table.
	 * @param string $detailcolumn The name of the child/detail table's foriegn
	 * key column.
	 */
	public function AddTable($detailtable,$detailcolumn)
	{
		$this->tables[] = $detailtable;
		$kp = explode(".",$this->masterkey,2);
		$this->relationships[$detailtable] = new DataRelationship(
			$kp[0],
			$kp[1],
			$detailtable,
			$detailcolumn);
	}

	/**
	 * Add a column to the dataset.
	 *
	 * @param string $name The name of the column to add to the dataset
	 * @param boolean $quoted If true, the value in the column must be wrapped
	 * in quotes while being inserted or updated in the database.
	 */
	public function AddColumn($name,$quoted)
	{
		if (!(strpos($name,'.')!==false))
			$name = $this->tables[0].".$name";
		$this->columns[$name] = new DataColumn($name,$quoted);
	}

	/**
	 * Add a series of quoted columns to the dataset.
	 *
	 * @param string $name[] The name of the column to add to the dataset.
	 */
	public function AddColumns()
	{
		$args = func_get_args();
		foreach ($args as $colname)
			$this->AddColumn($colname,true);
	}

	/**
	 * Return a new, unpopulated row.
	 *
	 * @return DataSetRow
	 */
	public function GetBlankRow()
	{
		$r = new DataSetRow();
		$r->DataSet = &$this;
		return $r;
	}

	/**
	 * Returns a list of strings to use as the WHERE clause for the SQL SELECT
	 * statement, based on the list of filters set in the DataSet.
	 *
	 * @return mixed
	 */
	public function getwhere()
	{
		$where = array();
		foreach ($this->Filters as $f)
		{
			if (!array_key_exists($f->columnname,$this->columns))
			{
				die("You have declared a filter for an undeclared column: {$f->columnname}.");
			}

			if ($this->columns[$f->columnname]->quoted && !($f->value == "null"))
				$value = "'".
					Zymurgy::$db->escape_string($f->value).
					"'";
			else
				$value = $f->value;

			$where[] = "({$f->columnname} {$f->operator} $value)";
		}
		return $where;
	}

	/**
	 * Populate the dataset.
	 *
	 * @param int $start The index of the first record to return
	 * @param int $length The maximum number of records to return
	 * @return int The number of rows returned by the query
	 */
	function fill($start=0,$length=0)
	{
		$selectcols = array();
		foreach ($this->columns as $c)
		{
			$selectcols[] = $c->name;
		}
		$join = array();
		foreach ($this->relationships as $r)
		{
			$join[] = " left join {$r->detailtable} on ({$r->mastertable}.{$r->mastercolumn} = {$r->detailtable}.{$r->detailcolumn})";
		}
		$where = $this->getwhere();
		$sql = " from {$this->tables[0]}";
		if (count($join)>0)
			$sql .= " ".join(" ",$join);
		if (count($where)>0)
			$sql .= " where ".implode(" and ",$where);
		if ($this->DisplayOrder != '')
		{
			$order = " order by ".$this->DisplayOrder;
		}
		elseif (isset($_GET['sortcolumn']))
		{
			$order = " order by ".$_GET['sortcolumn'];
			if (key_exists('sortorder',$_GET) && ($_GET['sortorder'] == 'DESC'))
				$order .= " desc";
		}
		else
		{
			$order = '';
		}
		if (!empty($this->ExtraSQL))
		{
			$sql = "$sql {$this->ExtraSQL}";
		}
		$count = Zymurgy::$db->get("select count(*)".$sql);

		$rsql = "select ".implode(",",$selectcols)."$sql $order";
		if (($start!=0) || ($length!=0))
			$rsql .= " limit $start,$length";
		//echo "[$rsql]<br>";
		$ri = Zymurgy::$db->query($rsql);
		if (!$ri)
		{
			echo "<hr>".Zymurgy::$db->error()."<p>$rsql<hr>";
			exit;
		}
		while (($row = Zymurgy::$db->fetch_row($ri)) !== false)
		{
			$dsr = new DataSetRow();
			$key = '';
			$colkeys = array_keys($this->columns);
			for ($n = 0; $n < count($colkeys); $n++)
			{
				$colvalue = $row[$n];
				$colname = $this->columns[$colkeys[$n]]->name;
				$dsr->values[$colname] = $colvalue;
				$dsr->originalvalues[$colname] = $colvalue;
				if ($colname == $this->masterkey)
					$key = $colvalue;
			}
			$dsr->DataSet = &$this;
			$this->rows[$key] = $dsr;
		}
		Zymurgy::$db->free_result($ri);
		return $count;
	}
}

class DataGridValidator
{
	var $function;
	var $message;
	var $parameter;

	function DataGridValidator($function,$message,$parameter='')
	{
		$this->function = $function;
		$this->message = $message;
		$this->parameter = $parameter;
	}
}

/**
 * Represents a column in a DataGrid control.
 *
 */
class DataGridColumn
{
	/**
	 * The text to display in the Header row of the datagrid for this column.
	 *
	 * @var string
	 */
	var $headertxt;
	var $datacolumn;
	var $template;
	var $editcaption;
	var $editor;
	var $editortype;
	var $validator;
	var $default;

	function DataGridColumn($headertxt,$datacolumn,$template="{0}")
	{
		$this->headertxt=$headertxt;
		$this->datacolumn=$datacolumn;
		$this->template=$template;
	}
}

class DataGrid
{
	/**
	 * Represents the data to be consumed by the grid
	 *
	 * @var DataSet
	 */
	var $DataSet;
	var $name;
	var $rowsperpage;
	var $columns;
	var $editors;
	var $width;
	var $insertlabel;
	var $editlabel;
	var $deletelabel;
	var $lookups;
	var $constants;
	var $cssfile; //Passed to fckeditor if supplied
	var $fckeditorpath;
	var $fckeditorcss;
	var $UsePennies = false;
	var $SaveDrafts = true;
	var $CurrentEditRow = false;
	var $thumbs = array();
	var $pretext = array();

	function DataGrid(&$dataset,$name='')
	{
		$this->DataSet = &$dataset;
		$this->DataSet->DataGrid = &$this;
		$this->name = $name;
		$this->rowsperpage = 20;
		$this->columns = array();
		$this->editors = array();
		$this->lookups = array();
		$this->constants = array();
		$this->buttons = array();
		$this->insertlabel = "Insert a new Record";
		$this->editlabel = "Edit";
		$this->deletelabel = "Delete";

		//Set default fckeditorpath
		$this->fckeditorpath = getapplpath(__FILE__).'fckeditor/';
	}

	function &AddColumn($headertxt,$datacolumn,$template="{0}")
	{
		//If $datacolumn is the key then I can't use it for both edit and delete, not
		//to mention display.
		if (!(strpos($datacolumn,'.') !== false))
			$datacolumn = $this->DataSet->tables[0].".$datacolumn";
		//echo "ac[$headertxt,$datacolumn,$template]<br>";
		$col = new DataGridColumn($headertxt,$datacolumn,$template);
		$this->columns[] = $col;
		return $col;
	}

	function AddButton($linktext, $link, $action='')
	{
		$link = "<a $action href=\"$link\">$linktext</a>";
		$this->buttons[] = $link;
	}

	function &AddScrollingColumn($headertxt,$datacolumn,$width=300,$height=100)
	{
		return $this->AddColumn($headertxt,$datacolumn,"<div style='Overflow: auto; Width: $width; Height:$height'>{0}</div>");
	}

	/**
	 * Add a thumbnail column.  Automatically generates thumbs for uploaded images.
	 *
	 * @param string $headertxt
	 * @param string $datacolumn
	 * @param int $width
	 * @param int $height
	 * @param bool $fixedratio
	 * @return DataGridColumn
	 */
	function &AddThumbColumn($headertxt,$datacolumn,$width,$height,$fixedratio = true)
	{
		if (!(strpos($datacolumn,'.') !== false))
			$datacolumn = $this->DataSet->tables[0].".$datacolumn";
		$targetsize = "{$width}x{$height}";
		if (array_key_exists($datacolumn,$this->thumbs))
			$this->thumbs[$datacolumn][] = $targetsize;
		else
			$this->thumbs[$datacolumn] = array($targetsize);

		//Tweak Width and Height for Thumb
		$shrink = 1;
		$twidth = $width;
		$theight = $height;
		$maxw = array_key_exists('MaxGridImageWidth',Zymurgy::$config) ? Zymurgy::$config['MaxGridImageWidth'] : 100;
		$maxh = array_key_exists('MaxGridImageHeight',Zymurgy::$config) ? Zymurgy::$config['MaxGridImageHeight'] : 100;
		if ($twidth > $maxw)
		{
			$shrink = $maxw/$twidth;
			$theight = round($shrink * $theight);
			$twidth = $maxw;
		}
		if ($theight > $maxh)
		{
			$shrink = $maxh/$theight;
			$twidth = round($shrink * $twidth);
			$theight = $maxh;
		}
		list($ds,$dc) = explode('.',$datacolumn,2);
		$imgsrc = Zymurgy::getUrlPath("~file.php") . "?mime=auto&dataset=".urlencode($ds)."&datacolumn=".
			urlencode($dc)."&id={ID}&w=$twidth&h=$theight";
		//$returnurl = urlencode($this->BuildSelfReference(array(),array('action','deletekey','editkey','movefrom','movedirection')));
		if ($fixedratio)
			$fixedratio = 'true';
		else
			$fixedratio = 'false';

		$thumb = "<a onclick=\"aspectcrop_popup('$datacolumn','$targetsize',{ID},'{ID}$datacolumn',$fixedratio,'{$twidth}x{$theight}')\">".
			"<img id=\"{ID}$datacolumn.{$width}x{$height}\" src=\"$imgsrc\" alt=\"$headertxt\" style=\"cursor: pointer\" /></a>";
		return $this->AddColumn($headertxt,$this->DataSet->masterkey,$thumb);
	}

	function AddEditColumn()
	{
		$this->AddButton($this->editlabel,$this->BuildSelfReference(array(),array('action','deletekey','editkey','movefrom','movedirection'))."&editkey={0}");
	}

	function AddDeleteColumn()
	{
		$action = "onclick=\"return confirm_delete();\"";
		$this->AddButton($this->deletelabel,$this->BuildSelfReference(array(),array('action','deletekey','editkey','movefrom','movedirection'))."&deletekey={0}",$action);
	}

	function AddUpDownColumn($datacolumn)
	{
		if ($this->DataSet->DisplayOrder!='')
			die("This dataset already has a display order, and it can only be set once with AddUpDownColumn.");
		if (!(strpos($datacolumn,'.') !== false))
			$datacolumn = $this->DataSet->tables[0].".$datacolumn";
		$this->DataSet->DisplayOrder = $datacolumn;
		$this->AddButton("<img border=\"0\" alt=\"Up\" src=\"images/Top.gif\">",$this->BuildSelfReference(array(),array('editkey','deletekey','action','movefrom','movedirection'))."&movefrom={DO}&movedirection=-1000\"");
		$this->AddButton("<img border=\"0\" alt=\"Up\" src=\"images/Up.gif\">",$this->BuildSelfReference(array(),array('editkey','deletekey','action','movefrom','movedirection'))."&movefrom={DO}&movedirection=-1\"");
		$this->AddButton("<img border=\"0\" alt=\"Down\" src=\"images/Down.gif\">",$this->BuildSelfReference(array(),array('editkey','action','movefrom','movedirection','deletekey'))."&movefrom={DO}&movedirection=1\"");
		$this->AddButton("<img border=\"0\" alt=\"Down\" src=\"images/Bottom.gif\">",$this->BuildSelfReference(array(),array('editkey','action','movefrom','movedirection','deletekey'))."&movefrom={DO}&movedirection=1000\"");
		/*return $this->AddColumn("",$datacolumn,
			"<a href=\"".$this->BuildSelfReference(array(),array('editkey','deletekey','action','movefrom','movedirection'))."&movefrom={0}&movedirection=-1\">Up</a> <a href=\"".$this->BuildSelfReference(array(),array('editkey','action','movefrom','movedirection','deletekey'))."&movefrom={0}&movedirection=1\">Down</a>");*/
	}

	function &AddImageColumn($headertxt,$datacolumn)
	{
		return $this->AddColumn($headertxt,$datacolumn,"<img src='datagrid.php?datacolumn=".
			urlencode($datacolumn)."&mime={0}&id={ID}&dataset=".urlencode($this->DataSet->tables[0])."'>");
	}

	function AddEditor($datacolumn,$caption,$type, $default = "")
	{
		if (!(strpos($datacolumn,'.') !== false))
			$datacolumn = $this->DataSet->tables[0].".$datacolumn";
		$found = false;
		for ($n=0; $n<count($this->columns); $n++)
		{
			if ($this->columns[$n]->datacolumn==$datacolumn)
			{
				$found = true;
				break;
			}
		}
		if (!$found)
		{
			$this->AddColumn('',$datacolumn,'');
		}
		//$this->columns[$name] = new DataColumn($name,$quoted);
		if (!array_key_exists($datacolumn,$this->DataSet->columns))
			$this->DataSet->AddColumn($datacolumn,true);
		$tp = explode('.',$type,2);
		$pretext = InputWidget::GetPretext($type);
		if (!empty($pretext)) $this->pretext[$tp[0]] = $pretext;
		$this->columns[$n]->editcaption = $caption;
		$this->columns[$n]->editor = $type;
		//Create thumb entry for the image type
		$ep = explode('.',$type);
		$this->columns[$n]->editortype = $ep[0];
		$this->columns[$n]->default = $default;
		switch($ep[0])
		{
			case 'image':
				array_shift($ep); //Remove type
				$targetsize = str_replace('.','x',implode('.',$ep));
				//$targetsize = "{$ep[1]}x{$ep[2]}";
				if (array_key_exists($datacolumn,$this->thumbs))
					$this->thumbs[$datacolumn][] = $targetsize;
				else
					$this->thumbs[$datacolumn] = array($targetsize);
				break;
			case 'lookup':
				if (!array_key_exists($ep[1],$this->lookups))
				{
					$this->lookups[$ep[1]] = new DataGridLookup($ep[1],$ep[2],$ep[3],$ep[4]);
				}
				break;
		}
	}

	function AddValidator($datacolumn,$validator,$message,$parameter='')
	{
		if (!(strpos($datacolumn,'.') !== false))
			$datacolumn = $this->DataSet->tables[0].".$datacolumn";
		for ($n=0; $n<count($this->columns); $n++)
		{
			if ($this->columns[$n]->datacolumn==$datacolumn)
			{
				break;
			}
		}
		$v = new DataGridValidator($validator,$message,$parameter);
		$this->columns[$n]->validator = $v;
	}

	function AddConstant($datacolumn,$value,$addfilter = true)
	{
		if (!(strpos($datacolumn,'.') !== false))
			$datacolumn = $this->DataSet->tables[0].".$datacolumn";
		if ($addfilter)
		{
			$exists = false;
			foreach($this->DataSet->Filters as $dsf)
			{
				if ($dsf->columnname == $datacolumn)
					$exists = true;
			}
			if (!$exists)
				$this->DataSet->AddDataFilter($datacolumn,$value);
		}
		$this->constants[$datacolumn] = $value;
	}

	function AddInput($datacolumn,$caption,$maxlength=255,$size=15,$default = "")
	{
		$this->AddEditor($datacolumn,$caption,"input.$size.$maxlength", $default);
	}

	function AddPasswordInput($datacolumn,$caption,$maxlength=255,$size=15)
	{
		$this->AddEditor($datacolumn,$caption,"password.$size.$maxlength");
	}

	function AddTextArea($datacolumn,$caption,$width=50,$height=5)
	{
		$this->AddEditor($datacolumn,$caption,"textarea.$width.$height");
	}

	function AddHtmlEditor($datacolumn,$caption,$widthpx=600,$heightpx=400)
	{
		if ((array_key_exists('expert',$_GET)) && ($_GET['expert']==1))
			$this->AddEditor($datacolumn,$caption,"textarea.60.15");
		else
			$this->AddEditor($datacolumn,$caption,"html.$widthpx.$heightpx");
	}

	function AddYuiHtmlEditor($datacolumn,$caption,$widthpx=600,$heightpx=400)
	{
		if ((array_key_exists('expert',$_GET)) && ($_GET['expert']==1))
			$this->AddEditor($datacolumn,$caption,"textarea.60.15");
		else
			$this->AddEditor($datacolumn,$caption,"yuihtml.$widthpx.$heightpx");
	}

	function AddColorEditor($datacolumn,$caption)
	{
		$this->AddColourEditor($datacolumn,$caption);
	}

	function AddColourEditor($datacolumn,$caption)
	{
		$this->AddEditor($datacolumn,$caption,'colour');
	}

	function AddRadioEditor($datacolumn,$caption,$optionarray)
	{
		$this->AddEditor($datacolumn,$caption,"radio.".serialize($optionarray));
	}

	function AddDropListEditor($datacolumn,$caption,$optionarray)
	{
		$this->AddEditor($datacolumn,$caption,"drop.".serialize($optionarray));
	}

	function AddAttachmentEditor($datacolumn,$caption)
	{
		$this->AddEditor($datacolumn,$caption,"attachment");
	}

	function AddMoneyEditor($datacolumn,$caption)
	{
		$this->AddEditor($datacolumn,$caption,"money");
	}

	function AddUnixDateEditor($datacolumn,$caption,$time = false)
	{
		if (array_key_exists('expert',$_GET))
			$this->AddEditor($datacolumn,$caption,"input.15.255");
		else
		{
			if ($time)
				$this->AddEditor($datacolumn,$caption,"unixdatetime");
			else
				$this->AddEditor($datacolumn,$caption,"unixdate");
		}
	}

	function AddLookup(
		$datacolumn,
		$caption,
		$table,
		$idcolumn,
		$valcolumn,
		$ordercolumn='',
		$allowNulls = false)
	{
		$this->AddEditor(
			$datacolumn,
			$caption,
			"lookup.$table.$idcolumn.$valcolumn.$ordercolumn".
			($allowNulls ? ".checked" : ""));
	}

	function BuildSelfReference($getvars,$removevars=array())
	{
		$getvars = array_merge($_GET,$getvars);
		$keys = array_keys($getvars);
		$newkeys = array();
		foreach ($keys as $key)
		{
			if (!in_array($key,$removevars))
				$newkeys[$key] = urlencode($key)."=".urlencode($getvars[$key]);
		}
		return $_SERVER['PHP_SELF']."?".implode("&",$newkeys);
	}

	function RenderType()
	{
		if ((isset($_GET['editkey'])) ||
			((array_key_exists('action',$_GET)) && ($_GET['action']=='insert')))
			return 'Edit';
		elseif (isset($_GET['deletekey']))
			return 'Delete';
		elseif (isset($_GET['movefrom']))
			return 'Move';
		else
			return 'Grid';
	}

	function Render()
	{
		if (array_key_exists('page',$_GET))
			$page = trim($_GET['page']);
		else
			$page = 1;
		$rt = $this->RenderType();
		if (($rt == 'Edit') || ($rt=='Delete'))
		{
			if ($rt=='Edit')
				$keyname = 'editkey';
			else
				$keyname = 'deletekey';
			//Limit dataset fill to record in question
			//Also solves pagination problem when linking directly to editor
			if (array_key_exists($keyname,$_GET))
			{
				$editkey = 0 + $_GET[$keyname];
				$this->DataSet->AddDataFilter($this->DataSet->masterkey,$editkey);
			}
			else
				$editkey = '';
			$page = 1;
		}
		$start = ($page-1) * $this->rowsperpage;
		$count = $this->DataSet->fill($start,$this->rowsperpage);
		switch ($rt)
		{
			case 'Edit':
				$this->RenderEdit($page,$editkey);
				break;
			case 'Delete':
				$this->RenderDelete($page,$count);
				break;
			case 'Move':
				$this->RenderMove($page,$count);
				break;
			default:
				$this->RenderGrid($page,$count);
				break;
		}
	}

	function DeleteThumbs($datacolumn,$id)
	{
		global $ZymurgyRoot;

		//Delete associated thumb stuff
		$path = "$ZymurgyRoot/UserFiles/DataGrid/$datacolumn";
		$thumbs = glob("$path/{$id}thumb*");
		array_merge($thumbs,glob("$path/{$id}aspectcropDark.jpg"));
		array_merge($thumbs,glob("$path/{$id}aspectcropNormal.jpg"));
		array_merge($thumbs,glob("$path/{$id}raw.jpg"));
		foreach($thumbs as $thumb)
		{
			@unlink($thumb);
		}
	}

	function RenderDelete($page)
	{
		global $ZymurgyRoot;

		$id = 0 + $_GET['deletekey'];
		$dsr = &$this->DataSet->rows[$id];
		//Set constants for the benifit of any attached OnDelete events.
		$dsr->Edit();
		foreach ($this->constants as $datacolumn=>$value)
		{
			$dsr->SetValue($datacolumn,$value);
		}
		$delstatus = $dsr->Delete();
		//Delete associated files; thumbs and uploads.
		foreach ($this->columns as $c)
		{
			if (isset($c->editcaption))
			{
				if (($c->editor == "attachment") || ($c->editortype == "image"))
				{
					$datacolumn = $c->datacolumn;
					if (array_key_exists($datacolumn,$this->thumbs))
					{
						$this->DeleteThumbs($datacolumn,$id);
					}
					else
					{
						$path = Zymurgy::getFilePath("~uploads");
						@unlink("$path/$datacolumn.$id");
					}
				}
			}
		}
		header("Location: ".$this->BuildSelfReference($_GET,array('deletekey')));
	}

	function RenderMove($page,$count)
	{
		list ($tname,$column) = explode('.',$this->DataSet->DisplayOrder,2);
		//Ignore count and get the max(disporder) value instead.
		$ri = Zymurgy::$db->query("select max($column) from $tname");
		$count = Zymurgy::$db->result($ri,0,0);
		Zymurgy::$db->free_result($ri);
		$olddo = $_GET['movefrom'];
		//Get newdo from dataset->getwhere function and select for next/prev disporder.
		//Otherwise move breaks on filtered data.
		$where = $this->DataSet->getwhere();
		$direction = $_GET['movedirection'];
		if (abs($direction) == 1000)
		{ //Move to top or bottom
			if ($direction > 0)
			{
				$addsign = '-';
				$sign = '>';
				$newdo = $count;
			}
			else
			{
				$addsign = '+';
				$sign = '<';
				$newdo = 1;
			}
			Zymurgy::$db->run("update $tname set $column = 0 where $column = $olddo");
			Zymurgy::$db->run("update $tname set $column = $column $addsign 1 where ($column <> 0) and ($column $sign $olddo)");
			Zymurgy::$db->run("update $tname set $column = $newdo where $column = 0");
		}
		else
		{
			if (count($where)>0)
			{
				if ($direction>0)
				{
					$sign = ">";
					$sort = "asc";
				}
				else
				{
					$sign = "<";
					$sort = "desc";
				}
				$sql = "select $column from $tname where ($column $sign $olddo) and ".implode(' and ',$where).
					" order by $column $sort limit 1";
				$ri = Zymurgy::$db->query($sql);
				if (Zymurgy::$db->num_rows($ri) == 1)
					$newdo = Zymurgy::$db->result($ri,0,0);
				else
					$newdo = 0;
			}
			else
				$newdo = $olddo + $direction;
			if (($newdo > 0) && ($newdo <= $count))
			{
				Zymurgy::$db->query("update $tname set $column=0 where $column=$olddo");
				Zymurgy::$db->query("update $tname set $column=$olddo where $column=$newdo");
				Zymurgy::$db->query("update $tname set $column=$newdo where $column=0");
			}
		}
		$start = ($page-1) * $this->rowsperpage;
		$this->DataSet->Clear();
		$count = $this->DataSet->fill($start,$this->rowsperpage);
		unset($_GET['movefrom']);
		unset($_GET['movedirection']);
		$this->RenderGrid($page,$count);
	}

	function Exception($string)
	{
		if (ob_get_level()>0)
			ob_clean();
		echo "An unexpected error has occured:  $string";
		exit;
	}

	function MakeThumbs($datacolumn,$id,$targets,$uploadpath = '',$ext = 'jpg')
	{
		Zymurgy::MakeThumbs($datacolumn, $id, $targets, $uploadpath,$ext);
	}

	function RegenerateThumbs($ext='jpg')
	{
		$this->DataSet->fill();
		foreach ($this->DataSet->rows as $dsr)
		{
			foreach ($this->thumbs as $dc=>$size)
			{
				$this->MakeThumbs($dc,$dsr->values[$this->DataSet->masterkey],$size,'',$ext);
			}
		}
	}

	function RenderEdit($page,$editkey)
	{
		global $datagridexpertmode, $ZymurgyRoot;

		if ((array_key_exists('action',$_GET)) && ($_GET['action'] == 'insert'))
			$dsr = $this->DataSet->GetBlankRow();
		else
			$dsr = &$this->DataSet->rows[$editkey];
		$validmsg = '';
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			//Check the validators first
			$valids = array();
			foreach ($this->columns as $c)
			{
				if (is_object($c->validator))
				{
					$v = $c->validator;
					$postname = str_replace(".","_",$c->datacolumn);
					$func = $v->function;
					$vok = $func($_POST[$postname],$v->parameter);
					if (!$vok)
						$valids[] = "<li>".$v->message;
				}
			}

			//Go into Edit/Insert regardless of validation success.  We need to store the values
			//so that they don't dissappear when the form is re-generated on validation failure.
			if ((array_key_exists('action',$_GET)) && ($_GET['action'] == 'insert'))
				$dsr->Insert();
			else
				$dsr->Edit();
			if (count($valids)>0)
			{
				$validmsg = "<ul>".implode($valids)."</ul>";
				foreach ($this->columns as $c)
				{
					if (isset($c->editcaption))
					{
						$postname = str_replace(".","_",$c->datacolumn);
						if (($c->editor == "attachment") || ($c->editortype == "image"))
						{
							$file = $_FILES[$postname];
							if ($file['type']!='')
							{
								$dsr->SetValue($c->datacolumn,$file['type']);
							}
						}
						else
						{
							$dsr->SetValue($c->datacolumn,$_POST[$postname]);
						}
					}
				}
			}
			else
			{
				unset($validmsg);
				//Save the data, remove editkey from $_GET and render the grid
				//foreach($_POST as $k=>$v) echo "$k: $v<br>";
				$uploads = array();
				$widget = new InputWidget();
				foreach ($this->columns as $c)
				{
					if (isset($c->editcaption))
					{
						$postname = str_replace(".","_",$c->datacolumn);
						$widget->UsePennies = $this->UsePennies;
						$widget->lookups = $this->lookups;
						if (isset($this->OnBeforeAutoInsert))
							$widget->OnBeforeAutoInsert = $this->OnBeforeAutoInsert;
						if (isset($this->OnAutoInsert))
							$widget->OnAutoInsert = $this->OnAutoInsert;
						$postvalue = $widget->PostValue($c->editor,$postname);
						if (($c->editor == "attachment") || ($c->editortype == "image"))
						{
							$file = $_FILES[$postname];
							switch ($file['error'])
							{
							   case UPLOAD_ERR_OK:
							       break;
							   case UPLOAD_ERR_INI_SIZE:
							       $this->Exception("The uploaded file exceeds the upload_max_filesize directive (".ini_get("upload_max_filesize").") in php.ini.");
							       break;
							   case UPLOAD_ERR_FORM_SIZE:
							       $this->Exception("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
							       break;
							   case UPLOAD_ERR_PARTIAL:
							       $this->Exception("The uploaded file was only partially uploaded.");
							       break;
							   case UPLOAD_ERR_NO_FILE:
							       //$this->Exception("No file was uploaded."); Safe to ignore
							       break;
							   case UPLOAD_ERR_NO_TMP_DIR:
							       $this->Exception("Missing a temporary folder.");
							       break;
							   case UPLOAD_ERR_CANT_WRITE:
							       $this->Exception("Failed to write file to disk");
							       break;
							   default:
							       $this->Exception("Unknown File Error");
							}
							if ($file['type']!='')
								$uploads[$c->datacolumn] = $file['tmp_name'];
							else
								$postvalue = $dsr->originalvalues[$c->datacolumn];
						}
						$dsr->SetValue($c->datacolumn,$postvalue);
					}
				}
				foreach ($this->constants as $datacolumn=>$value)
				{
					$dsr->SetValue($datacolumn,$value);
				}
				$id = $dsr->Update();
				//print_r($_FILES); exit;
				$uploadfolder = Zymurgy::getFilePath("~uploads");
				foreach ($uploads as $dc=>$upload)
				{
					if (array_key_exists($dc,$this->thumbs))
					{
						require_once(Zymurgy::getFilePath("~include/Thumb.php"));
						$ext = Thumb::mime2ext($file['type']);
						$this->MakeThumbs($dc,$dsr->values[$this->DataSet->masterkey],$this->thumbs[$dc],$upload,Thumb::mime2ext($file['type']));
					}
					else if (!@move_uploaded_file($upload,"$uploadfolder/$dc.$id"))
					{
						echo "Failed to write $uploadfolder/$dc.$id<!--";
						print_r($file);
						echo "-->";
						exit;
					}
				}
				if (array_key_exists('action',$_GET) && ($_GET['action'] == 'insert'))
				{
					$start = ($page-1) * $this->rowsperpage;
					$this->DataSet->Clear();
				}
				if ($this->SaveDrafts)
				{
					//Save "keeper" draft for commited changes.
					$json = array();
					foreach ($this->columns as $c)
					{
						$key = str_replace('"','\"',$c->datacolumn);
						$value = str_replace('"','\"',$dsr->values[$c->datacolumn]);
						$json[$key] = "\"$key\":\"$value\"";
					}
					ksort($json);
					/*foreach($dsr->values as $key=>$value)
					{
						$key = str_replace('"','\"',$key);
						$value = str_replace('"','\"',$value);
						$json[] = "\"$key\":\"$value\"";
					}*/
					$sql = "insert into zcm_draft (saved,form,keeper,json) values (now(),'".
						Zymurgy::$db->escape_string($this->DataSet->masterkey).'-'.$dsr->values[$this->DataSet->masterkey]."',1,'".
						Zymurgy::$db->escape_string('{'.implode(',',$json).'}')."')";
					Zymurgy::$db->query($sql) or die("Can't save draft ($sql): ".Zymurgy::$db->error());
				}
				if ($id !== false)
				{
					if (!isset($this->customSaveLocation))
						$this->customSaveLocation = $this->BuildSelfReference(array(),array('action','editkey'));
					header('Location: '.$this->customSaveLocation);
				}
				else
					$validmsg = $dsr->invalidmsg;
			}
		}
		if (isset($validmsg))
		{ //Show the input form
			echo $validmsg;
			if ($datagridexpertmode)
			{
				echo "<p>";
				if ($_GET['expert']==1)
					echo "<a href='".$this->BuildSelfReference(array(),array('expert','action','deletekey','movefrom','movedirection'))."'>Return to normal mode</a>";
				else
					echo "<a href='".$this->BuildSelfReference(array(),array('action','deletekey','movefrom','movedirection'))."&expert=1'>Expert mode</a>";
				echo "</p>";
			}
			echo implode($this->pretext); //Useful to initialize certain javascript widgets.
			$formid = array_key_exists($this->DataSet->masterkey,$dsr->values) ?
				$this->DataSet->masterkey.'-'.$dsr->values[$this->DataSet->masterkey] :
				$this->DataSet->masterkey;
			if ($this->SaveDrafts)
			{
				//Count fckeditors so we can get our initial state after they have rendered.
				$fckcount = 0;
				foreach ($this->columns as $c)
				{
					$exploded = explode('.',$c->editor,2);
					$editor = array_shift($exploded);
					if ($editor=='html')
					{
						$fckcount++;
					}
				}

				echo Zymurgy::YUI("yuiloader/yuiloader-min.js");

				echo "<script src=\"" . Zymurgy::getUrlPath("~include/autosave.js") . "\"></script>\r\n";
				echo "<script>InitializeAutoSave('$formid',$fckcount);</script>\r\n";
			}
			echo "<form id=\"$formid\" name=\"datagridform\" method=\"post\" enctype=\"multipart/form-data\" action=\"{$_SERVER['REQUEST_URI']}\">\r\n";
			echo "<table>\r\n";
			$donecalcs = false;
			$fck = array();
			if (isset($dsr->DataSet->OnPreRenderEdit))
				$dsr->values = call_user_func($dsr->DataSet->OnPreRenderEdit,$dsr->values);

			$widget = new InputWidget();
			$js = array();
			foreach ($this->columns as $c)
			{
				$thisjs = $widget->JSRender($c->editor,$c->datacolumn,array_key_exists($c->datacolumn,$dsr->values) ? $dsr->values[$c->datacolumn] : '');
				if (!empty($thisjs))
					$js[] = $thisjs;
			}
			if ($js)
			{
				echo "<script type=\"text/javascript\">\r\n";
				echo implode("\r\n",$js);
				echo "</script>";
			}
			foreach ($this->columns as $c)
			{
//				echo("<pre>".print_r($c, true)."</pre>");
				if (isset($c->editcaption))
				{
					echo "<tr><td align=right>{$c->editcaption}</td><td id=\"cell-{$c->datacolumn}\">";
					$widget->fckeditorpath = $this->fckeditorpath;
					$widget->UsePennies = $this->UsePennies;
					$widget->fckeditorcss = $this->fckeditorcss;
					$widget->lookups = $this->lookups;
					$widget->datacolumn = $c->datacolumn;
					$widget->editkey = array_key_exists('editkey',$_GET) ? 0 + $_GET['editkey'] : 0;
					$widget->Render(
						$c->editor,
						$c->datacolumn,
						array_key_exists($c->datacolumn,$dsr->values) ? $dsr->values[$c->datacolumn] : $c->default);
					echo "</td></tr>\r\n";
				}
			}

			echo "<tr><td align=\"middle\" colspan=\"2\"><input id=\"submitForm\" type=\"submit\" value=\"Save\">";

			if (!isset($this->customCancelLocation))
				$this->customCancelLocation = $this->BuildSelfReference(array(),array('action','deletekey','editkey','movefrom','movedirection'));
			$cancellink = $this->customCancelLocation;
			echo "&nbsp;&nbsp;<input type=\"button\" value=\"Cancel\" onClick=\"document.location.href='$cancellink';\"></td></tr>\r\n";
			echo "</table>\r\n</form>\r\n";
		}
	}

	function RenderContextMenuItem($name,$action)
	{
		echo "<li class=\"yuimenuitem\">";
		echo "<a class=\"yuimenuitemlabel\" href=\"$action\">$name</a>";
		echo "</li>";
	}

	function RenderContextMenu($row)
	{
		$id = $row->values[$this->DataSet->masterkey];
		$elid = "zcmdgt$id";
		echo "<td>";
		echo "<img id=\"$elid\" src=\"" . Zymurgy::getUrlPath("~images/Context.gif") . "\" alt=\"Context Menu\" width=\"12\" height=\"12\" />";
		echo "</td>";
		return $elid;
	}

	function RenderGrid($page,$count)
	{
		global $hasdumpeddatagridcss;

		if (!$hasdumpeddatagridcss) DumpDataGridCSS();
		//Context menu YUI requirements
		echo Zymurgy::YUI('fonts/fonts-min.css');
		echo Zymurgy::YUI('menu/assets/skins/sam/menu.css');
		echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo Zymurgy::YUI('container/container_core-min.js');
		echo Zymurgy::YUI('menu/menu-min.js');
		echo Zymurgy::RequireOnce(Zymurgy::getUrlPath('~include/datagrid.js'));
		echo "<table cellspacing=\"0\" cellpadding=\"3\" rules=\"cols\" bordercolor=\"#999999\" border=\"1\" class=\"DataGrid\"";
		if (isset($this->width)) echo " width=\"{$this->width}\"";
		echo "><tr class=\"DataGridHeader\">\r\n";
		//Show empty context menu button column header
		//echo "<td></td>";
		//Show data columns
		$colcount = 0;
		foreach ($this->columns as $c)
		{
			if ($c->template == '') continue;
			$colcount++;
			$sa = array('sortcolumn'=>$c->datacolumn);
			if ($this->DataSet->DisplayOrder != '')
				echo "<td>{$c->headertxt}</td>";
			else
			{
				if (array_key_exists('sortcolumn',$_GET))
				{
					if (key_exists('sortorder',$_GET) && ($_GET['sortcolumn'] == $c->datacolumn) && ($_GET['sortorder'] != 'DESC'))
						$sa['sortorder'] = 'DESC';
					else
						$sa['sortorder'] = 'ASC';
				}
				echo "<td><a class=\"DataGrid\" href=\"".
						$this->BuildSelfReference($sa).
						"\">".$c->headertxt."</a></td>";
			}
		}
		if (count($this->buttons)>0)
		{
			echo "<td></td>"; //Empty header for link buttons
			$colcount++;
		}
		echo "</tr>\r\n";
		$alternate = false;
		$widget = new InputWidget();
		foreach ($this->DataSet->rows as $row)
		{
			if ($alternate)
				$trclass = "DataGridRowAlternate";
			else
				$trclass = "DataGridRow";
			$alternate = !$alternate;
			echo "<tr class=\"$trclass\">";
			foreach ($this->columns as $c)
			{
				if($c->template == "member")
				{
					$sql = "SELECT COALESCE(`fullname`, `email`) FROM `zcm_member` WHERE `id` = '".
						Zymurgy::$db->escape_string($row->values[$c->datacolumn]).
						"' LIMIT 0, 1";
					$memberName = Zymurgy::$db->get($sql);

					echo("<td><a href=\"editmember.php?action=edit_member&amp;id=".
						$row->values[$c->datacolumn].
						"\">".
						$memberName.
						"</a></td>");
				}
				else if ($c->template != '')
				{
					$widget->lookups = $this->lookups;
					$widget->UsePennies = $this->UsePennies;
					$display = $widget->Display($c->editor,$c->template,$row->values[$c->datacolumn],$row->values[$this->DataSet->masterkey]);
					if (isset($this->OnBeforeRenderCell))
						$display = call_user_func($this->OnBeforeRenderCell,$c->datacolumn,$row->values,$display);
					echo "<td>$display</td>";
				}
			}
			if (count($this->buttons)>0)
			{
				echo "<td align=\"right\">";
				$btntxt = str_replace(array("{0}","{DO}"),
					array($row->values[$this->DataSet->masterkey],
					(array_key_exists($this->DataSet->DisplayOrder,$row->values) ? $row->values[$this->DataSet->DisplayOrder] : '')),
					implode("&nbsp;|&nbsp;",$this->buttons));
				echo "<div style=\"width=100%\">$btntxt</div>";
				echo "</td>";
			}
			echo "</tr>\r\n";
		}
		echo "<tr class=\"DataGridHeader\"><td colspan=\"$colcount\">";
		$pagecount = ceil($count/$this->rowsperpage);
		if ($page>1)
			$prev = $this->BuildSelfReference(array('page'=>$page-1));
		else
			$prev = "#";
		$prev = "<a class=\"DataGrid\" href=\"$prev\">Prev</a>";
		if ($page < $pagecount)
			$next = $this->BuildSelfReference(array('page'=>$page+1));
		else
			$next = "#";
		$next = "<a class=\"DataGrid\" href=\"$next\">Next</a>";
		$jumppage = array();
		for ($n = 1; $n <= $pagecount; $n++)
		{
			$tj = "<option value=\"".$this->BuildSelfReference(array('page'=>$n))."\"";
			if ($n == $page)
				$tj .= " selected";
			$tj .= ">$n";
			$jumppage[] = $tj;
		}
		$jump = "<select onChange=\"location.href=this.options[this.selectedIndex].value\">\r\n".
			implode("\r\n",$jumppage)."</select>\r\n";
		echo "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">";
		if ($pagecount > 1)
		{
			echo "<tr><td align=\"left\">$prev&nbsp;</td>";
			echo "<td align=\"middle\" style=\"text-align: center;\"><font color=\"white\">Go to Page</font> $jump</td>";
			echo "<td align=\"right\">&nbsp;$next</td></tr>";
		}
		if ($this->insertlabel <> '')
			echo "<tr><td colspan=\"3\" align=\"middle\" style=\"text-align: center;\"><a href='".$this->BuildSelfReference(array('action'=>'insert'))."'><font color=\"white\">{$this->insertlabel}</font></a></td></tr>";
		echo "</table></td>";
		echo "</tr>\r\n";
		echo "</table>\r\n";
	}
}

$hasdumpeddatagridcss = false;

function DumpDataGridCSS()
{
	if (array_key_exists('gridcss',Zymurgy::$config))
		$gridcss = Zymurgy::$config['gridcss'];
	else
		$gridcss = "~include/datagrid.css";

	if(strpos($gridcss, "{") == FALSE)
	{
		echo '<link href="' . Zymurgy::getUrlPath($gridcss) . '" rel="stylesheet" type="text/css" />';
	}
	else
	{
		echo "<style><!--\n{$gridcss}\n--></style>\n";
	}

}

//Tried putting these in a class, but PHP4 won't call static methods as "variable functions".  So here we are.
function ValidatorRequired($input,$parameter)
{
	return ($input != '');
}

function ValidatorMoney($input,$parameter)
{
	$m = str_replace(array('$',','),'',$input);
	$data=split('[.]',$m);

	if ( count($data) != 2 )
	{
		return false;
	}
	if ( ctype_digit($data[0]) && ctype_digit($data[1]) )
	{
		return true;
	}
	else
	{
		return false;
	}
}

function ValidateTitle($input,$parameter)
{
	$m = str_replace(' ','',$input);
	if ( $m == '')
	{
		return false;
	}
	else
	{
		return true;
	}
}

?>
