<?
class Calendar extends PluginBase
{
	function Upgrade()
	{
		require_once(Zymurgy::$root."/zymurgy/install/upgradelib.php");

		VerifyColumnExists(
			"calendar",
			"location",
			"VARCHAR(80)",
			"");
		VerifyColumnExists(
			"calendar",
			"instance",
			"INTEGER",
			"");

		$this->CompleteUpgrade();
	}

	function GetTitle()
	{
		return 'Calendar Plugin';
	}

	function GetRelease()
	{
		return 3;
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
		/*
		//Data types are in the format:
		//Implemented:
		//Not Implemented:
//		"input.$size.$maxlength"
//		"textarea.$width.$height"
//		"html.$widthpx.$heightpx"
//		"radio.".serialize($optionarray)
//		"drop.".serialize($optionarray)
//		"attachment"
//		"money"
//		"unixdate"
//		"lookup.$table"
		return array(
			'Allow Multi-day Events'=>'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			'Events have Location'=>'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			'Date Format'=>'input.30.30',
			'Date Tag'=>'input.50.50',
			'Title Tag'=>'input.50.50',
			'Location Tag'=>'input.50.50',
			'Description Tag'=>'input.50.50',
			'Event Separator'=>'textarea.60.5'
		);
		*/
		return;
	}

	function Initialize()
	{
		Zymurgy::$db->query("CREATE TABLE IF NOT EXISTS `calendar` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `start` int(10) unsigned NOT NULL default '0',
  `end` int(10) unsigned NOT NULL default '0',
  `title` varchar(60) NOT NULL default '',
  `location` varchar(60) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `start` (`start`,`end`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
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