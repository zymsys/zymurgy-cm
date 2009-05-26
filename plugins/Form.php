<?
class Form extends PluginBase
{
	var $ValidationErrors;
	var $InputRows;
	var $XmlValues; //Poke form data into this to pre-populate the form
	var $SaveID; //Poke id number to save to existing slot instead of insert
	var $InputDataLoaded = false;
	var $member = false;

	function Form()
	{
		$this->ValidationErrors = array();
		$this->InputRows = array();
		if (Zymurgy::memberauthenticate())
		{
			$this->member = Zymurgy::$member;
		}
		parent::PluginBase();
	}

	function GetTitle()
	{
		return 'Form Plugin';
	}

	function GetUninstallSQL()
	{
		return 'drop table zcm_form_capture; drop table zcm_form_input; drop table zcm_form_inputtype';
	}

	function RemoveInstance()
	{
		$sql = "delete from zcm_form_input where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove form fields ($sql): ".Zymurgy::$db->error());
		$sql = "delete from zcm_form_capture where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove form captures ($sql): ".Zymurgy::$db->error());
		$sql = "delete from zcm_form_export where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove form exports ($sql): ".Zymurgy::$db->error());
		$sql = "delete from zcm_form_header where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove form headers ($sql): ".Zymurgy::$db->error());
		parent::RemoveInstance();
	}

	function GetConfigItems()
	{
		$configItems = array();

		$configItems["Name"] = array(
			"name" => "Name",
			"default" => "My New Form",
			"inputspec" => "input.50.100",
			"authlevel" => 2);
		$configItems["Submit Button Text"] = array(
			"name" => "Submit Button Text",
			"default" => "Send",
			"inputspec" => "input.10.80",
			"authlevel" => 0);
		$configItems["Validation Error Intro"] = array(
			"name" => "Validation Error Intro",
			"default" => "There were some problems processing your information:",
			"inputspec" => "html.500.300",
			"authlevel" => 0);
		$configItems["Footer"] = array(
			"name" => "Footer",
			"default" => "",
			"inputspec" => "html.500.300",
			"authlevel" => 0);
		$configItems["Thanks"] = array(
			"name" => "Thanks",
			"default" => "Thanks for your feedback!  We will get back to you shortly.",
			"inputspec" => "html.500.300",
			"authlevel" => 0);

		return $configItems;
	}

	function GetDefaultConfig()
	{
		global $ZymurgyConfig;

		$dom = $ZymurgyConfig['sitehome'];
		if (substr($dom,0,4) == "www.")
			$dom = substr($dom,4);
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

		$this->BuildMenuItem(
			$r,
			"View form details",
			"pluginadmin.php?pid={pid}&iid={iid}&name={name}",
			0);
		$this->BuildSettingsMenuItem($r);
		//$this->BuildMenuItem(
		//	$r,
		//	"Edit settings",
		//	"zkpluginconfig.php?plugin={pid}&amp;instance={iid}",
		//	0);
		$this->BuildMenuItem(
			$r,
			"Edit available input types",
			"pluginsuperadmin.php?plugin={pid}&amp;instance={iid}",
			2);
		$this->BuildMenuItem(
			$r,
			"Edit available validators",
			"pluginadmin.php?ras=regex&pid={pid}&iid={iid}&name={name}",
			2);
		$this->BuildDeleteMenuItem($r);

		$this->BuildExtensionMenu($r);

		return $r;
	}

	function Initialize()
	{
		Zymurgy::$db->query("CREATE TABLE `zcm_form_capture` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `instance` int(11) NOT NULL default '0',
  `submittime` datetime NOT NULL default '0000-00-00 00:00:00',
  `ip` varchar(15) NOT NULL default '',
  `useragent` varchar(80) NOT NULL default '',
  `export` int(11),
  `formvalues` text NOT NULL,
  `member` bigint,
  PRIMARY KEY  (`id`),
  KEY export (export),
  KEY member (member),
  KEY `instance` (`instance`,`submittime`)
)");
		Zymurgy::$db->query("CREATE TABLE `zcm_form_input` (
  `id` int(11) NOT NULL auto_increment,
  `instance` int(11) NOT NULL default '0',
  `inputtype` int(11) NOT NULL default '0',
  `caption` text NOT NULL,
  `header` varchar(40) NOT NULL default '',
  `disporder` int(11) NOT NULL default '0',
  `defaultvalue` text NOT NULL,
  `isrequired` smallint(6) NOT NULL default '0',
  `validator` bigint,
  `validatormsg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `instance` (`instance`,`disporder`)
)");
		Zymurgy::$db->query("CREATE TABLE `zcm_form_inputtype` (
  `id` int(11) NOT NULL auto_increment,
  `takesxtra` smallint(6) NOT NULL default '0',
  `name` varchar(60) NOT NULL default '',
  `specifier` text NOT NULL,
  PRIMARY KEY  (`id`)
)");
		Zymurgy::$db->query("CREATE TABLE `zcm_form_export` (
  `id` int(11) NOT NULL auto_increment,
  `exptime` datetime default NULL,
  `expuser` int(11) default NULL,
  `instance` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `exptime` (`exptime`),
  KEY `instance` (`instance`)
)");
		Zymurgy::$db->query("CREATE TABLE `zcm_form_header` (
  `id` int(11) NOT NULL auto_increment,
  `instance` int(11) default NULL,
  `header` varchar(60) default NULL,
  PRIMARY KEY  (`id`),
  KEY `instance` (`instance`)
)");

		Zymurgy::$db->query("CREATE TABLE `zcm_form_regex` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `disporder` bigint(20) default NULL,
  `name` varchar(35) default NULL,
  `regex` text,
  UNIQUE KEY `id` (`id`),
  KEY `disporder` (`disporder`)
)");

		Zymurgy::$db->query("INSERT INTO `zcm_form_inputtype` VALUES
			(1,0,'Short Text (30 characters)','input.30.30'),
			(2,0,'Medium Text (60 characters)','input.30.60'),
			(3,0,'Long Text (100 characters)','input.50.100'),
			(4,0,'Small Text Box  (40 x 5)','textarea.40.5'),
			(5,0,'Medium Text Box (60 x 10)','textarea.60.10'),
			(6,0,'Large Text Box (80 x 25)','textarea.80.25'),
			(7,0,'Checkbox (default off)','checkbox'),
			(8,0,'Checkbox (default on)','checkbox.checked'),
			(9,0,'Radio (Yes/No)','radio.Yes,No'),
			(10,0,'Drop Down (Yes/No)','drop.Yes,No'),
			(11,0,'Date','unixdate'),
			(12,0,'File Attachment','attachment'),
			(13,0,'Verbiage','verbiage')");

			Zymurgy::$db->query($this->getDefaultRegexInsert());
	}

	private function getDefaultRegexInsert()
	{
		$defaultregex = array(
			'None'=>'',
			'Email'=>'^([a-zA-Z0-9_\-\.])+@(([0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5])|((([a-zA-Z0-9\-])+\.)+([a-zA-Z\-])+))$',
			'Postal/Zip Code'=>'^\d{5}-\d{4}|\d{5}|[A-Z,a-z]\d[A-Z,a-z] \d[A-Z,a-z]\d$',
			'Phone number (555-555-5555 format)'=>'^[2-9]\d{2}-\d{3}-\d{4}$');
		$sql = "INSERT INTO `zcm_form_regex` (`id`,`disporder`,`name`, `regex`) VALUES ";
		$id = 0;
		$v = array();
		foreach ($defaultregex as $name=>$regex)
		{
			$id++;
			$v[] = "($id,$id,'".
				Zymurgy::$db->escape_string($name)."','".
				Zymurgy::$db->escape_string($regex)."')";
		}
		$sql .= implode(", ",$v);
		return $sql;
	}

	function Upgrade()
	{
		$diemsg = "Unable to upgrade Form plugin: ";
		if ($this->dbrelease < 2)
		{
			//Upgrade to r2 - capture/export support
			//Need export table to track exports and when they happened and for who.
			//Need to be able to re-download any exports we want.
			//Need to be able to flush data from any export we want from the server side.
			//Capture table needs to link to which export that capture belongs to.
			//Null export info in capture means it's fresh.
			@Zymurgy::$db->query("alter table zcm_form_capture add export int");// or die($diemsg.Zymurgy::$db->error());
			@Zymurgy::$db->query("alter table zcm_form_capture add index(export)");// or die($diemsg.Zymurgy::$db->error());
			Zymurgy::$db->query("CREATE TABLE `zcm_form_export` (
				  `id` int(11) NOT NULL auto_increment,
				  `exptime` datetime default NULL,
				  `expuser` int(11) default NULL,
				  `instance` int(11) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `exptime` (`exptime`),
				  KEY `instance` (`instance`)
				)");
			Zymurgy::$db->query("alter table zcm_form_capture change `values` formvalues text NOT NULL");
		}
		if ($this->dbrelease < 3)
		{
			//Upgrade to r3 - capture member relationship, report on member ID in export and email.
			@Zymurgy::$db->query("alter table zcm_form_capture add member bigint");// or die($diemsg.Zymurgy::$db->error());
			@Zymurgy::$db->query("alter table zcm_form_capture add index(member)");// or die($diemsg.Zymurgy::$db->error());
		}
		if ($this->dbrelease < 4)
		{
			//Upgrade to r4 - Renamed tables.
			$map = array(
				'formcapture' => 'zcm_form_capture',
				'formexport' => 'zcm_form_export',
				'formheader' => 'zcm_form_header',
				'forminput' => 'zcm_form_input',
				'forminputtype' => 'zcm_form_inputtype');
			foreach ($map as $oldname=>$newname)
			{
				$sql = "rename table $oldname to $newname";
				@mysql_query($sql);// or die("Can't rename table ($sql): ".mysql_error());
			}
		}
		if ($this->dbrelease < 5)
		{
			Zymurgy::$db->query("CREATE TABLE `zcm_form_regex` (
			  `id` bigint(20) unsigned NOT NULL auto_increment,
			  `disporder` bigint(20) default NULL,
			  `name` varchar(35) default NULL,
			  `regex` text,
			  UNIQUE KEY `id` (`id`),
			  KEY `disporder` (`disporder`))");
			Zymurgy::$db->query($this->getDefaultRegexInsert());
			$ri = Zymurgy::$db->query("select id,validator from zcm_form_input");
			$reregex = array();
			while (($row = Zymurgy::$db->fetch_array($ri))!==FALSE)
			{
				$reregex[$row['id']] = $row['validator'];
			}
			Zymurgy::$db->free_result($ri);
			$unknowncount = 0;
			foreach($reregex as $id=>$validator)
			{
				switch($validator)
				{
					case '':
						break;
					case '^([a-zA-Z0-9_\-\.])+@(([0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5])|((([a-zA-Z0-9\-])+\.)+([a-zA-Z\-])+))$':
						Zymurgy::$db->query("update zcm_form_input set validator=2 where id=$id");
						break;
					case '^\d{5}-\d{4}|\d{5}|[A-Z,a-z]\d[A-Z,a-z] \d[A-Z,a-z]\d$':
						Zymurgy::$db->query("update zcm_form_input set validator=3 where id=$id");
						break;
					case '^[2-9]\d{2}-\d{3}-\d{4}$':
						Zymurgy::$db->query("update zcm_form_input set validator=4 where id=$id");
						break;
					default:
						if(is_numeric($validatior))
						{
							// Validator is already set by ID. The dbrelease for this plugin was
							// probably set incorrectly.
							//
							// Ignore.
						}
						else
						{
							$unknowncount++;
							$sql = "INSERT INTO `zcm_form_regex` (`name`, `regex`) VALUES ('Unknown Validator #$unknowncount','".
								Zymurgy::$db->escape_string($validator)."')";
							Zymurgy::$db->query($sql);
							$newregex = Zymurgy::$db->insert_id();
							Zymurgy::$db->query("update zcm_form_regex set disporder=$newregex where id=$newregex");
							Zymurgy::$db->query("update zcm_form_input set validator=$newregex where id=$id");
						}

						break;
				}
			}
			Zymurgy::$db->query("alter table zcm_form_input change validator validator bigint");
		}
		$this->CompleteUpgrade();
	}

	function GetRelease()
	{
		return 6; // Added support for PaymentForm, which uses the same db schema
		return 5; //Added capture/export capabilities to db.
		//return 3; //Added capture/export capabilities to db.
	}

	function LoadInputData()
	{
		$sql = "select *,zcm_form_input.id as fid from zcm_form_input,zcm_form_inputtype where (instance={$this->iid}) and (zcm_form_input.inputtype=zcm_form_inputtype.id) order by disporder";
		$ri = Zymurgy::$db->query($sql);
		if (!$ri)
		{
			die("$sql: ".Zymurgy::$db->error());
		}
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$this->InputRows[] = $row;
		}
		Zymurgy::$db->free_result($ri);
		$this->InputDataLoaded = true;
	}

	function RenderForm()
	{
		$postback = ((array_key_exists('formname',$_POST)) && ($_POST['formname']==$this->InstanceName));
		if ($this->XmlValues!='')
		{
			$values = $this->xmltoarray($this->XmlValues);
		}
		else
			$values = array();
		// Verbiage renders with colspan 2.  Checkboxes render with caption and input columns reversed.
		// Or should that reverse be a form config option?  damit.  Here I am.
		// Need user editable configuration values too.
		//echo $this->GetConfigValue('Field Name');
		$widget = new InputWidget();
		$js = array();
		foreach ($this->InputRows as $row)
		{
			$fieldname = "Field{$row['fid']}";
			if ($postback && (array_key_exists($fieldname,$_POST)))
			{
				$fieldvalue = $widget->PostValue($row['specifier'],$fieldname);
			}
			else
			{
				if (array_key_exists($row['header'],$values))
					$fieldvalue = $values[$row['header']];
				else
					$fieldvalue = $row['defaultvalue'];
			}
			$code = $widget->JSRender($row['specifier'],$fieldname,$fieldvalue);
			if (!empty($code))
			{
				$js[] = $code;
			}
		}
		$code = implode("\r\n",$js);
		echo "<form class=\"InputForm\" enctype=\"multipart/form-data\" method=\"POST\"";
		if (!empty($code))
		{
			$name = str_replace(' ','_',$this->InstanceName);
			echo " onsubmit=\"return Validate$name(this)\"";
		}
		echo ">\r\n";
		echo "<input type=\"hidden\" name=\"formname\" value=\"".htmlentities($this->InstanceName)."\">\r\n";
		echo "<table>\r\n";
		if (count($this->ValidationErrors)>0)
		{
			echo "<tr><td colspan=\"2\" class=\"ValidationCell\">".$this->GetConfigValue('Validation Error Intro')."<ul>";
			foreach($this->ValidationErrors as $err)
				echo "<li>$err</li>";
			echo "</ul></td></tr>\r\n";
		}
		if (!empty($code))
		{
			echo "<script type=\"text/javascript\">
<!--
function Validate$name(me) {
	var ok = true;
	$code
	return ok;
}
//-->
</script>
";
		}
		foreach ($this->InputRows as $row)
		{
			@list($inputtype, $inputparameters) = explode('.',$row['specifier'],2);
			if ($inputtype=='verbiage')
			{
				echo "<tr><td colspan=\"2\" class=\"VerbiageCell\" id=\"Field{$row['fid']}\">{$row['caption']}</td></tr>\r\n";
			}
			else
			{
				$isvalerr = array_key_exists($row['fid'],$this->ValidationErrors);
				$fieldname = "Field{$row['fid']}";
				if ($postback && (array_key_exists($fieldname,$_POST)))
				{
					$fieldvalue = $widget->PostValue($row['specifier'],$fieldname);
				}
				else
				{
					if (array_key_exists($row['header'],$values))
						$fieldvalue = $values[$row['header']];
					else
						$fieldvalue = $row['defaultvalue'];
				}
				if ($isvalerr)
					echo "<tr class=\"ValidationError\">";
				else
					echo "<tr>";
				if ($inputtype=='lookup')
				{
					list($table,$idcolumn,$valcolumn,$ordercolumn) = explode('.',$inputparameters);
					$widget->lookups[$table] = new DataGridLookup($table,$idcolumn,$valcolumn,$ordercolumn);
				}
				if ($inputtype=='checkbox')
				{
					echo "<td class=\"CheckboxCell\">";
					$widget->Render($row['specifier'],$fieldname,$fieldvalue);
					echo "</td><th class=\"CheckboxLabel\">{$row['caption']}</th></tr>\r\n";
				}
				else if($inputtype=='hidden')
				{
					$widget->Render($row['specifier'],$fieldname,$fieldvalue);
				}
				else
				{
					echo "<th class=\"NormalLabel";
					if ($row['isrequired']==1)
					{
						echo " Required";
					}
					else
					{
						echo " Optional";
					}
					echo "\">{$row['caption']}</th><td>";
					$widget->Render($row['specifier'],$fieldname,$fieldvalue);
					echo "</td></tr>\r\n";
				}
			}
		}
		echo "<tr><td colspan=\"2\" class=\"SubmitCell\">".$this->GetConfigValue('Footer')."<br /><input type=\"submit\" value=\"".
			$this->GetConfigValue('Submit Button Text')."\"";
		echo " /></td></tr>\r\n";
		echo "</table></form>";
		return "";
	}

	function GetValues()
	{
		//Build substitution array out of supplied headers/values
		$values = array();
		foreach($this->InputRows as $row)
		{
			$fieldname = "Field".$row['fid'];
			if (!array_key_exists($fieldname,$_POST))
				continue; //Don't report missing fields...  Think checkboxes.
                        if (empty($row['header']))
                                $key = $fieldname;
                        else
                                $key = $row['header'];
                        $values[$key] = $_POST['Field'.$row['fid']];
		}
		if (is_array($this->extra))
		{
			$values = array_merge($this->extra,$values);
		}
		return $values;
	}

	/**
	 * Parse an email address in the form "First Last <name@example.com>" into an array like
	 * array("First Last","name@example.com")
	 *
	 * @param string $addr Email address to parse
	 * @return array Address components
	 */
	function AddressElements($addr)
	{
		$ap = explode('<',$addr);
		if (count($ap)==1)
			return array('',$addr);
		$name = trim($ap[0]);
		$ap = explode('>',$ap[1]);
		$email = $ap[0];
		return array($name,$email);
	}

	function RenderThanks()
	{
		$this->CallExtensionMethod("SendEmail");
		$this->CallExtensionMethod("CaptureFormData");

		echo $this->GetConfigValue('Thanks');
	}

	function IsValid()
	{
		$validators = array();
		$ri = Zymurgy::$db->query("select id,regex from zcm_form_regex");
		while (($row = Zymurgy::$db->fetch_array($ri))!==FALSE)
		{
			$validators[$row['id']] = $row['regex'];
		}
		foreach($this->InputRows as $row)
		{
			$fieldname = "Field".$row['fid'];
			//If not required and doesn't exist, just continue.  Think checkboxes.
			if (($row['isrequired']==0) && (!array_key_exists($fieldname,$_POST)))
				continue;
			$input = $_POST[$fieldname] = trim($_POST[$fieldname]);
			if (empty($row['validatormsg']))
				$vmsg = "The field \"{$row['caption']}\" failed to validate.";
			else
				$vmsg = $row['validatormsg'];
            if (($row['isrequired']==1) && ($input == ''))
			{
				$this->ValidationErrors[$row['fid']] = $vmsg;
				continue;
			}
			if (array_key_exists($row['validator'],$validators) && ($validators[$row['validator']]!='') && ($input!='') && (!preg_match('/'.$validators[$row['validator']].'/',$input)))
			{
				$this->ValidationErrors[$row['fid']] = $vmsg;
			}
		}
		return (count($this->ValidationErrors)==0);
	}

	function Render()
	{
		if (!$this->InputDataLoaded)
			$this->LoadInputData();
		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			if ($_POST['formname']!=$this->InstanceName)
			{
				//Another form is posting, just render the form as usual.
				$this->RenderForm();
			}
			else
			{
				if ($this->IsValid())
				{
					//This does the send/store and the thanks.
					$this->RenderThanks();
				}
				else
				{
					$this->RenderForm();
				}
			}
		}
		else
		{
			$this->RenderForm();
		}
	}

	function AdminMenuText()
	{
		return 'Forms';
	}

	function RenderSuperAdmin()
	{
		$ds = new DataSet('zcm_form_inputtype','id');
		$ds->AddColumns('id','takesxtra','name','specifier');
		$dg = new DataGrid($ds);
		$dg->AddColumn('Name','name');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->AddInput('name','Name:',60,40);
		$dg->AddEditor('specifier','Input Specifier:','inputspec');
		//$dg->AddRadioEditor('takesxtra','Takes Extra Data?',array(0=>'No',1=>'Yes'));
		$dg->insertlabel = 'Add new Input Type';
		$dg->Render();
	}

	function RenderAdmin()
	{
		if (array_key_exists('ras',$_GET))
		{
			switch ($_GET['ras'])
			{
				case 'datamgmt':
					$this->CallExtensionMethod("RenderAdminDataManagement");
					break;

				case 'regex':
					$this->RenderAdminRegex();
					break;

				case 'fields':
				default:
					$this->RenderAdminFields();
					break;
			}
		}
		else
		{
			echo "<h3>Form administration for: ".$this->InstanceName."</h3>\r\n";

			$this->RenderAdminFields();
		}
	}

	function xmltoarray($xml)
	{
		$xvals = array();
		$xindex = array();
		$p = xml_parser_create();
		$xi = xml_parse_into_struct($p,$xml,$xvals,$xindex);
		xml_parser_free($p);
		$xrow = array();
		if(isset($xindex["VALUE"]))
		{
			foreach($xindex['VALUE'] as $idx)
			{
				$key = $xvals[$idx]['attributes']['HEADER'];
				if (array_key_exists('value',$xvals[$idx]))
					$val = $xvals[$idx]['value'];
				else
					$val = '';
				$xrow[$key] = $val;
			}
		}
		return $xrow;
	}

	public function RenderCalendarControl($name,$value)
	{
		$iw = new InputWidget();
		$iw->Render('datetime',$name,$value);
	}

	public function GetCalendarControlValue($name)
	{
		$iw = new InputWidget();
		return $iw->PostValue('unixdatetime',$name);
	}

	function RenderAdminFields()
	{
		$ds = new DataSet('zcm_form_input','id');
		$ds->AddColumns('id','instance','inputtype','caption','header','disporder','defaultvalue','isrequired','validator','validatormsg');
		$ds->AddDataFilter('instance',$this->iid);
		$dg = new DataGrid($ds);
		$dg->AddColumn("Caption",'caption');
		$dg->AddColumn('Input Type','inputtype');
		$dg->AddColumn('Required','isrequired');
		$dg->AddInput('caption','Form Caption:',4096,40);
		$dg->AddLookup('inputtype','Input Type:','zcm_form_inputtype','id','name');
		$dg->AddInput('header','Export Table Header:',40,20);
		$dg->AddInput('defaultvalue','Default Value:',1024,40);
		$dg->AddRadioEditor('isrequired','Required?',array(0=>'No',1=>'Yes'));
		$dg->AddLookup('validator','Validator:','zcm_form_regex','id','name','disporder');
		$dg->AddInput('validatormsg','Validator Message:',4096,60);
		$dg->AddUpDownColumn('disporder');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Field';
		$dg->AddConstant('instance',$this->iid);
		$dg->Render();
	}

	function RenderAdminRegex()
	{
		$ds = new DataSet('zcm_form_regex','id');
		$ds->AddColumns('id','disporder','name','regex');
		$dg = new DataGrid($ds);
		$dg->AddColumn('Name','name');
		$dg->AddUpDownColumn('disporder');
		$dg->AddInput('name','Name:',35,35);
		$dg->AddTextArea('regex','Validator Regex:');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel = 'Add New Regex Validator';
		$dg->Render();
		if (((array_key_exists('action',$_GET)) && ($_GET['action']=='insert')) || (array_key_exists('editkey',$_GET)))
		{
			echo "<dl><dt>Email validator:</dt>";
			echo '<dd>^([a-zA-Z0-9_\-\.])+@(([0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5]\.[0-2]?[0-5]?[0-5])|((([a-zA-Z0-9\-])+\.)+([a-zA-Z\-])+))$</dd>';
			echo "<dt>Postal/Zip Code:</dt>";
			echo '<dd>^\d{5}-\d{4}|\d{5}|[A-Z,a-z]\d[A-Z,a-z] \d[A-Z,a-z]\d$</dd>';
			echo "<dt>Phone number (555-555-5555 format)</dt>";
			echo '<dd>^[2-9]\d{2}-\d{3}-\d{4}$</dd></dl>';
			echo "Visit <a href=\"http://regexlib.com/\" target=\"_blank\">http://regexlib.com/</a> for more examples.";
		}
	}

	function GetExtensions()
	{
		$extensions = array();

		$extensions[] = new FormEmailToWebmaster();
		$extensions[] = new FormEmailConfirmation();
		$extensions[] = new FormAddToCustomTable();
		$extensions[] = new FormCaptureToDatabase();
		$extensions[] = new FormExportFromDatabase();

		return $extensions;
	}
}

function FormFactory()
{
	return new Form();
}

class FormEmailToWebmaster implements PluginExtension
{
	public function GetExtensionName()
	{
		return "E-mail to Webmaster";
	}

	public function GetDescription()
	{
		return "<p>When enabled, the information a user enters into the ".
			"form will be automatically e-mailed to you. This is great for ".
			"Contact Us forms, or other forms requiring immeiate action.</p>";
	}

	public function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "Enable E-mail to Address",
			"default" => "on",
			"inputspec" => "checkbox",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Email Form Results To Address",
			"default" => "you@".Zymurgy::$config['sitehome'],
			"inputspec" => "input.50.100",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Email Form Results From Address",
			"default" => "you@".Zymurgy::$config['sitehome'],
			"inputspec" => "input.50.100",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Email Form Results Subject",
			"default" => 'Feedback from '.Zymurgy::$config['defaulttitle'],
			"inputspec" => "input.50.100",
			"authlevel" => 0);

		return $configItems;
	}

	public function GetCommands()
	{
		return array();
	}

	public function ConfirmPluginCompatability($plugin)
	{
		if(!($plugin instanceof Form))
		{
			die("FormEmailToWebmaster: plugin sent not an instance of Form. Aborting.");
		}
	}

	public function IsEnabled($plugin)
	{
		return strlen(trim($plugin->GetConfigValue('Email Form Results To Address'))) > 0;
	}

	public function SendEmail(
		$plugin)
	{
		$this->ConfirmPluginCompatability($plugin);

		//Load PHPMailer class
		$mail = Zymurgy::GetPHPMailer();

		$values = $plugin->GetValues();
		//Build body lines
		$body = array();
		$subvalues = array();
		foreach($values as $header=>$value)
		{
			$body[] = $header.': '.$value;
			$subvalues['{'.$header.'}']=$value;
		}

		if ($plugin->member !== false)
		{
			$body[] = "Submitted by member #{$plugin->member['id']}: {$plugin->member['email']}";
		}

		if (array_key_exists('zcmtracking',$_COOKIE))
		{
			$tracking = $_COOKIE['zcmtracking'];

			$firstcontact = Zymurgy::$db->get("select * from zcm_tracking where id='".
				Zymurgy::$db->escape_string($tracking)."'");

			$body[] = '';

			$body[] = "User tracking started {$firstcontact['created']}:";

			if (!empty($firstcontact['tag']))
				$body[] = "\tIncoming Link Tag: {$firstcontact['tag']}";

			require_once Zymurgy::$root.'/zymurgy/include/referrer.php';

			$r = new Referrer($firstcontact['referrer']);

			if (!empty($r->host))
				$body[] = "\tFrom Host: {$r->host}";

			if (!empty($r->searchengine))
				$body[] = "\tSearch Engine: {$r->searchengine}";

			if (!empty($r->searchstring))
				$body[] = "\tSearch String: {$r->searchstring}";

			$ri = Zymurgy::$db->run("select viewtime,document from zcm_pageview left join zcm_meta on zcm_pageview.pageid=zcm_meta.id where trackingid='".
				Zymurgy::$db->escape_string($tracking)."' order by viewtime");

			$views = array();
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$views[] = $row;
			}

			Zymurgy::$db->free_result($ri);

			if (count($views)>0)
			{
				$body[] = '';
				$body[] = "Page Views:";

				if (count($views) > 10)
				{
					//Show only the first and last 5
					$views = array_merge(array_slice($views,0,5), array_slice($views,-5));
					$body[] = "(Showing only the first and last 5 page views)";
				}

				foreach($views as $view)
				{
					$body[] = "\t{$view['viewtime']}: {$view['document']}";
				}
			}
		}

		//Also substitute newline characters for blank to avoid attacks on our email headers
		$values["\r"] = '';
		$values["\n"] = '';
		$from = str_replace(array_keys($subvalues),$subvalues,$plugin->GetConfigValue('Email Form Results From Address'));
		$subject = str_replace(array_keys($subvalues),$subvalues,$plugin->GetConfigValue('Email Form Results Subject'));

		$to = $plugin->GetConfigValue('Email Form Results To Address');

		list($mail->FromName, $mail->From) = $plugin->AddressElements($from);
		$mail->Subject = $subject;
		$mail->AddAddress($to);

		foreach ($_FILES as $file=>$fileinfo)
		{
			$mail->AddAttachment($fileinfo['tmp_name'],$fileinfo['name']);
		}

		$mail->Body = implode("\n",$body);

		if (!$mail->Send())
			echo " There has been a problem sending email to [$to]. ";
	}
}

class FormEmailConfirmation implements PluginExtension
{
	public function GetExtensionName()
	{
		return "E-mail Confirmation";
	}

	public function GetDescription()
	{
		return "<p>When enabled, an e-mail will be automatically ".
			"sent to the user notifying them that you have received ".
			"the information on the form.</p>";
	}

	public function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "Confirmation Email From",
			"default" => "",
			"inputspec" => "input.50.100",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Confirmation Email Subject",
			"default" => "",
			"inputspec" => "input.50.100",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Confirmation Email Address Field",
			"default" => "",
			"inputspec" => "input.50.100",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Confirmation Email Contents",
			"default" => "",
			"inputspec" => "html.500.300",
			"authlevel" => 0);

		return $configItems;
	}

	public function GetCommands()
	{
		return array();
	}

	public function ConfirmPluginCompatability($plugin)
	{
		if(!($plugin instanceof Form))
		{
			die("FormEmailConfirmation: plugin sent not an instance of Form. Aborting.");
		}
	}

	public function IsEnabled($plugin)
	{
		return strlen(trim($plugin->GetConfigValue('Confirmation Email Address Field'))) > 0;
	}

	public function SendEmail(
		$plugin)
	{
		$this->ConfirmPluginCompatability($plugin);

		//Load PHPMailer class
		$mail = Zymurgy::GetPHPMailer();

		$values = $plugin->GetValues();
		//Build body lines
		$body = array();
		$subvalues = array();
		foreach($values as $header=>$value)
		{
			$body[] = $header.': '.$value;
			$subvalues['{'.$header.'}']=$value;
		}

		//Also substitute newline characters for blank to avoid attacks on our email headers
		$values["\r"] = '';
		$values["\n"] = '';

		$tofield = $plugin->GetConfigValue('Confirmation Email Address Field');

		//Send confirmation email as well.
		if (!array_key_exists($tofield,$values))
			die("This form doesn't have a field called '$tofield' for email confirmation.");
		$to = str_replace(array("\r","\n"),'',$values[$tofield]);
		list($mail->FromName, $mail->From) = $plugin->AddressElements($plugin->GetConfigValue('Confirmation Email From'));
		$body = $plugin->GetConfigValue('Confirmation Email Contents');
		$mail->Subject = str_replace(array_keys($subvalues),$subvalues,$plugin->GetConfigValue('Confirmation Email Subject'));
		$mail->AltBody = strip_tags($body);
		$mail->Body = str_replace(array_keys($subvalues),$subvalues,$body);
		$mail->ClearAddresses();
		$mail->ClearAttachments();
		$mail->AddAddress($to);
		if (!$mail->Send())
			echo " There has been a problem sending email to [$to]. ";
	}
}

class FormCaptureToDatabase implements PluginExtension
{
	public function GetExtensionName()
	{
		return "Capture to Database";
	}

	public function GetDescription()
	{
		return "<p>When a form is submitted, the data will be automatically ".
			"saved to the database for later retrieval.</p>".
			"<p>There are no items to configure for this feature.</p>";
	}

	public function IsEnabled($plugin)
	{
		return true;
	}

	public function GetConfigItems()
	{
		return array();
	}

	public function GetCommands()
	{
		return array();
	}

	public function ConfirmPluginCompatability($plugin)
	{
		if(!($plugin instanceof Form))
		{
			die("FormCaptureToDatabase: plugin sent not an instance of Form. Aborting.");
		}
	}

	function MakeXML($values)
	{
		$xml = array("<formvalues>");
		foreach ($values as $header=>$value)
		{
			$v = str_replace(array('<','&','>','"',"'"),array('&lt;','&amp;','&gt;','&quot;','&apos;'),$value);
			$xml[] = "\t<value header=\"$header\">$v</value>";
		}
		$xml[] = "</formvalues>";
		return implode("\r\n",$xml);
	}

	public function CaptureFormData(
		$plugin)
	{
		$this->ConfirmPluginCompatability($plugin);

		$xml = $this->MakeXML($plugin->GetValues());

		if (array_key_exists('HTTP_X_FORWARDED_FOR',$_SERVER))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];

		if ($plugin->SaveID)
		{
			$sql = "update zcm_form_capture set submittime=now(), ip='{$_SERVER['REMOTE_ADDR']}', useragent='".
				Zymurgy::$db->escape_string($_SERVER['HTTP_USER_AGENT'])."', formvalues='".
				Zymurgy::$db->escape_string($xml)."' where id=".$plugin->SaveID;
		}
		else
		{
			$sql = "insert into zcm_form_capture (instance,submittime,ip,useragent,formvalues,member) values ({$plugin->iid},now(),
				'{$_SERVER['REMOTE_ADDR']}','".Zymurgy::$db->escape_string($_SERVER['HTTP_USER_AGENT'])."','".
				Zymurgy::$db->escape_string($xml)."',".
				($plugin->member === false ? 'NULL' : $plugin->member['id']).")";
		}
		Zymurgy::$db->query($sql) or die("Unable to store form info ($sql): ".Zymurgy::$db->error());

		return Zymurgy::$db->insert_id();
	}
}

class FormExportFromDatabase implements PluginExtension
{
	public function GetExtensionName()
	{
		return "Export from Database";
	}

	public function GetDescription()
	{
		return "<p>Data submitted to the form can be retrieved from the ".
			"database in Microsoft Excel format.</p>".
			"<p>There are no items to configure for this feature.</p>";
	}

	public function IsEnabled($plugin)
	{
		return true;
	}

	public function GetConfigItems()
	{
		return array();
	}

	public function ConfirmPluginCompatability($plugin)
	{
		if(!($plugin instanceof Form))
		{
			die("FormExpertFromDatabase: plugin sent not an instance of Form. Aborting.");
		}
	}

	public function GetCommands()
	{
		$commands = array();

		$commands[] = array(
			"caption" => "Captured Data Management",
			"url" => "pluginadmin.php?ras=datamgmt&amp;pid={pid}&amp;iid={iid}&amp;name={name}",
			"authlevel" => 0);

		return $commands;
	}

	public function RenderAdminDataManagement($plugin)
	{
		$this->ConfirmPluginCompatability($plugin);

		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			if ($_POST['action']=='dl')
				$this->DownloadDataExport($plugin);
			else
				$this->DeleteData($plugin);
		}
		else
			$this->DisplayExportPage($plugin);
	}

	private function DisplayExportPage($plugin)
	{
		list($from,$to) = Zymurgy::$db->get("select min(submittime),max(submittime) from zcm_form_capture where instance=".$plugin->iid);
		if (empty($from))
		{
			echo "This form has no captured data to manage.  Enable capture from the form's configuration page to capture form data.";
			return;
		}
		?>
		<form onSubmit="return checkAction()" method="post" action="pluginadmin.php?ras=datamgmt&pid=<?php echo "{$plugin->pid}&iid={$plugin->iid}&name=".urlencode($plugin->InstanceName); ?>">
			<table>
				<tr>
					<td  align="right">Action:</td>
					<td><select id="action" name="action" onChange="changeAction(this)"><option value="dl">Download</option><option value="rm">Delete</option></select>
				</tr>
				<tr>
					<td  align="right">Records from:</td>
					<td><?php $plugin->RenderCalendarControl('from',$from); ?></td>
				</tr>
				<tr>
					<td  align="right">Records to:</td>
					<td><?php $plugin->RenderCalendarControl('to',$to); ?></td>
				</tr>
			</table>
			<input id="submit" type="submit" value="Download Selected Form Data">
		</form>
		<script>
		function changeAction(sel) {
			var btn = document.getElementById('submit');
			if (sel.value=='dl')
				btn.value = 'Download Selected Form Data';
			else
				btn.value = 'Delete Selected Form Data';
		}

		function checkAction() {
			var sel = document.getElementById('action');
			if (sel.value=='rm')
			{
				return (confirm("Are you sure you want to delete this data?  This action is not reversible.")==true);
			}
			else
				return true;
		}
		</script>
		<?php
	}

	private function DownloadDataExport($plugin)
	{
		$exported = array();
		$headers = array();
		$rows = array();

		$from = "";
		$to = "";

		$this->GetCaptureArrays(
			$plugin,
			$this->GetData($plugin, $from, $to),
			$exported,
			$headers,
			$rows);
		$this->RenderExcelSpreadsheet(
			$plugin,
			$headers,
			$rows,
			$plugin->InstanceName."-$from-$to");
	}

	private function GetCaptureArrays(
		$plugin,
		$exportRecordset,
		&$exported,
		&$headers,
		&$rows)
	{
		$headers = $this->GetReportHeaders($plugin);
		$ri = $exportRecordset;

		$exported = array();
		$rows = array();

		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			//Mark this row for update at the end to show it is part of this export
			$exported[] = $row['id'];
			$xrow = $plugin->xmltoarray($row['formvalues']);

			//Ensure the headers are registered
			$keys = array_keys($xrow);
			foreach ($keys as $key)
			{
				if (!in_array($key,$headers))
				{
					$sql = "insert into zcm_form_header (instance,header) values ({$plugin->iid},'".
						Zymurgy::$db->escape_string($key)."')";
					Zymurgy::$db->query($sql)
						or die ("Unable to create new header [$key] ($sql): ".Zymurgy::$db->error());
					$headers[] = $key;
				}
			}

			if ($membercount)
			{
				$xrow = array_merge(array('Member ID'=>$row['member'],'Member Email'=>$row['email']),$xrow);
			}

			$rows[] = $xrow;

			// echo "<pre>";
			// print_r($xrow);
			// echo "</pre>";
		}
	}

	private function GetReportHeaders($plugin)
	{
		//Get form's headers which will include headers from previous runs, even those no longer in use so that exports line up.
		$sql = "select count(*) from zcm_form_capture where member is not null and instance={$plugin->iid}";
		$ri = Zymurgy::$db->query($sql) or die("Can't check member records ($sql): ".Zymurgy::$db->error());
		$membercount = Zymurgy::$db->result($ri,0,0);
		$headers = $membercount ? array('Member ID','Member Email') : array();
		$sql = "select * from zcm_form_header where instance={$plugin->iid}";
		$ri = Zymurgy::$db->query($sql) or die("Unable to get export headers ($sql): ".Zymurgy::$db->error());
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$headers[] = $row['header'];
		}
		Zymurgy::$db->free_result($ri);

		return $headers;
	}

	function GetData($plugin, &$from, &$to)
	{
		//Now get actual data for this export
		$from = $plugin->GetCalendarControlValue('from');
		//Round to up to the next minute so we search under it, and including the full minute selected by the user.
		$to = $plugin->GetCalendarControlValue('to');

		$top = explode(' ',strftime('%Y %m %d %H %M',$to+60));
		$to = mktime($top[3],$top[4],0,$top[1],$top[2],$top[0]);

		$sql = "select zcm_form_capture.id,zcm_form_capture.formvalues,zcm_form_capture.member,zcm_member.email
			from zcm_form_capture left join zcm_member on (zcm_form_capture.member=zcm_member.id)
			where (instance={$plugin->iid}) and (unix_timestamp(submittime)>='$from') and (unix_timestamp(submittime)<'$to')";

		//Change from and to into values that can be used to identify the xls file.
		$from = strftime('%Y%m%d%H%M',$from);
		$to = strftime('%Y%m%d%H%M',$to);

		$ri = Zymurgy::$db->query($sql)
			or die("Unable to export records ($sql): ".Zymurgy::$db->error());

		return $ri;
	}

	function RenderExcelSpreadsheet(
		$plugin,
		$headers,
		$rows,
		$reportName = "formrecords")
	{
		ob_clean();
		header("Content-Type: application/vnd.ms-excel");
		header("Content-Disposition: attachment;filename=".urlencode($reportName).".xls");
		echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"
xmlns:x=\"urn:schemas-microsoft-com:office:excel\"
xmlns=\"http://www.w3.org/TR/REC-html40\">
<head>
<meta http-equiv=Content-Type content=\"text/html; charset=windows-1252\">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content=\"Zymurgy:CM Form Plugin\">
<style>
<!--table
	{mso-displayed-decimal-separator:\"\\.\";
	mso-displayed-thousand-separator:\"\\,\";}
@page
	{margin:1.0in .75in 1.0in .75in;
	mso-header-margin:.5in;
	mso-footer-margin:.5in;}
tr
	{mso-height-source:auto;}
col
	{mso-width-source:auto;}
br
	{mso-data-placement:same-cell;}
td
	{mso-style-parent:style0;
	padding-top:1px;
	padding-right:1px;
	padding-left:1px;
	mso-ignore:padding;
	color:windowtext;
	font-size:10.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Arial;
	mso-generic-font-family:auto;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border:none;
	mso-background-source:auto;
	mso-pattern:auto;
	mso-protection:locked visible;
	white-space:nowrap;
	mso-rotate:0;}
.xl24
	{mso-style-parent:style0;
	font-weight:700;
	font-family:Arial, sans-serif;
	mso-font-charset:0;}
-->
</style>
<!--[if gte mso 9]><xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>{$plugin->InstanceName} Export</x:Name>
    <x:WorksheetOptions>
     <x:Print>
      <x:ValidPrinterInfo/>
      <x:HorizontalResolution>-3</x:HorizontalResolution>
      <x:VerticalResolution>0</x:VerticalResolution>
     </x:Print>
     <x:Selected/>
     <x:Panes>
      <x:Pane>
       <x:Number>1</x:Number>
       <x:ActiveRow>2</x:ActiveRow>
       <x:ActiveCol>1</x:ActiveCol>
      </x:Pane>
     </x:Panes>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
  <x:WindowHeight>11760</x:WindowHeight>
  <x:WindowWidth>15315</x:WindowWidth>
  <x:WindowTopX>360</x:WindowTopX>
  <x:WindowTopY>75</x:WindowTopY>
  <x:ProtectStructure>False</x:ProtectStructure>
  <x:ProtectWindows>False</x:ProtectWindows>
 </x:ExcelWorkbook>
</xml><![endif]-->
		</head>
		<body>";
		echo "<table x:str border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:
 collapse;table-layout:fixed\">";
		echo "<tr>";
		echo "<td class=\"xl24\">".implode("</td><td class=\"xl24\">",$headers)."</td>";
		echo "</tr>";
		foreach($rows as $xrow)
		{
			echo "<tr>";
			foreach($headers as $key)
			{
				if (array_key_exists($key,$xrow))
					$val = $xrow[$key];
				else
					$val = '';
				echo "<td>".htmlentities($val)."</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
		echo "</body></html>";
		exit;
	}

	private function DeleteData($plugin)
	{
		$from = $plugin->GetCalendarControlValue('from');
		//Round to up to the next minute so we search under it, and including the
		// full minute selected by the user.
		$to = $plugin->GetCalendarControlValue('to');

		$top = explode(' ',strftime('%Y %m %d %H %M',$to+60));
		$to = mktime($top[3],$top[4],0,$top[1],$top[2],$top[0]);

		$sql = "delete from zcm_form_capture where (instance={$plugin->iid}) and ".
			"(unix_timestamp(submittime)>='$from') and (unix_timestamp(submittime)<'$to')";
		Zymurgy::$db->run($sql);

		echo "The selected range of data has been removed from the database.";
	}

}

class FormAddToCustomTable implements PluginExtension
{
	public function GetExtensionName()
	{
		return "Add to Custom Table";
	}

	public function GetDescription()
	{
		return "<p>When a form is submitted, the data can be automatically ".
			"added to a Custom Table. Custom Tables are generated by the ".
			"Web Development team to provide additional functionality to your ".
			"Web site.</p>".
			"<p>To work properly, the Export Table Header for the field in the ".
			"form must match the field name in the Custom Table. Fields that do ".
			"not match will be ignored.</p>".
			"<p>If you are adding to a Detail Table, you must provide the name of ".
			"the Parent table. If this form will always add to a single record in ".
			"the Parent table, also specify the ID for this record. Otherwise, ".
			"the record will be added based on a field with the same name as the ".
			"Parent table.</p>";
	}

	public function IsEnabled($plugin)
	{
		// die("Add to custom table: ".$plugin->GetConfigValue('Enable Add to Custom Table'));
		return $plugin->GetConfigValue('Enable Add to Custom Table') == "on";
	}

	public function GetConfigItems()
	{
		$configItems = array();

		$sql = "SELECT `tname` FROM `zcm_customtable` ORDER BY `tname`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of custom tables: ".mysql_error().", $sql");

		$customTables = array();
		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$customTables[] = $row["tname"];
		}

		$configItems[] = array(
			"name" => "Enable Add to Custom Table",
			"default" => "off",
			"inputspec" => "checkbox",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Custom Table Name",
			"default" => "",
			"inputspec" => "drop.".implode(",", $customTables),
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Custom Table Parent Table",
			"default" => "",
			"inputspec" => "input.20.50",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Custom Table Parent ID",
			"default" => "",
			"inputspec" => "input.5.50",
			"authlevel" => 0);

		return $configItems;
	}

	public function GetCommands()
	{
		return array();
	}

	public function ConfirmPluginCompatability($plugin)
	{
		if(!($plugin instanceof Form))
		{
			die("FormCaptureToDatabase: plugin sent not an instance of Form. Aborting.");
		}
	}

	public function CaptureFormData(
		$plugin)
	{
		// die("Add to Custom Table CaptureFormData Start");

		$this->ConfirmPluginCompatability($plugin);

		$fieldSQL = "SELECT `cname` FROM `zcm_customfield` INNER JOIN `zcm_customtable` ".
			"ON `zcm_customtable`.`id` = `zcm_customfield`.`tableid` AND ".
			"`zcm_customtable`.`tname` = '".
			Zymurgy::$db->escape_string($plugin->GetConfigValue('Custom Table Name')).
			"'";
		$fieldRI = Zymurgy::$db->query($fieldSQL)
			or die("Could not retrieve list of fields for custom table: ".mysql_error().", $fieldSQL");

		$fieldList = array();

		while(($row = Zymurgy::$db->fetch_array($fieldRI)) !== FALSE)
		{
			$fieldList[$row["cname"]] = "";
		}

		$values = $plugin->GetValues();

		foreach($values as $key => $value)
		{
			if(key_exists($key, $fieldList))
			{
				$fieldList[$key] = Zymurgy::$db->escape_string($value);
			}
		}

		$insertSQL = "INSERT INTO `{0}` ( {1} ) VALUES ( '{2}' )";
		$insertSQL = str_replace(
			"{0}",
			$plugin->GetConfigValue('Custom Table Name'),
			$insertSQL);
		$insertSQL = str_replace(
			"{1}",
			implode(", ", array_keys($fieldList)),
			$insertSQL);
		$insertSQL = str_replace(
			"{2}",
			implode("', '", array_values($fieldList)),
			$insertSQL);

		// die($insertSQL);

		Zymurgy::$db->query($insertSQL)
			or die("Could not insert record into custom table: ".mysql_error().", $insertSQL");

		return;
	}
}
?>
