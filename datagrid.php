<? 
/* DataGrid classes
 * Copyright(c) 2006 by Zymurgy Systems Inc. http://www.zymsys.com/
 * All rights reserved.
 *
 * Some of the GET variables used by the grid include:
 *	sortcolumn: Data column name for the sort column
 *	sortorder: ASC or DESC
 *	page: Current page of datagrid
 *  editkey: Key of the column being edited, -1 for insert (not set when not in edit mode)
*/ 

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

require_once("$ZymurgyRoot/zymurgy/InputWidget.php");

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
		
		function aspectcrop_popup(ds, d, id, returnurl, fixedratio)
		{
			var fixedar;
			if (fixedratio)
				fixedar = '&fixedar=1';
			else
				fixedar = '';
			window.open('/zymurgy/aspectcrop.php?ds='+ds+'&d='+d+'&id='+id+'&returnurl='+returnurl+fixedar,'','scrollbars=no,width=780,height=500');
		}
//-->
</script>
<?
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

class DataColumn
{
	var $name;
	var $quoted;
	
	function DataColumn($name,$quoted)
	{
		$this->name = $name;
		$this->quoted = $quoted;
	}
}

class DataSetRow
{
	var $originalvalues;
	var $values;
	var $dirty; //Has been changed
	var $DataSet;
	var $state; //NORMAL or EDITING
	var $edittype; //Blank, INSERT or UPDATE
	var $invalidmsg; //Set when OnBeforeInsert or OnBeforeUpdate fail with an error or validation message
	
	function DataSetRow()
	{
		$this->dirty = false;
		$this->values = array();
		$this->originalvalues = array();
		$this->state = 'NORMAL';
		$this->edittype = '';
	}
	
	function SetValue($columnname,$value)
	{
		if ($this->state != 'EDITING')
		{
			echo "<hr>Attempt to edit DataSetRow that isn't in EDITING state.<hr>";
			return;
		}
		$this->values[$columnname] = $value;
		$this->dirty = true;
	}
	
	function Cancel()
	{
		$this->values = $this->originalvalues;
		$this->state = 'NORMAL';
		$this->edittype = '';
	}
	
	function Edit()
	{
		$this->state = 'EDITING';
		$this->edittype = 'UPDATE';
	}
	
	function Insert()
	{
		$this->state = 'EDITING';
		$this->edittype = 'INSERT';
	}
	
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
	
	function Delete()
	{
		if (isset($this->DataSet->OnDelete))
		{
			if (call_user_func($this->DataSet->OnDelete,$this->values) === false) 
				return false; //Callback can abort delete
		}
		list($tables,$tablekeys) = $this->GetMyTables();
		foreach ($tables as $tname=>$values)
		{
			$sql = "delete from $tname where {$tablekeys[$tname]}={$_GET['deletekey']}";
			$ri = Zymurgy::$db->query($sql);
		}
		return $ri;
	}
	
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
			return false; //Allow before events to abort the insert or update 
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
			$clist = array();
			$vlist = array();
			$alist = array();
			foreach ($values as $cname=>$val)
			{
				$clist[] = "`$cname`";
				if (!key_exists("$tname.$cname",$this->DataSet->columns))
					$this->DataSet->AddColumn($cname,true); //Auto create missing columns
				if ($this->DataSet->columns["$tname.$cname"]->quoted)
				{
					$vlist[] = "'".Zymurgy::$db->escape_string($val)."'";
					$alist[] = "`$cname`='".Zymurgy::$db->escape_string($val)."'";
				}
				else 
				{
					$vlist[] = $val;
					$alist[] = "`$cname`=$val";
				}
			}
			if ($this->edittype == 'UPDATE')
			{
				if ($this->DataSet->columns["$tname.{$tablekeys[$tname]}"]->quoted)
					$keyval = "'".Zymurgy::$db->escape_string($this->values["$tname.{$tablekeys[$tname]}"])."'";
				else
					$keyval = $this->values["$tname.{$tablekeys[$tname]}"];
				$sql = "update $tname set ".implode(',',$alist)." where {$tablekeys[$tname]}=$keyval";
				if ($rid==0) 
					$rid = $this->values["$tname.{$tablekeys[$tname]}"];
			}
			else 
			{
				$sql = "insert into $tname (".implode(",",$clist).") values (".
					implode(",",$vlist).")";
			}
			//echo $sql;
			$ri = Zymurgy::$db->query($sql);
			if ($ri === false)
			{
				echo "Error updating record: ".Zymurgy::$db->error()." [$sql]";
				exit;
			}
			if ($rid==0) 
			{
				$rid = Zymurgy::$db->insert_id();
			}
			$this->values[$this->DataSet->masterkey] = $rid;
			//echo "[rid:$rid][key:{$this->DataSet->masterkey}][sql:$sql]";exit;
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
				call_user_func($this->DataSet->OnUpdate,@$this->values);
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

class DataRelationship
{
	var $mastertable;
	var $mastercolumn;
	var $detailtable;
	var $detailcolumn;
	
	function DataRelationship($mastertable,$mastercolumn,$detailtable,$detailcolumn)
	{
		$this->mastertable = $mastertable;
		$this->mastercolumn = $mastercolumn;
		$this->detailtable = $detailtable;
		$this->detailcolumn = $detailcolumn;
	}
}

class DataSetFilter
{
	var $columnname;
	var $value;
	var $operator;
	
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
	var $tables;
	var $columns;
	var $rows;
	var $editrow;
	var $relationships;
	var $fetchrows;
	var $DisplayOrder;
	var $Filters;
	var $ExtraSQL; //Used for full text queries
	
	//Define these events to hook into them
	//var $OnInsert($dataset)
	//var $OnUpdate($dataset)
	
	function DataSet($mastertable,$masterkey)
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

	function AddDataFilter($columnname,$value,$operator="=")
	{
		if (!(strpos($columnname,'.')!==false))
			$columnname = $this->tables[0].".$columnname";
		$this->Filters[] = new DataSetFilter($columnname,$value,$operator);
	}
	
	function Clear()
	{
		//$this->columns = array();
		$this->rows = array();
	}
	
	function AddTable($detailtable,$detailcolumn)
	{
		$this->tables[] = $detailtable;
		$kp = explode(".",$this->masterkey,2);
		$this->relationships[$detailtable] = new DataRelationship($kp[0],$kp[1],$detailtable,$detailcolumn);
	}
	
	function AddColumn($name,$quoted)
	{
		if (!(strpos($name,'.')!==false))
			$name = $this->tables[0].".$name";
		$this->columns[$name] = new DataColumn($name,$quoted);
	}
	
	function AddColumns()
	{
		$args = func_get_args();
		foreach ($args as $colname)
			$this->AddColumn($colname,true);
	}
	
	function GetBlankRow()
	{
		$r = new DataSetRow();
		$r->DataSet = &$this;
		return $r;
	}
	
	function getwhere()
	{
		$where = array();
		foreach ($this->Filters as $f)
		{
			if (!array_key_exists($f->columnname,$this->columns))
			{
				die("You have declared a filter for an undeclared column: {$f->columnname}.");
			}
			if ($this->columns[$f->columnname]->quoted)
				$value = "'{$f->value}'";
			else
				$value = $f->value;
			$where[] = "({$f->columnname} {$f->operator} $value)";
		}
		return $where;
	}
	
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
		$csql = "select count(*)".$sql;
		$ri = Zymurgy::$db->query($csql);
		if (!$ri)
		{
			echo "<hr>".Zymurgy::$db->error()."<p>$csql<hr>";
			exit;
		}
		$count = Zymurgy::$db->result($ri,0,0);

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

class DataGridColumn
{
	var $headertxt;
	var $datacolumn;
	var $template;
	var $editcaption;
	var $editor;
	var $editortype;
	var $validator;
	
	function DataGridColumn($headertxt,$datacolumn,$template="{0}")
	{
		$this->headertxt=$headertxt;
		$this->datacolumn=$datacolumn;
		$this->template=$template;
	}
}

class DataGrid
{
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
	var $CurrentEditRow = false;
	var $thumbs = array();
	var $pretext = array();
	
	function DataGrid(&$dataset,$name='')
	{
		$this->DataSet = &$dataset;
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
		$this->columns[] = &$col;
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
		//$imgsrc = "/UserFiles/DataGrid/$datacolumn/{ID}thumb$targetsize.jpg";
		list($ds,$dc) = explode('.',$datacolumn,2);
		$imgsrc = "/zymurgy/file.php?mime=image/jpeg&dataset=".urlencode($ds)."&datacolumn=".
			urlencode($dc)."&id={ID}&w=$width&h=$height";
		//$returnurl = urlencode($this->BuildSelfReference(array(),array('action','deletekey','editkey','movefrom','movedirection')));
		if ($fixedratio)
			$fixedratio = 'true';
		else
			$fixedratio = 'false';
		$thumb = "<a onclick=\"aspectcrop_popup('$datacolumn','$targetsize',{ID},'{ID}$datacolumn',$fixedratio)\">".
			"<img id=\"{ID}$datacolumn.{$width}x{$height}\" src=\"$imgsrc\" alt=\"$headertxt\" /></a>";
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
		$this->AddButton("<img border=\"0\" alt=\"Up\" src=\"images/Up.gif\">",$this->BuildSelfReference(array(),array('editkey','deletekey','action','movefrom','movedirection'))."&movefrom={DO}&movedirection=-1\"");
		$this->AddButton("<img border=\"0\" alt=\"Down\" src=\"images/Down.gif\">",$this->BuildSelfReference(array(),array('editkey','action','movefrom','movedirection','deletekey'))."&movefrom={DO}&movedirection=1\"");
		/*return $this->AddColumn("",$datacolumn,
			"<a href=\"".$this->BuildSelfReference(array(),array('editkey','deletekey','action','movefrom','movedirection'))."&movefrom={0}&movedirection=-1\">Up</a> <a href=\"".$this->BuildSelfReference(array(),array('editkey','action','movefrom','movedirection','deletekey'))."&movefrom={0}&movedirection=1\">Down</a>");*/
	}
	
	function &AddImageColumn($headertxt,$datacolumn)
	{
		return $this->AddColumn($headertxt,$datacolumn,"<img src='datagrid.php?datacolumn=".
			urlencode($datacolumn)."&mime={0}&id={ID}&dataset=".urlencode($this->DataSet->tables[0])."'>");
	}
	
	function AddEditor($datacolumn,$caption,$type)
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
		$tp = explode('.',$type,2);
		$pretext = InputWidget::GetPretext($type);
		if (!empty($pretext)) $this->pretext[$tp[0]] = $pretext;
		$this->columns[$n]->editcaption = $caption;
		$this->columns[$n]->editor = $type;
		//Create thumb entry for the image type
		$ep = explode('.',$type);
		$this->columns[$n]->editortype = $ep[0];
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
				if (!array_key_exists($table,$this->lookups)) 
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
	
	function AddConstant($datacolumn,$value)
	{
		if (!(strpos($datacolumn,'.') !== false))
			$datacolumn = $this->DataSet->tables[0].".$datacolumn";
		$this->constants[$datacolumn] = $value;
	}
	
	function AddInput($datacolumn,$caption,$maxlength=255,$size=15)
	{
		$this->AddEditor($datacolumn,$caption,"input.$size.$maxlength");
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

	function AddLookup($datacolumn,$caption,$table,$idcolumn,$valcolumn,$ordercolumn='')
	{
		$this->AddEditor($datacolumn,$caption,"lookup.$table.$idcolumn.$valcolumn.$ordercolumn");
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
		@unlink("$path/{$id}aspectcropDark.jpg");
		@unlink("$path/{$id}aspectcropNormal.jpg");
		@unlink("$path/{$id}raw.jpg");
		$thumbs = glob("$path/{$id}thumb*");
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
						$path = "$ZymurgyRoot/zymurgy/uploads";
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
			$start = ($page-1) * $this->rowsperpage;
			$this->DataSet->Clear();
			$count = $this->DataSet->fill($start,$this->rowsperpage);
		}
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
	
	function MakeThumbs($datacolumn,$id,$targets,$uploadpath = '')
	{
		global $ZymurgyRoot, $ZymurgyConfig;
		
		@mkdir("$ZymurgyRoot/UserFiles/DataGrid");
		$thumbdest = "$ZymurgyRoot/UserFiles/DataGrid/$datacolumn";
		@mkdir($thumbdest);
		$rawimage = "$thumbdest/{$id}raw.jpg";
		if ($uploadpath!=='')
			move_uploaded_file($uploadpath,$rawimage);
		if ((function_exists('mime_content_type')) && (mime_content_type($rawimage)!='image/jpeg'))
		{
			//Supplied image isn't a jpeg.  Convert raw into one (best effort!).
			system("{$ZymurgyConfig['ConvertPath']}convert $rawimage $thumbdest/{$id}jpg.jpg");
			rename("$thumbdest/{$id}jpg.jpg",$rawimage);
		}
		require_once("$ZymurgyRoot/zymurgy/include/Thumb.php");
		//echo "[Targets: "; print_r($targets); echo "]";
		foreach($targets as $targetsizes)
		{
			$targetsizes = explode(',',$targetsizes);
			foreach ($targetsizes as $targetsize)
			{
				$dimensions = explode('x',$targetsize);
				Thumb::MakeFixedThumb($dimensions[0],$dimensions[1],$rawimage,"$thumbdest/{$id}thumb$targetsize.jpg");
			}
		}
		Thumb::MakeQuickThumb(640,480,$rawimage,"$thumbdest/{$id}aspectcropNormal.jpg");
		system("{$ZymurgyConfig['ConvertPath']}convert -modulate 75 $thumbdest/{$id}aspectcropNormal.jpg $thumbdest/{$id}aspectcropDark.jpg");
	}
	
	function RegenerateThumbs()
	{
		$this->DataSet->fill();
		foreach ($this->DataSet->rows as $dsr)
		{
			foreach ($this->thumbs as $dc=>$size)
			{
				$this->MakeThumbs($dc,$dsr->values[$this->DataSet->masterkey],$size);
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
				foreach ($this->columns as $c)
				{
					if (isset($c->editcaption))
					{
						$postname = str_replace(".","_",$c->datacolumn);
						$widget = new InputWidget();
						$widget->UsePennies = $this->UsePennies;
						$widget->lookups = $this->lookups;
						$postvalue = $widget->PostValue($c->editor,$postname);
						//echo "[$postname,{$c->editor},$postvalue]";
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
				$uploadfolder = "$ZymurgyRoot/zymurgy/uploads";
				foreach ($uploads as $dc=>$upload)
				{
					if (array_key_exists($dc,$this->thumbs))
					{
						$this->MakeThumbs($dc,$dsr->values[$this->DataSet->masterkey],$this->thumbs[$dc],$upload);
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
			echo "<form name=\"datagridform\" method=\"post\" enctype=\"multipart/form-data\" action=\"{$_SERVER['REQUEST_URI']}\">\r\n";
			echo "<table>\r\n";
			$donecalcs = false;
			$fck = array();
			if (isset($dsr->DataSet->OnPreRenderEdit)) 
				$dsr->values = call_user_func($dsr->DataSet->OnPreRenderEdit,$dsr->values);
			foreach ($this->columns as $c)
			{
				if (isset($c->editcaption))
				{
					echo "<tr><td align=right>{$c->editcaption}</td><td>";
					$widget = new InputWidget();
					$widget->fckeditorpath = $this->fckeditorpath;
					$widget->UsePennies = $this->UsePennies;
					$widget->fckeditorcss = $this->fckeditorcss;
					$widget->lookups = $this->lookups;
					$widget->Render($c->editor,$c->datacolumn,
						array_key_exists($c->datacolumn,$dsr->values) ? $dsr->values[$c->datacolumn] : '');
					echo "</td></tr>\r\n";
				}
			}
			echo "<tr><td align=\"middle\" colspan=\"2\"><input type=\"submit\" value=\"Save\">";
			if (!isset($this->customCancelLocation))
				$this->customCancelLocation = $this->BuildSelfReference(array(),array('action','deletekey','editkey','movefrom','movedirection'));
			$cancellink = $this->customCancelLocation;
			echo "&nbsp;&nbsp;<input type=\"button\" value=\"Cancel\" onClick=\"document.location.href='$cancellink';\"></td></tr>\r\n";
			echo "</table>\r\n</form>\r\n";
		}
	}
	

	function RenderGrid($page,$count)
	{
		global $hasdumpeddatagridcss;
		
		if (!$hasdumpeddatagridcss) DumpDataGridCSS();
		echo "<table cellspacing=\"0\" cellpadding=\"3\" rules=\"cols\" bordercolor=\"#999999\" border=\"1\" class=\"DataGrid\"";
		if (isset($this->width)) echo " width=\"{$this->width}\"";
		echo "><tr class=\"DataGridHeader\">\r\n";
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
				if ($c->template != '') 
				{
					$widget = new InputWidget();
					$widget->lookups = $this->lookups;
					$widget->UsePennies = $this->UsePennies;
					$display = $widget->Display($c->editor,$c->template,$row->values[$c->datacolumn],$row->values[$this->DataSet->masterkey]);
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

function DumpDataGridCSS($schemename='')
{
	global $ZymurgyConfig;
	
	if (is_array($ZymurgyConfig) && (array_key_exists('gridcss',$ZymurgyConfig)))
		$gridcss = $ZymurgyConfig['gridcss'];
		
	echo '<style type="text/css">
<!--
';
	switch ($schemename) 
	{
		case 'blank':
			break;
		default:
			if (!isset($gridcss))
				$gridcss = "table.DataGrid {
	background-color:White;
	border-color:#999999;
	border-width:1px;
	border-style:None;
	border-collapse:collapse;
}
tr.DataGridHeader {
	color:#000000;
	background-color:#999999;
	font-weight:bold;
}
tr.DataGridRow {
	color:Black;
	background-color:#FFFFFF;
}
tr.DataGridRowAlternate {
	color:Black;
	background-color:#cccccc;
}
a.DataGrid {
	color:White;
}
";
			echo $gridcss;
	}
	echo '-->
</style>
';
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
