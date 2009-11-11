<?
/**
 *
 * @package Zymurgy_Plugins
 */
class Calendar extends PluginBase
{
	function Upgrade()
	{
		$this->VerifyTableDefinitions();
	}

	function VerifyTableDefinitions()
	{
		require_once(Zymurgy::$root."/zymurgy/installer/upgradelib.php");

		$tableDefinitions = array(
			array(
				"name" => "calendar",
				"columns" => array(
					DefineTableField("id", "INT(10)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("start", "INT(10)", "UNSIGNED NOT NULL DEFAULT '0'"),
					DefineTableField("end", "INT(10)", "UNSIGNED NOT NULL DEFAULT '0'"),
					DefineTableField("title", "VARCHAR(60)", "NOT NULL DEFAULT ''"),
					DefineTableField("location", "VARCHAR(60)", "NOT NULL DEFAULT ''"),
					DefineTableField("description", "TEXT", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "start, end", "unique" => "false", "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
	}

	function GetTitle()
	{
		return 'Calendar Plugin';
	}

	function GetUninstallSQL()
	{
		return 'drop table calendar';
	}

	function RemoveInstance()
	{
		$sql = "delete from calendar where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove calendar ($sql): ".Zymurgy::$db->error());
		parent::RemoveInstance();
	}

	function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => 'Allow Multi-day Events',
			"default" => 'no',
			"inputspec" => 'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Events have Location',
			"default" => 'no',
			"inputspec" => 'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Date Format',
			"default" => 'F  jS, Y',
			"inputspec" => 'input.30.30',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Date Tag',
			"default" => 'h2',
			"inputspec" => 'input.50.50',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Title Tag',
			"default" => 'h3',
			"inputspec" => 'input.50.50',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Location Tag',
			"default" => 'em',
			"inputspec" => 'input.50.50',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Description Tag',
			"default" => 'p',
			"inputspec" => 'input.50.50',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Event Separator',
			"default" => '',
			"inputspec" => 'textarea.60.5',
			"authlevel" => 0);

		return $configItems;
	}

	function GetDefaultConfig()
	{
		$r = array();

		$configItems = $this->GetConfigItems();

		foreach($configItems as $configItem)
		{
			$this->BuildConfig(
				$r,
				$configItem["name"],
				$configItem["default"],
				$configItem["inputspec"],
				$configItem["authlevel"]);
		}

		$this->BuildExtensionConfig($r);

		return $r;
	}

	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function GetConfigItemTypes()
	{
		return;
	}

	function Initialize()
	{
		$this->VerifyTableDefinitions();
	}

	function Render()
	{
		if($this->GetConfigValue('Date Format') == null)
		{
			echo("Calendar plugin instance must be configured before it can be displayed.");
			die("Calendar plugin instance must be configured before it can be displayed.");
		}

		//Begin by deleting items which are over.
		//Zymurgy::$db->query("delete from calendar where end<(unix_timestamp()+24*3600)");
		Zymurgy::$db->query("delete from calendar where end<(unix_timestamp()-24*3600)");
		$cal = array();
		$iid = 0 + $this->iid;
		$ri = Zymurgy::$db->query("select * from calendar where (instance=$iid) order by start");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$out = array();
			$start = date($this->GetConfigValue('Date Format'),$row['start']);
			$end = date($this->GetConfigValue('Date Format'),$row['end']);
			$date = $start;
			if (($this->GetConfigValue('Allow Multi-day Events') == 'yes') && ($end != $start))
				$date .= " to $end";
			$out[] = "<{$this->GetConfigValue('Date Tag')}>$date</{$this->GetConfigValue('Date Tag')}>";
			$out[] = "<{$this->GetConfigValue('Title Tag')}>{$row['title']}</{$this->GetConfigValue('Title Tag')}>";
			if ($this->GetConfigValue('Events have Location') == 'yes')
				$out[] = "<{$this->GetConfigValue('Location Tag')}>{$row['location']}</{$this->GetConfigValue('Location Tag')}>";
			$out[] = "<{$this->GetConfigValue('Description Tag')}>{$row['description']}</{$this->GetConfigValue('Description Tag')}>";
			$cal[] = join("\r\n",$out);
		}
		return "<div class=\"Calendar\">".join($this->GetConfigValue('Event Separator'),$cal)."</div>";
	}

	function AdminMenuText()
	{
		return 'Calendar';
	}

	function RenderAdmin()
	{
		$ds = new DataSet('calendar','id');
		$ds->AddColumns('id','instance','start','end','title','location','description');
		$ds->AddDataFilter('instance',$this->iid);
		$dg = new DataGrid($ds);
		$dg->AddColumn("Start Date",'start');
		if ($this->GetConfigValue('Allow Multi-day Events') == 'yes')
			$dg->AddColumn('End Date','end');
		$dg->AddColumn('Title','title');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Event';
		$dg->AddUnixDateEditor('start','Start Date:');
		if ($this->GetConfigValue('Allow Multi-day Events') == 'yes')
			$dg->AddUnixDateEditor('end','End Date:');
		else
			$ds->OnBeforeUpdate = $ds->OnBeforeInsert = "CalendarSetEndDate";
		$dg->AddConstant('instance',$this->iid);
		$dg->AddInput('title','Title:',60,60);
		if ($this->GetConfigValue('Events have Location') == 'yes')
		{
			$dg->AddColumn('','location','');
			$dg->AddInput('location','Location:',60,60);
		}
		$dg->AddHtmlEditor('description','Description:');
		global $CurrentCalendarGrid;
		$CurrentCalendarGrid = &$dg;
		$dg->Render();
	}
}

function CalendarSetEndDate($values)
{
	//Should only be called if we need to populate end to match start.
	$values['calendar.end'] = $values['calendar.start'];
	echo "Set end to [{$values['calendar.end']}]<br>";
	return $values;
}

function CalendarFactory()
{
	return new Calendar();
}
?>