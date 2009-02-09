<?php 
require_once('InputWidget.php');
require_once('cmo.php');

class ZymurgyMemberDataTable
{
	/**
	 * Child tables.  This is an array of ZymurgyMemberDataTable instances.
	 *
	 * @var array
	 */
	public $children = array();
	
	/**
	 * Parent table
	 *
	 * @var ZymurgyMemberDataTable
	 */
	public $parent = null;
	
	/**
	 * Fields for this table.  This is an array of ZymurgyMemberDataField instances.
	 *
	 * @var array
	 */
	private $fields = array();
	
	/**
	 * ID number from zcm_customtable identifying this table's meta data
	 *
	 * @var int
	 */
	private $tableid;
	
	/**
	 * Name of the database table
	 *
	 * @var string
	 */
	private $tablename;
	
	/**
	 * Navigation name for this table, used in grid UI's.
	 *
	 * @var string
	 */
	public $navname;
	
	/**
	 * Creates a ZymurgyMemberDataTable from a table ID.  To create one from a navigation name use the FactoryByNavName static method.
	 *
	 * @param int $tableid
	 */
	public function __construct($tableid)
	{
		if (!is_numeric($tableid)) die("Invalid table ID: [$tableid]");
		//Authenticate and make sure we have the member's info
		if (!Zymurgy::memberauthenticate())
		{
			$rurl = urlencode($_SERVER['REQUEST_URI']);
			Zymurgy::JSRedirect(Zymurgy::$config['MemberLoginPage']."?rurl=$rurl");
		}
		$table = Zymurgy::$db->get("select * from zcm_customtable where id=$tableid");
		if ($table === FALSE) die("No such custom table ID: $tableid");
		$this->tableid = $table['id'];
		$this->navname = $table['navname'];
		$this->tablename = $table['tname'];
		if ($table['detailfor']>0)
		{
			$this->parent = true; //Ugly...  Used so that is_null checks to see if we're the root node work correctly,
				// but this means that we can't count on parent being an object reference.  Should be re-visited.
		}
		$ri = Zymurgy::$db->run("select * from zcm_customfield where tableid={$table['id']} order by disporder");
		while (($row = Zymurgy::$db->fetch_array($ri))!==FALSE)
		{
			$this->fields[$row['id']] = new ZymurgyMemberDataField($row); 
		}
		Zymurgy::$db->free_result($ri);
		$ri = Zymurgy::$db->run("select id from zcm_customtable where detailfor=$tableid order by disporder");
		$children = array();
		while (($row = Zymurgy::$db->fetch_array($ri))!==FALSE)
		{
			$children[] = $row['id'];
		}
		Zymurgy::$db->free_result($ri);
		foreach($children as $childid)
		{
			$newtable = new ZymurgyMemberDataTable($childid);
			$newtable->parent = $this;
			$this->children[$childid] = $newtable;
		}
	}
	
	/**
	 * Create a ZymurgyMemberDataTable from a custom table navigation name.
	 *
	 * @param string $navname
	 * @return ZymurgyMemberDataTable
	 */
	public static function FactoryByNavName($navname)
	{
		//Look up table info by $navname
		$tableid = Zymurgy::$db->get("select id from zcm_customtable where navname='".
			Zymurgy::$db->escape_string($navname)."'");
		if ($tableid === FALSE) die("No such custom table: $navname");
		return new ZymurgyMemberDataTable($tableid);
	}
	
	/**
	 * Create a form UI, or handle the submitted data.
	 *
	 * @param int $rowid
	 */
	public function renderForm($rowid = 0)
	{
		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			$this->renderFormSubmit();
		}
		else 
		{
			$this->renderFormUI($rowid);
		}
	}
	
	/**
	 * Handle data submitted from a form.
	 * TODO:  Ensure that duplicate entries can't be inserted when a user loads two initial instances of the form, and then submits one after the other.
	 */
	private function renderFormSubmit()
	{
		//print_r($_POST); exit;
		$values = array();
		$updateid = 0 + $_POST['rowid'];
		foreach($this->fields as $fieldid=>$field)
		{
			$values[$field->columnname] = Zymurgy::$db->escape_string($_POST["field{$field->fieldid}"]);
		}
		if ($updateid == 0)
		{
			if (is_null($this->parent))
			{
				$values['member'] = Zymurgy::$member['id'];
			}
			$sql = "insert into {$this->tablename} (".
				implode(',',array_keys($values)).") values ('".
				implode("','",$values)."')";
		}
		else 
		{
			$ucol = array();
			foreach($values as $colname=>$value)
			{
				$ucol[] = "$colname='$value'";
			}
			$sql = "update {$this->tablename} set ".implode(', ',$ucol)." where (id=$updateid)";
			if (is_null($this->parent))
			{
				$sql .= " and (member=".Zymurgy::$member['id'].")";
			}
		}
		echo $sql;
		Zymurgy::$db->run($sql);
	}
	
	/**
	 * Create a form's UI
	 *
	 * @param unknown_type $rowid
	 */
	private function renderFormUI($rowid = 0)
	{
		//Load existing member data
		if ($rowid == 0)
		{
			$memberdata = Zymurgy::$db->get("select * from {$this->tablename} where member=".
				Zymurgy::$member['id']);
		}
		else 
		{
			if (!is_numeric($rowid)) die("Invalid row id for {$this->tablename}: $rowid");
			$memberdata = Zymurgy::$db->get("select * from {$this->tablename} where id=$rowid");
		}
		if (count($this->children) > 0)
		{
			echo "<div id=\"ZymurgyTabset{$this->tablename}\" class=\"yui-navset\">\r\n";
			echo "<ul class=\"yui-nav\">\r\n";
			echo "<li class=\"selected\"><a href=\"#tab{$this->tableid}\"><em>{$this->navname}</em></a></li>\r\n";
			foreach($this->children as $child)
			{
				echo "<li><a href=\"#tab{$child->tableid}\"><em>{$child->navname}</em></a></li>\r\n";
			}
			echo "</ul>\r\n";
			echo "<div class=\"yui-content\">\r\n";
			echo "<div>\r\n";
		}
		echo "<form id=\"zymurgyForm{$this->tablename}\" action=\"{$_SERVER['SCRIPT_URI']}\" method=\"post\">\r\n";
		if (is_array($memberdata))
		{
			$rowid = $memberdata['id'];
		}
		else 
		{
			$rowid = 0;
		}
		echo "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\" />\r\n";
		echo "<table>\r\n";
		foreach($this->fields as $fieldid=>$field)
		{
			if (is_array($memberdata))
				$value = $memberdata[$field->columnname];
			else 
				$value = '';
			$field->renderTableRow($value);
		}
		echo "<tr><td colspan=\"2\"><input type=\"submit\" value=\"Save\" /></td></tr>\r\n";
		echo "</table></form>\r\n";
		if (count($this->children) > 0)
		{
			echo "</div>"; //First tab content div
			foreach($this->children as $child)
			{
				echo "<div>";
				$child->renderGrid($rowid);
				echo "</div>";
			}
			echo "</div>"; //yui-content
			echo "</div>"; //yui-navset
			echo "<script type=\"text/javascript\">
    var tabView{$this->tablename} = new YAHOO.widget.TabView('ZymurgyTabset{$this->tablename}');
</script>";
		}
	}
	
	public function renderDialogs()
	{
		if (!is_null($this->parent))
		{
		//Render a dialog box to edit the contents of this grid
		?>
<div id="ZymurgyDialog<?= $this->tablename ?>">
	<div class="hd"><?= $this->navname ?></div>
	<div class="bd">
		<form name="ZymurgyDialogForm<?= $this->tablename ?>" method="post" action="/zymurgy/memberdata.php">
		<input type="hidden" name="tableid" value="<?= $this->tableid ?>" />
		<input type="hidden" name="rowid" id="rowid<?= $this->tableid ?>" value="0" />
		<table>
<?
foreach($this->fields as $fieldid=>$field)
{
	$field->renderTableRow('');
}
?>
		</table>
		</form>
	</div>
</div>
<script type="text/javascript">
var ZymurgyDialog<?= $this->tablename ?>;
YAHOO.util.Event.addListener(window, "load", function() {
	ZymurgyDialog<?= $this->tablename ?> = new YAHOO.widget.Dialog("ZymurgyDialog<?= $this->tablename ?>",{
		visible:false,
		buttons: zymurgyDialogButtons
	});
	ZymurgyDialog<?= $this->tablename ?>.setField = function(id,value) {
		var el = document.getElementById('field'+id);
		el.value = value;
	};
	ZymurgyDialog<?= $this->tablename ?>.render();
});
</script>	
		<?
		}
		foreach ($this->children as $child)
		{
			$child->renderDialogs();
		}
	}
	
	public function renderGrid($rowid=0)
	{
		$dtkeys = array();
		$dskeys = array();
		$editors = array();
		$dlgassigns = array();
		$dtkeys['id'] = "{key:\"id\", hidden:true}";
		$dtkeys['id'] = "{key:\"id\"}";
		foreach($this->fields as $fieldid=>$field)
		{
			$columndef = "{key:\"{$field->columnname}\", label:\"{$field->gridheader}\"}";
			$dskeys[$field->columnname] = $columndef;
			if (!empty($field->gridheader))
				$dtkeys[$field->columnname] = $columndef;
			$editors[$fieldid] = "<label for=\"field$fieldid\">{$field->caption}</label>";
			$dlgassigns[$fieldid] = "ZymurgyDialog{$this->tablename}.setField($fieldid,rowdata['{$field->columnname}']);";
		}
		$dtkeys['edit'] = "{key:\"edit\", label:\"Edit\", formatter:this.formatEdit}";
		//Render the grid
		echo "<div id=\"ZymurgyDataTable{$this->tablename}\"></div><script type=\"text/javascript\">\r\n";
		?>
YAHOO.util.Event.addListener(window, "load", function() {
    InitializeGrid<?= $this->tablename ?> = new function() {
        this.formatEdit = function(elCell, oRecord, oColumn, sData) {
            elCell.innerHTML = "Edit";
        };
		this.onCellClickEvent = function(oArgs) {
			var target = oArgs.target;
			var column = InitializeGrid<?= $this->tablename ?>.myDataTable.getColumn(target);
			if (column.key == 'edit') {
				var dtrec = InitializeGrid<?= $this->tablename ?>.myDataTable.getRecord(target);
				var rowdata = dtrec.getData();
				var el = document.getElementById('rowid<?= $this->tableid ?>');
				el.value = rowdata['id'];
				<?
				echo implode("\r\n",$dlgassigns);
				?>
				ZymurgyDialog<?= $this->tablename ?>.show();
			}
		};

        this.myDataSource = new YAHOO.util.DataSource("/zymurgy/memberdata.php?");
        this.myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
        this.myDataSource.connXhrMode = "queueRequests";
        this.myDataSource.responseSchema = {
            resultsList: "ResultSet.Result",
            fields: ["id","<?= implode('","',array_keys($dskeys)) ?>"]
        };

        this.myDataTable = new YAHOO.widget.DataTable("ZymurgyDataTable<?= $this->tablename ?>", [<?= implode(",\r\n\t\t\t",$dtkeys) ?>],
                this.myDataSource, {initialRequest:"t=<?= $this->tableid ?>&r=<?= $rowid ?>&m=<?= $this->parent->tableid ?>"});
		this.myDataTable.subscribe('cellClickEvent',this.onCellClickEvent);
    };
});
<?
		echo "</script>\r\n";
	}
	
	public function renderJSON($rowid)
	{
		$ri = Zymurgy::$db->run("select * from {$this->tablename} where {$this->parent->tablename}=$rowid");
		$r = array();
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$rp = array("\"id\":\"{$row['id']}\"");
			foreach($this->fields as $field)
			{
				$rp[] = "\"{$field->columnname}\":\"{$row[$field->columnname]}\"";
			}
			$r[] = implode(',',$rp);
		}
		$datablock = implode("},\r\n{\r\n",$r);
?>
{
	"ResultSet":
	{
		"totalResultsAvailable":<?= count($r) ?>,
		"totalResultsReturned":<?= count($r) ?>,
		"firstResultPosition":1,
		"Result":[
				{
<?= $datablock ?>
	            }
	        ]
	}
}
<?
	}
	
	/**
	 * Gets a child table instance by its id.
	 *
	 * @param int $childid
	 * @return ZymurgyMemberDataTable
	 */
	public function getChild($childid)
	{
		return $this->children[$childid];
	}
}

class ZymurgyMemberDataField
{
	public $columnname;
	public $inputspec;
	public $caption;
	public $gridheader;
	public $fieldid;
	
	public function __construct($fieldrow)
	{
		$this->fieldid = $fieldrow['id'];
		$this->columnname = $fieldrow['cname'];
		$this->inputspec = $fieldrow['inputspec'];
		$this->caption = $fieldrow['caption'];
		$this->gridheader = $fieldrow['gridheader'];
	}
	
	public function renderTableRow($value)
	{
		$iw = new InputWidget();
		echo "<tr><td>{$this->caption}</td><td>";
		$iw->Render($this->inputspec,"field{$this->fieldid}",$value);
		echo "</td></tr>\r\n";
	}
	
	/*public function renderDialogRow($value)
	{
		$iw = new InputWidget();
		echo "<label for=\"field$this->fieldid\">{$this->caption}</label>";
		$iw->Render($this->inputspec,"field".$this->fieldid,$value);
		echo "\r\n";
	}*/
}

if (array_key_exists('t',$_GET))
{
	//Look up data for ajax request.
	$m = new ZymurgyMemberDataTable($_GET['m']);
	$c = $m->getChild($_GET['t']);
	$c->renderJSON($_GET['r']);
} 
else if ($_SERVER['REQUEST_METHOD']=='POST')
{
	echo "Saving posted data for table #{$_POST['tableid']} and row #{$_POST['rowid']}\r\n";
	$t = new ZymurgyMemberDataTable(0 + $_POST['tableid']);
	$t->renderForm(0 + $_POST['rowid']);
}
?>