<?
// Custom Table definitions may only be set by the webmaster
$adminlevel = 2;

ob_start();

require_once('cmo.php');
include 'datagrid.php';
include 'customlib.php';

$detailfor = array_key_exists('d',$_GET) ? (0 + $_GET['d']) : 0;
$crumbs = array("customtable.php"=>"Custom Tables");
if ($detailfor > 0)
{
	tablecrumbs($detailfor);
}
if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$ek = array_key_exists('editkey',$_GET) ? 0 + $_GET['editkey'] : 0;
	$tbl = Zymurgy::$db->get("select * from zcm_customtable where id=$ek");
	$tblname = empty($tbl['navname']) ? $tbl['tname'] : $tbl['navname'];
	$crumbs[''] = "Edit $tblname";
}

include 'header.php';

function DropTable($table,$tid)
{
	Zymurgy::$db->run("drop table $table");
	Zymurgy::$db->run("delete from zcm_customtable where id=$tid");
	$parents = array();
	$ri = Zymurgy::$db->run("select id,tname from zcm_customtable where detailfor=$tid");
	echo "[$ri]";
	while (($row=Zymurgy::$db->fetch_array($ri))!==false)
	{
		$parents[$row['id']] = $row['tname'];
	}
	foreach($parents as $ptid=>$pname)
	{
		DropTable($pname,$ptid);
	}
}

//The values array contains tablename.columnname keys with values from the row to be deleted.
function OnDelete($values)
{
	DropTable($values['zcm_customtable.tname'],$values['zcm_customtable.id']);
	return true; //Return false to override delete.
}

//The values array contains tablename.columnname keys with the proposed new values for the updated row.
function OnBeforeUpdate($values)
{
	$okname = okname($values['zcm_customtable.tname']);
	if ($okname!==true)
	{
		return $okname;
	}
	//Get old values
	$sql = "select * from zcm_customtable where id={$values['zcm_customtable.id']}";
	$ri = mysql_query($sql) or die("Unable to get previous table info ($sql): ".mysql_error());
	$row = mysql_fetch_array($ri) or die("No such table!");
	if ($row['tname']!=$values['zcm_customtable.tname'])
	{
		//Table name has changed
		$oldname = $row['tname'];
		$newname = $values['zcm_customtable.tname'];
		$needchange = array();
		$changed = array();
		$errmsg = '';
		//Find relationships and rename those
		$sql = "select * from zcm_customtable where detailfor={$row['id']}";
		$ri = mysql_query($sql) or die("Unable to get relationships ($sql): ".mysql_error());
		while (($drow = mysql_fetch_array($ri))!==false)
		{
			$needchange[] = $drow['tname'];
		}
		mysql_free_result($ri);
		foreach ($needchange as $tname)
		{
			$sql = "alter table $tname change $oldname $newname bigint";
			$ri = mysql_query($sql);
			if ($ri !== false)
			{
				$changed[] = $tname;
			}
			else
			{
				$errmsg = "Couldn't rename the $oldname column to $newname in the table $tname ($sql): ".mysql_error();
				break; //Don't bother changing any more, we're going to try to undo the damage and get out.
			}
		}
		if (empty($errmsg))
		{
			//All required relationships have been successfully updated.
			$sql = "rename table `{$row['tname']}` to `{$values['zcm_customtable.tname']}`";
			$ri = mysql_query($sql);
			if (!$ri)
			{
				$e = mysql_errno();
				switch($e)
				{
					default:
						$errmsg = "SQL error $e trying to rename table {$row['tname']} to {$values['zcm_customtable.tname']} ($sql): ".mysql_error();
				}
			}
		}
		if (!empty($errmsg))
		{
			//Something went wrong.  Back out.
			foreach ($changed as $tname)
			{
				$sql = "update $tname change $newname $oldname bigint";
				$ri = mysql_query($sql);
				if ($ri === false)
				{
					//Uh oh, can't even back out!  Best we can do is alert the developer of the snafu.
					$errmsg .= "<br />Additionally we couldn't back out one of the table changes already made.  We tried ($sql) but received the error: ".mysql_error();
				}
			}
			echo "<p>$errmsg</p><p>Please use your back button to correct the problem and try again.</p>";
			return false;
		}
	}
	if ($row['hasdisporder']!=$values['zcm_customtable.hasdisporder'])
	{
		//display order flag has changed
		if ($values['zcm_customtable.hasdisporder'] == 0)
		{
			//Remove display order column
			$sql = "alter table `{$values['zcm_customtable.tname']}` drop disporder";
			$ri = mysql_query($sql) or die ("Unable to remove display order ($sql): ".mysql_error());
		}
		else
		{
			//Add display order column
			$sql = "alter table `{$values['zcm_customtable.tname']}` add disporder bigint";
			mysql_query($sql) or die("Unable to add display order ($sql): ".mysql_error());
			$sql = "alter table `{$values['zcm_customtable.tname']}` add index(disporder)";
			mysql_query($sql) or die("Unable to add display order index ($sql): ".mysql_error());
			$sql = "update `{$values['zcm_customtable.tname']}` set disporder=id";
			mysql_query($sql) or die("Unable to set default display order ($sql): ".mysql_error());
		}
	}
	if ((0+$row['ismember'])!=(0+$values['zcm_customtable.ismember']))
	{
		//ismember flag has changed
		if ($values['zcm_customtable.ismember'] == 0)
		{
			//Remove member column
			$sql = "alter table `{$values['zcm_customtable.tname']}` drop member";
			$ri = mysql_query($sql) or die ("Unable to remove member ($sql): ".mysql_error());
		}
		else
		{
			//Add member column
			$sql = "alter table `{$values['zcm_customtable.tname']}` add member bigint";
			mysql_query($sql) or die("Unable to add member ($sql): ".mysql_error());
			$sql = "alter table `{$values['zcm_customtable.tname']}` add index(member)";
			mysql_query($sql) or die("Unable to add member index ($sql): ".mysql_error());
		}
	}
	if ($row['selfref']!=$values['zcm_customtable.selfref'])
	{
		//Self reference has changed
		if (empty($values['zcm_customtable.selfref']))
		{
			//Remove self ref column
			Zymurgy::$db->run("alter table `{$values['zcm_customtable.tname']}` drop selfref");
		}
		else
		{
			//Add self ref column
			Zymurgy::$db->run("alter table `{$values['zcm_customtable.tname']}` add selfref bigint default 0");
			Zymurgy::$db->run("alter table `{$values['zcm_customtable.tname']}` add index(selfref)");
		}
	}
	return $values; // Change values you want to alter before the update occurs.
}

//The values array contains tablename.columnname keys with the proposed new values for the new row.
function OnBeforeInsert($values)
{
	global $detailfor;

	$okname = okname($values['zcm_customtable.tname']);
	if ($okname!==true)
	{
		return $okname;
	}
	//Try to create table
	$sql = "create table `{$values['zcm_customtable.tname']}` (id bigint not null auto_increment primary key";
	if ($detailfor>0)
	{
		$tbl = gettable($detailfor);
		$sql .= ", `{$tbl['tname']}` bigint, key `{$tbl['tname']}` (`{$tbl['tname']}`)";
	}
	if ($values['zcm_customtable.hasdisporder'] == 1)
	{
		$sql .= ", disporder bigint, key disporder (disporder)";
	}
	if ($values['zcm_customtable.ismember'] == 1)
	{
		$sql .= ", member bigint, key member (member)";
	}
	$sql .= ")";
	$ri = mysql_query($sql) or die("Unable to create table ($sql): ".mysql_error());
	if (!$ri)
	{
		$e = mysql_errno();
		switch ($e)
		{
			case 1050:
				return "The table {$values['zcm_customtable.tname']} already exists.  Please select a different name.";
			default:
				return "<p>SQL error $e trying to create table: ".mysql_error()."</p>";
		}
		return false;
	}
	if (!empty($values['zcm_customtable.selfref']))
	{
		Zymurgy::$db->run("alter table `{$values['zcm_customtable.tname']}` add selfref bigint default 0");
		Zymurgy::$db->run("alter table `{$values['zcm_customtable.tname']}` add index(selfref)");
	}
	if ($detailfor == 0)
	{
		///TODO: Add to navigation
	}
	return $values;
}

$ds = new DataSet('zcm_customtable','id');
$ds->AddColumns('id','disporder','tname','detailfor','hasdisporder','ismember','navname','selfref');
$ds->AddDataFilter('detailfor',$detailfor);
$ds->OnBeforeUpdate = 'OnBeforeUpdate';
$ds->OnBeforeInsert = 'OnBeforeInsert';
$ds->OnDelete = 'OnDelete';

$dg = new DataGrid($ds);
$dg->AddConstant('detailfor',$detailfor);
$dg->AddColumn('Table','tname');
$dg->AddColumn('Display Order?','hasdisporder');
$dg->AddColumn('Member Data?','ismember');
$dg->AddUpDownColumn('disporder');

$dg->AddColumn(
	"Contents",
	"id",
	"<a href=\"customedit.php?t={0}\">Contents</a>");

$dg->AddColumn('Fields','id','<a href="customfield.php?t={0}">Fields</a>');
$dg->AddColumn('Detail Tables','id','<a href="customtable.php?d={0}">Detail Tables</a>');
$dg->AddInput('tname','Table Name:',30,30);
$dg->AddInput('navname','Link Name:',30,30);
$dg->AddInput('selfref','Self Reference:',30,30);
$dg->AddDropListEditor('hasdisporder','Display Order?',array(0=>'No',1=>'Yes'));
$dg->AddDropListEditor('ismember','Member Data?',array(0=>'No',1=>'Yes'));
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add a New Table';
$dg->Render();

include('footer.php');
?>