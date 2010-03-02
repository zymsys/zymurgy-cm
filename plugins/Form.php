<?
/**
 *
 * @package Zymurgy_Plugins
 */
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

	function GetDescription()
	{
		return <<<BLOCK
			<h3>Form Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>This plugin allows you to add Forms to your web site. Using the
			Form plugin, you can create forms that:</p>
			<ul>
				<li>Send an e-mail to a specified e-mail address (Contact Us
				Forms)</li>
				<li>Send an e-mail back to the user that filled out the form
				confirming that the form was properly submitted</li>
				<li>Capture the data to Export to Excel</li>
				<li>Capture the data in a custom table</li>
			</ul>
			<p>The Form plugin also has an extensive Extension system, allowing
			you to add custom functionality to a given Form.</p>
BLOCK;
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
		$this->VerifyTableDefinitions();

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

	protected function VerifyTableDefinitions()
	{
		require_once(Zymurgy::$root."/zymurgy/installer/upgradelib.php");

		$tableDefinitions = array(
			array(
				"name" => "zcm_form_capture",
				"columns" => array(
					DefineTableField("id", "INT(10)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("submittime", "DATETIME", "NOT NULL DEFAULT '0000-00-00 00:00:00'"),
					DefineTableField("ip", "VARCHAR(15)", "NOT NULL DEFAULT ''"),
					DefineTableField("useragent", "VARCHAR(80)", "NOT NULL DEFAULT ''"),
					DefineTableField("export", "INT(11)", ""),
					DefineTableField("formvalues", "TEXT", "NOT NULL"),
					DefineTableField("member", "BIGINT", "")
				),
				"indexes" => array(
					array("columns" => "export", "unique" => false, "type" => ""),
					array("columns" => "member", "unique" => false, "type" => ""),
					array("columns" => "instance, submittime", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			),
			array(
				"name" => "zcm_form_input",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("inputtype", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("caption", "TEXT", "NOT NULL"),
					DefineTableField("header", "VARCHAR(40)", "NOT NULL DEFAULT ''"),
					DefineTableField("disporder", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("defaultvalue", "TEXT", "NOT NULL"),
					DefineTableField("isrequired", "SMALLINT(6)", "NOT NULL DEFAULT '0'"),
					DefineTableField("validator", "BIGINT", ""),
					DefineTableField("validatormsg", "TEXT", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "instance, disporder", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			),
			array(
				"name" => "zcm_form_inputtype",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("takesxtra", "SMALLINT(6)", "NOT NULL DEFAULT '0'"),
					DefineTableField("name", "VARCHAR(60)", "NOT NULL DEFAULT ''"),
					DefineTableField("specifier", "TEXT", "NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "id",
				"engine" => "InnoDB"
			),
			array(
				"name" => "zcm_form_export",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("exptime", "DATETIME", "DEFAULT NULL"),
					DefineTableField("expuser", "INT(11)", "DEFAULT NULL"),
					DefineTableField("instance", "INT(11)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "exptime", "unique" => false, "type" => ""),
					array("columns" => "instance", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			),
			array(
				"name" => "zcm_form_header",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INT(11)", "DEFAULT NULL"),
					DefineTableField("header", "VARCHAR(60)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			),
			array(
				"name" => "zcm_form_regex",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("disporder", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("name", "VARCHAR(35)", "DEFAULT NULL"),
					DefineTableField("regex", "TEXT", "")
				),
				"indexes" => array(
					array("columns" => "disporder", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
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
		$this->VerifyTableDefinitions();

		$diemsg = "Unable to upgrade Form plugin: ";
		require_once(Zymurgy::$root."/zymurgy/installer/upgradelib.php");

		// -----
		// Convert the validators from straight regex to a foriegn key of
		// the zcm_form_validator table
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
					if(is_numeric($validator))
					{
						// Validator is already set by ID. Ignore.
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
	}

	function Duplicate($oldpi)
	{
		$sql = "INSERT INTO `zcm_form_input` ( `instance`, `inputtype`, `caption`, `header`, `disporder`, `defaultvalue`, `isrequired`, `validator`, `validatormsg` ) SELECT '".
			Zymurgy::$db->escape_string($this->iid).
			"', `inputtype`, `caption`, `header`, `disporder`, `defaultvalue`, `isrequired`, `validator`, `validatormsg` FROM `zcm_form_input` WHERE `instance` = '".
			Zymurgy::$db->escape_string($oldpi->iid).
			"'";
		Zymurgy::$db->query($sql)
			or die("Could not duplicate form fields: ".Zymurgy::$db->error().", $sql");
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
			$this->InputRows[$row["header"]] = $row;
		}
		Zymurgy::$db->free_result($ri);
		$this->InputDataLoaded = true;

		$this->CallExtensionMethod("LoadInputData");
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
		echo "<input type=\"hidden\" name=\"formname\" value=\"".htmlspecialchars($this->InstanceName)."\">\r\n";
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

		// -----
		// Render the pretext for each of the InputWidget controls used on the form

		$pretextRendered = array();

		foreach ($this->InputRows as $row)
		{
			@list($inputtype, $inputparameters) = explode('.',$row['specifier'],2);

			if(!in_array($inputtype, $pretextRendered))
			{
				echo InputWidget::GetPretext($inputtype);
				$pretextRendered[] = $inputtype;
			}
		}

		// -----
		// Render the controls

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
		echo "<tr><td colspan=\"2\" class=\"SubmitCell\">".$this->GetConfigValue('Footer')."<br />";

		echo($this->RenderSubmitButton());
		echo "</td></tr>\r\n";
		echo "</table></form>";
		return "";
	}

	function RenderSubmitButton()
	{
		return "<input type=\"submit\" value=\"".
			$this->GetConfigValue('Submit Button Text')."\" />";
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

		$extensionValues = $this->CallExtensionMethod("GetValues");

		$values = (array_merge(
			$values,
			is_array($extensionValues) ? $extensionValues : array()));

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

		$this->CallExtensionMethod("Forward");

		// If none of the extensions take advantage of the
		// Forward extension method, display the standard
		// Thank You message
		echo $this->GetConfigValue('Thanks');
	}

	function IsValid()
	{
		$widget = new InputWidget();

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

			$input = "";

			if(!isset($_POST[$fieldname]))
			{
				$input = $_POST[$fieldname] = "";
			}
			else
			{
				if(is_array($_POST[$fieldname]))
				{
					$input = $_POST[$fieldname] = implode(",", $_POST[$fieldname]);
					$input = $_POST[$fieldname] = trim($_POST[$fieldname]);
				}
				else
				{
					$input = $_POST[$fieldname] = trim($_POST[$fieldname]);
				}
			}

			if (empty($row['validatormsg']))
				$vmsg = "The field \"{$row['caption']}\" failed to validate.";
			else
				$vmsg = $row['validatormsg'];

            if (($row['isrequired']==1) && ($input == ''))
			{
				$this->ValidationErrors[$row['fid']] = $vmsg; // ." (required)";
				continue;
			}
			if (array_key_exists($row['validator'],$validators) && ($validators[$row['validator']]!='') && ($input!='') && (!preg_match('/'.$validators[$row['validator']].'/',$input)))
			{
				$this->ValidationErrors[$row['fid']] = $vmsg; // ." (regex)";
			}

			// -----
			// ZK: Double check with the field's InputWidget to make sure
			// the data is valid. This is of particular importance for
			// dates, which cannot be validated using a regex.

			// print_r($row);
			$widgetValid = $widget->IsValid($row["specifier"], $input);
			// $this->ValidationErrors[$row['fid']] = $row['caption']. " Widget Valid: ".$widgetValid;

			if(!$widgetValid)
			{
				$this->ValidationErrors[$row['fid']] = $vmsg; // ." (widget)";
				continue;
	  		}
		}

		$extensionValidation = $this->CallExtensionMethod("Validate");

		$this->ValidationErrors = array_unique(array_merge(
			$this->ValidationErrors,
			is_array($extensionValidation) ? $extensionValidation : array()));

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

	public function GetDefinitionForExport()
	{
		$template = <<<XML
<?xml version="1.0"?>
<form>
	<extensions>
{0}
	</extensions>
	<inputtypes>
{1}
	</inputtypes>
	<validators>
{2}
	</validators>
	<inputs>
{3}
	</inputs>
</form>
XML;

		$xml = str_replace("{0}", $this->GetDefinitionForExport_RequiredExtensions(), $template);
		$xml = str_replace("{1}", $this->GetDefinitionForExport_InputTypes(), $template);
		$xml = str_replace("{2}", $this->GetDefinitionForExport_Validators(), $template);
		$xml = str_replace("{3}", $this->GetDefinitionForExport_Inputs(), $template);

		return $xml;
	}

	private function GetDefinitionForExport_RequiredExtensions()
	{
		$template = <<<XML
		<extension name="{0}"/>
XML;
		$extensions = $this->GetExtensions();
		$extensionXML = array();

		foreach($extensions as $extension)
		{
			if($extension->IsEnabled($this))
			{
				$xml = $template;
				$xml = str_replace("{0}", $extension->GetExtensionName(), $xml);
				$extensionXML[] = $xml;
			}
		}

		return implode("", $extensionXML);
	}

	private function GetDefinitionForExport_InputTypes()
	{
		$template = <<<XML
		<inputtype takesxtra="{0}" name="{1}" specifier="{2}" />
XML;
		$inputTypes = array();

		$sql = "SELECT `takesxtra`, `name`, `specifier` FROM `zcm_form_inputtype`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of input types: ".Zymurgy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$xml = $template;
			$xml = str_replace("{0}", $row["takesxtra"], $xml);
			$xml = str_replace("{1}", $row["name"], $xml);
			$xml = str_replace("{2}", $row["specifier"], $xml);
			$inputTypes[] = $xml;
		}

		Zymurgy::$db->free_result($ri);

		return implode("", $inputTypes);
	}

	private function GetDefinitionForExport_Validators()
	{
		$template = <<<XML
		<validator disporder="{0}" name="{1}" regex="{2}" />
XML;
		$validators = array();

		$sql = "SELECT `disporder`, `name`, `regex` FROM `zcm_form_regex` ORDER BY `disporder`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of validators: ".Zymurgy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$xml = $template;
			$xml = str_replace("{0}", $row["disporder"], $xml);
			$xml = str_replace("{1}", $row["name"], $xml);
			$xml = str_replace("{2}", $row["specifier"], $xml);
			$validators[] = $xml;
		}

		Zymurgy::$db->free_result($ri);

		return implode("", $validators);
	}

	private function GetDefinitionForExport_Inputs()
	{
		$template = <<<XML
		<input disporder="{0}" type="{1}" caption="{2}" header="{3}" defaultvalue="{4}" required="{5}" validator="{6}" validatormessage="{7}"/>
XML;
		$inputs = array();

		$sql = "SELECT `disporder`, `zcm_form_inputtype`.`name` AS `inputtype`, `caption`, `header`, `defaultvalue`, `isrequired`, `zcm_form_regex`.`name` AS `validator`, `validatormsg` FROM `zcm_form_input` INNER JOIN `zcm_form_inputtype` ON `zcm_form_inputtype`.`id` = `zcm_form_input`.`inputtype` INNER JOIN `zcm_form_regex` ON `zcm_form_regex`.`id` = `zcm_form_input`.`validater` WHERE `instance` = '".
			Zymurgy::$db->escape_string($this->iid).
			"' ORDER BY `disporder`";
		$ri =Zymurgy::$db->query($sql)
			or die("Could not retrieve list of inputs: ".Zymurgy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$xml = $template;
			$xml = str_replace("{0}", $row["disporder"], $xml);
			$xml = str_replace("{1}", $row["inputtype"], $xml);
			$xml = str_replace("{2}", $row["caption"], $xml);
			$xml = str_replace("{3}", $row["header"], $xml);
			$xml = str_replace("{4}", $row["defaultvalue"], $xml);
			$xml = str_replace("{5}", $row["isrequired"], $xml);
			$xml = str_replace("{6}", $row["validator"], $xml);
			$xml = str_replace("{7}", $row["validatormessage"], $xml);
			$inputs[] = $xml;
		}

		return implode("", $inputs);
	}

	function GetExtensions()
	{
		$extensions = array();

		$extensions[] = new FormEmailToWebmaster();
		$extensions[] = new FormEmailConfirmation();
		$extensions[] = new FormAddToCustomTable();
		$extensions[] = new FormCaptureToDatabase();
		$extensions[] = new FormExportFromDatabase();
		$extensions[] = new FormPrefillFromMemberCustomTable();
		$extensions[] = new FormForward();

		if(file_exists(Zymurgy::$root."/zymurgy/custom/plugins/Form.php"))
		{
			include_once(Zymurgy::$root."/zymurgy/custom/plugins/Form.php");

			$extensions = array_merge(
				$extensions,
				CustomFormExtensions::GetExtensions());
		}

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
			"Contact Us forms, or other forms requiring immediate action.</p>";
	}

	public function GetConfigItems($plugin = NULL)
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

	public function GetConfigItems($plugin = NULL)
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

		$from = $plugin->GetConfigValue('Confirmation Email From');
		list($mail->FromName, $mail->From) = $plugin->AddressElements(array_key_exists($from, $values) ? $values[$from] : $from);

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
			"<p>This functionality is enabled by default. Check the box ".
			"below to DISABLE this functionality. Note that some other ".
			"features may not work correctly if this functionality is ".
			"disabled.</p>";
	}

	public function IsEnabled($plugin)
	{
		return !($plugin->GetConfigValue("DISABLE capture to database") == "on");
	}

	public function GetConfigItems($plugin = NULL)
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "DISABLE capture to database",
			"default" => "",
			"inputspec" => "checkbox",
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
			"<p>There are no items to configure for this feature.</p>".
			"<p>This feature will only work correctly if the Capture to ".
			"Database feature is enabled.</p>";
	}

	public function IsEnabled($plugin)
	{
		return true;
	}

	public function GetConfigItems($plugin = NULL)
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

		// If this is a PaymentForm, which doesn't properly support the extension
		// system, call its method to add the payment response information
		if($plugin instanceof PaymentForm)
		{
			$plugin->RenderAdminDoDownload_PaymentResponses(
				$exported,
				$headers,
				$rows);
		}

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
					$sql = "insert ignore into zcm_form_header (instance,header) values ({$plugin->iid},'".
						Zymurgy::$db->escape_string($key)."')";
					Zymurgy::$db->query($sql)
						or die ("Unable to create new header [$key] ($sql): ".Zymurgy::$db->error());
					$headers[] = $key;
				}
			}

			if (isset($membercount) && $membercount)
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
<meta name=Generator content=\"".Zymurgy::GetLocaleString("Common.ProductName")." Form Plugin\">
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
				echo "<td>".htmlspecialchars($val)."</td>";
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

	public function GetConfigItems($plugin = NULL)
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
			"default" => "",
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

		$fieldSQL = "SELECT `cname`, `inputspec` FROM `zcm_customfield` INNER JOIN `zcm_customtable` ".
			"ON `zcm_customtable`.`id` = `zcm_customfield`.`tableid` AND ".
			"`zcm_customtable`.`tname` = '".
			Zymurgy::$db->escape_string($plugin->GetConfigValue('Custom Table Name')).
			"'";
		$fieldRI = Zymurgy::$db->query($fieldSQL)
			or die("Could not retrieve list of fields for custom table: ".Zymurgy::$db->error().", $fieldSQL");

		$fieldList = array();
		$specList = array();

		while(($row = Zymurgy::$db->fetch_array($fieldRI)) !== FALSE)
		{
			$fieldList[$row["cname"]] = "";
			$specList[$row["cname"]] = $row["inputspec"];
		}

		$values = $plugin->GetValues();

		foreach($values as $key => $value)
		{
			if(key_exists($key, $fieldList))
			{
				if($specList[$key] == "unixdate" && !is_numeric($value))
				{
					$value = strtotime($value);
				}

				$fieldList[$key] = Zymurgy::$db->escape_string($value);
			}
		}

		$memberTableSQL = "SELECT `ismember` FROM `zcm_customtable` WHERE ".
			"`zcm_customtable`.`tname` = '".
			Zymurgy::$db->escape_string($plugin->GetConfigValue('Custom Table Name')).
			"'";
		$memberTableRI = Zymurgy::$db->query($memberTableSQL)
			or die("Could not get settings for custom table: ".Zymurgy::$db->error().", $memberTableSQL");

		$isMemberTable = false;

		if(($memberTableRow = Zymurgy::$db->fetch_array($memberTableRI)) !== FALSE)
		{
			$isMemberTable = ($memberTableRow["ismember"] == 1);
		}

		if($isMemberTable)
		{
			$fieldList["member"] = Zymurgy::$member["id"];

			// echo("<pre>");
			// print_r($fieldList);
			// echo("<br>");
			// print_r(Zymurgy::$member);
			// echo("</pre>");

			// die();
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

class FormForward implements PluginExtension
{
	public function GetExtensionName()
	{
		return "Forward to URL";
	}

	public function GetDescription()
	{
		return "Forwards the user who submits a form to a URL";
	}

	public function IsEnabled($plugin)
	{
		return $plugin->GetConfigValue('Forward user to a URL after submit') == "on";
	}

	public function GetConfigItems()
	{
		$configItems = array();
		$configItems[] = array(
			"name" => "Forward user to a URL after submit",
			"default" => "",
			"inputspec" => "checkbox",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Forward to URL",
			"default" => "http://www.google.com/",
			"inputspec" => "input.50.4096",
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
			die("FormForward: plugin sent not an instance of Form. Aborting.");
		}
	}

	public function CaptureFormData($plugin)
	{
	}

	public function Forward($plugin)
	{
		Zymurgy::JSRedirect($plugin->GetConfigValue('Forward to URL'));
	}
}

class FormPrefillFromMemberCustomTable implements PluginExtension
{
	public function GetExtensionName()
	{
		return "Prefill from Member's Custom Table";
	}

	public function GetDescription()
	{
		return "<p>When a form is displayed, the fields in the form can be ".
			"automatically filled in based on the data in a Custom Table.</p>".
			"<p>This extension assumes that the Form is only visible to ".
			"logged in Members of your web site, and that the Custom Table ".
			"being used to pre-fill the data is a Member table.</p>".
			"<p>To work properly, the Export Table Header for the field in the ".
			"form must match the field name in the Custom Table. Fields that do ".
			"not match will be ignored.</p>";
	}

	public function IsEnabled($plugin)
	{
		return $plugin->GetConfigValue('Prefill Fields using Member Custom Table') == "on";
	}

	public function GetConfigItems($plugin = NULL)
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "Prefill Fields using Member Custom Table",
			"default" => "",
			"inputspec" => "checkbox",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Custom Table with Prefill Data",
			"default" => "",
			"inputspec" => $this->GetCustomTableInputSpec(),
			"authlevel" => 0);

		return $configItems;
	}

	private function GetCustomTableInputSpec()
	{
		$sql = "SELECT `tname` FROM `zcm_customtable` WHERE `ismember` = '1' ORDER BY `tname`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of member custom tables: ".Zymurgy::$db->error().", $sql");

		$groups = array();

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$groups[] = $row["tname"];
		}

		Zymurgy::$db->free_result($ri);

		return "drop.".implode(",", $groups);
	}

	public function GetCommands()
	{
		return array();
	}

	public function ConfirmPluginCompatability($plugin)
	{
		if(!($plugin instanceof Form))
		{
			die("FormPrefillFromMemberCustomTable: plugin sent not an instance of Form. Aborting.");
		}
	}

	public function LoadInputData($plugin)
	{
		$this->ConfirmPluginCompatability($plugin);

		//die("FormPrefillFromMemberCustomTable.LoadInputData called");
		// echo("<pre>");
		// print_r($plugin->InputRows);
		// echo("</pre><br>");

		$sql = "SELECT * FROM `".
			$plugin->GetConfigValue("Custom Table with Prefill Data").
			"` WHERE `member` = '".
			Zymurgy::$member["id"].
			"' LIMIT 0, 1";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not get information from member custom table: ".Zymurgy::$db->error().", $sql");

		//die($sql);

		if(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			foreach($row as $key => $value)
			{
				if(!is_numeric($key))
				{
//					echo("Setting default for $key to $value<br>");

					foreach($plugin->InputRows as $inputIndex => $inputRow)
					{
						if($inputRow["header"] == $key)
						{
							$plugin->InputRows[$inputIndex]["defaultvalue"] = $value;
						}
					}
				}
			}
		}

		// echo("<pre>");
		// print_r($plugin->InputRows);
		// echo("</pre><br>");
	}
}
?>
