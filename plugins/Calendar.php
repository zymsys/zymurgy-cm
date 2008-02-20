<?
class Calendar extends PluginBase
{
	function Upgrade()
	{
		if ($this->dbrelease == $this->GetRelease()) return;
		switch($this->dbrelease)
		{
			case(2):
				Zymurgy::$db->query("alter table calendar add location varchar(80)") or die("Coulnd't upgrade to Calendar version 2: ".Zymurgy::$db->error());
		}
		$this->CompleteUpgrade();
	}
	
	function GetTitle()
	{
		return 'Calendar Plugin';
	}

	function GetRelease()
	{
		return 2;
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
		
	function GetDefaultConfig()
	{
		return array(
			'Allow Multi-day Events'=>'no',
			'Events have Location'=>'no',
			'Date Format'=>'F  jS, Y',
			'Date Tag'=>'h2',
			'Title Tag'=>'h3',
			'Location Tag'=>'i',
			'Description Tag'=>'p',
			'Event Separator'=>''
		);
	}
	
	function GetConfigItemTypes()
	{
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
	}
	
	function Initialize()
	{
		Zymurgy::$db->query("CREATE TABLE `calendar` (
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
		//Begin by deleting items which are over.
		//Zymurgy::$db->query("delete from calendar where end<(unix_timestamp()+24*3600)");
		Zymurgy::$db->query("delete from calendar where end<(unix_timestamp()-24*3600)");
		$cal = array();
		$iid = 0 + $this->iid;
		$ri = Zymurgy::$db->query("select * from calendar where (plugin={$this->pid}) and (instance=$iid) order by start");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$out = array();
			$start = date($this->config['Date Format'],$row['start']);
			$end = date($this->config['Date Format'],$row['end']);
			$date = $start;
			if (($this->config['Allow Multi-day Events'] == 'yes') && ($end != $start))
				$date .= " to $end";
			$out[] = "<{$this->config['Date Tag']}>$date</{$this->config['Date Tag']}>";
			$out[] = "<{$this->config['Title Tag']}>{$row['title']}</{$this->config['Title Tag']}>";
			if ($this->config['Events have Location'] == 'yes')
				$out[] = "<{$this->config['Location Tag']}>{$row['location']}</{$this->config['Location Tag']}>";
			$out[] = "<{$this->config['Description Tag']}>{$row['description']}</{$this->config['Description Tag']}>";
			$cal[] = join("\r\n",$out);
		}
		return "<div class=\"Calendar\">".join($this->config['Event Separator'],$cal)."</div>";
	}
	
	function AdminMenuText()
	{
		return 'Calendar';
	}
	
	function RenderAdmin()
	{
		$ds = new DataSet('calendar','id');
		$ds->AddColumns('id','plugin','instance','start','end','title','location','description');
		$ds->AddDataFilter('plugin',$this->pid);
		$ds->AddDataFilter('instance',$this->iid);
		$dg = new DataGrid($ds);
		$dg->AddColumn("Start Date",'start');
		if ($this->config['Allow Multi-day Events'] == 'yes')
			$dg->AddColumn('End Date','end');
		$dg->AddColumn('Title','title');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Event';
		$dg->AddUnixDateEditor('start','Start Date:');
		if ($this->config['Allow Multi-day Events'] == 'yes')
			$dg->AddUnixDateEditor('end','End Date:');
		else 
			$ds->OnBeforeUpdate = $ds->OnBeforeInsert = "CalendarSetEndDate";
		$dg->AddConstant('plugin',$this->pid);
		$dg->AddConstant('instance',$this->iid);
		$dg->AddInput('title','Title:',60,60);
		if ($this->config['Events have Location'] == 'yes')
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