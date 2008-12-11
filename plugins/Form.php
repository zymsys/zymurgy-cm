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
	
	function GetDefaultConfig()
	{
		global $ZymurgyConfig;
		
		$dom = $ZymurgyConfig['sitehome'];
		if (substr($dom,0,4) == "www.")
			$dom = substr($dom,4);
		$r = array();
		$this->BuildConfig($r,'Name','My New Form','input.40.60',2);
		$this->BuildConfig($r,'Email Form Results To Address','you@'.$dom);
		$this->BuildConfig($r,'Email Form Results From Address','you@'.$dom,'input.50.100');
		$this->BuildConfig($r,'Email Form Results Subject','Feedback from '.$ZymurgyConfig['defaulttitle'],'input.50.100');	
		$this->BuildConfig($r,'Capture to Database',0,'radio.'.serialize(array(0=>'No',1=>'Yes')));
		$this->BuildConfig($r,'Submit Button Text','Send','input.80.80');
		//$this->BuildConfig($r,'Field Name','','input.40.60');
		$this->BuildConfig($r,'Validation Error Intro','There were some problems processing your information...','html.500.300');
		$this->BuildConfig($r,'Footer','','html.500.300');
		$this->BuildConfig($r,'Thanks','Thanks for your feedback!  We will get back to you shortly.','html.500.300');
		$this->BuildConfig($r,'Confirmation Email From','','input.40.60');
		$this->BuildConfig($r,'Confirmation Email Subject','','input.40.60');
		$this->BuildConfig($r,'Confirmation Email Address Field','','input.40.60');
		$this->BuildConfig($r,'Confirmation Email Contents','','html.500.300');
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
		$this->BuildMenuItem(
			$r,
			"Data export",
			"pluginadmin.php?ras=export&pid={pid}&iid={iid}&name={name}",
			0);
		$this->BuildMenuItem(
			$r,
			"Edit available input types",
			"pluginsuperadmin.php?plugin={pid}&amp;instance={iid}",
			2);
		$this->BuildDeleteMenuItem($r);		
		
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
  `validator` text NOT NULL,
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
		$this->CompleteUpgrade();		
	}
	
	function GetRelease()
	{
		return 4; //Added capture/export capabilities to db.
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
				echo "<tr><td colspan=\"2\" class=\"VerbiageCell\">{$row['caption']}</td></tr>\r\n";
			}
			else 
			{
				$isvalerr = array_key_exists($row['fid'],$this->ValidationErrors);
				$fieldname = "Field{$row['fid']}";
				if ($postback && (array_key_exists($fieldname,$_POST)))
					$fieldvalue = $_POST[$fieldname];
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
	
	function SendEmail()
	{
		//Load PHPMailer class
		$mail = Zymurgy::GetPHPMailer();
		$to = $this->GetConfigValue('Email Form Results To Address');
		$values = $this->GetValues();
		//Build body lines
		$body = array();
		$subvalues = array();
		foreach($values as $header=>$value)
		{
			$body[] = $header.': '.$value;
			$subvalues['{'.$header.'}']=$value;
		}
		if ($this->member !== false)
		{
			$body[] = "Submitted by member #{$this->member['id']}: {$this->member['email']}";
		}
		//Also substitute newline characters for blank to avoid attacks on our email headers
		$values["\r"] = '';
		$values["\n"] = '';
		$from = str_replace(array_keys($subvalues),$subvalues,$this->GetConfigValue('Email Form Results From Address'));
		$subject = str_replace(array_keys($subvalues),$subvalues,$this->GetConfigValue('Email Form Results Subject'));
		if ($to != '')
		{
			list($mail->FromName, $mail->From) = $this->AddressElements($from);
			$mail->Subject = $subject;
			$mail->AddAddress($to);
			//Look for attachments
			//echo "<pre>"; print_r($_FILES); echo "</pre><hr />";
			foreach ($_FILES as $file=>$fileinfo)
			{
				$mail->AddAttachment($fileinfo['tmp_name'],$fileinfo['name']);
			}
			$mail->Body = implode("\n",$body);
			if (!$mail->Send())
				echo " There has been a problem sending email to [$to]. ";
			//mail($to,$subject,implode("\n",$body),"From: $from\nX-WebmailSrc: $ip");
		}
		$tofield = $this->GetConfigValue('Confirmation Email Address Field');
		if ($tofield!='')
		{
			//Send confirmation email as well.
			if (!array_key_exists($tofield,$values))
				die("This form doesn't have a field called '$tofield' for email confirmation.");
			$to = str_replace(array("\r","\n"),'',$values[$tofield]);
			list($mail->FromName, $mail->From) = $this->AddressElements($this->GetConfigValue('Confirmation Email From'));
			$body = $this->GetConfigValue('Confirmation Email Contents');
			$mail->Subject = str_replace(array_keys($subvalues),$subvalues,$this->GetConfigValue('Confirmation Email Subject'));
			$mail->AltBody = strip_tags($body);
			$mail->Body = str_replace(array_keys($subvalues),$subvalues,$body);
			$mail->ClearAddresses();
			$mail->ClearAttachments();
			$mail->AddAddress($to);
			if (!$mail->Send())
				echo " There has been a problem sending email to [$to]. ";
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
	
	function StoreCapture($xml = null)
	{
		if ($xml == null)
			$xml = $this->MakeXML($this->GetValues());
		if (array_key_exists('HTTP_X_FORWARDED_FOR',$_SERVER))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else 
			$ip = $_SERVER['REMOTE_ADDR'];
		if ($this->SaveID)
		{
			$sql = "update zcm_form_capture set submittime=now(), ip='{$_SERVER['REMOTE_ADDR']}', useragent='".
				Zymurgy::$db->escape_string($_SERVER['HTTP_USER_AGENT'])."', formvalues='".
				Zymurgy::$db->escape_string($xml)."' where id=".$this->SaveID;
		}
		else 
		{
			$sql = "insert into zcm_form_capture (instance,submittime,ip,useragent,formvalues,member) values ({$this->iid},now(),
				'{$_SERVER['REMOTE_ADDR']}','".Zymurgy::$db->escape_string($_SERVER['HTTP_USER_AGENT'])."','".
				Zymurgy::$db->escape_string($xml)."',".
				($this->member === false ? 'NULL' : $this->member['id']).")";
		}
		Zymurgy::$db->query($sql) or die("Unable to store form info ($sql): ".Zymurgy::$db->error());
	}
	
	function RenderThanks()
	{
		if ($this->GetConfigValue('Email Form Results To Address') != '')
			$this->SendEmail();
		if ($this->GetConfigValue('Capture to Database') == 1)
			$this->StoreCapture();
		echo $this->GetConfigValue('Thanks');
	}
	
	function IsValid()
	{
		foreach($this->InputRows as $row)
		{
			$fieldname = "Field".$row['fid'];
			//If not required and doesn't exist, just continue.  Think checkboxes.
			if (($row['isrequired']==0) && (!array_key_exists($fieldname,$_POST)))
				continue;
			$input = $_POST[$fieldname] = trim($_POST[$fieldname]);
			if (($row['isrequired']==1) && ($input == ''))
			{
				$this->ValidationErrors[$row['fid']] = $row['validatormsg'];
				continue;
			}
			if (($row['validator']!='') && ($input!='') && (!preg_match('/'.$row['validator'].'/',$input)))
			{
				$this->ValidationErrors[$row['fid']] = $row['validatormsg'];
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
				case 'export': 
					$this->RenderAdminExport(); 
					break;
				
				case 'doexport':
					$expid = $this->RenderAdminPrepDownload();
					$this->RenderAdminDoExport();
					break;
					
				case 'dodownload': 
					$expid = 0 + $_GET['expid'];
					$this->RenderAdminDoDownload($expid); 
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
								
			//echo "<a href=\"pluginadmin.php?ras=fields&pid={$this->pid}&iid={$this->iid}&name=".urlencode($this->InstanceName)."\">Edit Fields</a><br />\r\n";
			//echo "<a href=\"pluginadmin.php?ras=export&pid={$this->pid}&iid={$this->iid}&name=".urlencode($this->InstanceName)."\">Data Exports</a><br />\r\n";
		}
		//echo "doexport"; exit;		
	}
	
	function xmltoarray($xml)
	{
		$xvals = array();
		$xindex = array();
		$p = xml_parser_create();
		$xi = xml_parse_into_struct($p,$xml,$xvals,$xindex);
		xml_parser_free($p);
		$xrow = array();
		foreach($xindex['VALUE'] as $idx)
		{
			$key = $xvals[$idx]['attributes']['HEADER'];
			if (array_key_exists('value',$xvals[$idx]))
				$val = $xvals[$idx]['value'];
			else 
				$val = '';
			$xrow[$key] = $val;
		}
		return $xrow;
	}
	
	function RenderAdminDoExport()
	{
		$this->RenderAdminExport();
		$this->RenderDownloadCode($_GET['pid'],$_GET['iid'],$_GET['name']);
	}
	
	/**
	 * Generate an export ID and assign all unexported records to it.
	 *
	 * @return int export ID
	 */
	function RenderAdminPrepDownload()
	{
		global $zauth;
		$csql = "insert into zcm_form_export (exptime,expuser,instance) values (now(),".$zauth->authinfo['id'].",{$this->iid})";
		Zymurgy::$db->query($csql) or die ("Unable to create export ($csql): ".Zymurgy::$db->error());
		$expid = Zymurgy::$db->insert_id();
		$sql = "update zcm_form_capture set export=$expid where export is null";
		Zymurgy::$db->query($sql) or die("Unable to mark fields as exported ($sql): ".Zymurgy::$db->error());
		return $expid;
	}
	
	function RenderAdminDoDownload($expid)
	{
		global $zauth;

		//Get form's headers which will include headers from previous runs, even those no longer in use so that exports line up.
		$sql = "select count(*) from zcm_form_capture where member is not null and instance={$this->iid}";
		$ri = Zymurgy::$db->query($sql) or die("Can't check member records ($sql): ".Zymurgy::$db->error());
		$membercount = Zymurgy::$db->result($ri,0,0);
		$headers = $membercount ? array('Member ID','Member Email') : array();
		$sql = "select * from zcm_form_header where instance={$this->iid}";
		$ri = Zymurgy::$db->query($sql) or die("Unable to get export headers ($sql): ".Zymurgy::$db->error());
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$headers[] = $row['header'];
		}
		Zymurgy::$db->free_result($ri);
		//Now get actual data for this export
		$sql = "select zcm_form_capture.id,zcm_form_capture.formvalues,zcm_form_capture.member,member.email from zcm_form_capture left join member on (zcm_form_capture.member=member.id) where (instance={$this->iid}) and export=$expid";
		$ri = Zymurgy::$db->query($sql) or die("Unable to export records ($sql): ".Zymurgy::$db->error());
		$exported = array();
		$rows = array();
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			//Mark this row for update at the end to show it is part of this export
			$exported[] = $row['id'];
			$xrow = $this->xmltoarray($row['formvalues']);
			//Ensure the headers are registered
			$keys = array_keys($xrow);
			foreach ($keys as $key)
			{
				if (!in_array($key,$headers))
				{
					$sql = "insert into zcm_form_header (instance,header) values ({$this->iid},'".Zymurgy::$db->escape_string($key)."')";
					Zymurgy::$db->query($sql) or die ("Unable to create new header [$key] ($sql): ".Zymurgy::$db->error());
					$headers[] = $key;
				}
			}
			if ($membercount)
			{
				$xrow = array_merge(array('Member ID'=>$row['member'],'Member Email'=>$row['email']),$xrow);
			}
			$rows[] = $xrow;
			echo "<pre>";
			print_r($xrow);
			echo "</pre>";
		}
		//exit;
		//Now actually dump the data
		ob_clean();
		header("Content-Type: application/vnd.ms-excel");
		header("Content-Disposition: attachment;filename=formrecords$expid.xls");
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
    <x:Name>{$this->InstanceName} Export</x:Name>
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

	function RenderDownloadCode($pid,$iid,$name)
	{
		echo "<iframe frameborder=\"0\" width=\"10\" height=\"10\" src=\"";
		echo "pluginadmin.php?ras=dodownload&pid=$pid&iid=$iid&name=".
			urlencode($name)."\"></iframe>\r\n";
		/*echo "<script language=\"javascript\">\r\n";
		echo "window.location.href='pluginadmin.php?ras=dodownload&pid=$pid&iid=$iid&name=".
			urlencode($name)."';\r\n";*/
		/*echo "<!--
function DownloadExport(pid,iid,name) {
	window.location.href='pluginadmin.php?ras=doexport&pid='+pid+'&iid='+iid+'&name='+escape(name);
	window.location.href='pluginadmin.php?ras=export&pid='+pid+'&iid='+iid+'&name='+escape(name);
}
//-->\r\n";*/
		//echo "</script>\r\n";
	}
	
	function RenderAdminExport()
	{
		$sql = "select count(*) from zcm_form_capture where export is null";
		$ri = Zymurgy::$db->query($sql);
		$newcaps = Zymurgy::$db->result($ri,0,0);
		if ($newcaps>0)
		{
			/*echo "<a href=\"javascript:DownloadExport({$this->pid},{$this->iid},'".
				str_replace("'","''",$this->InstanceName).
				"')\">$newcaps new record(s) ready to download.</a><br />\r\n";*/
			echo "<a href=\"pluginadmin.php?ras=doexport&pid={$this->pid}&iid={$this->iid}&name=".urlencode($this->InstanceName)."\">$newcaps new record(s) ready to download.</a><br />\r\n";
		}
		$ds = new DataSet('zcm_form_export','id');
		$ds->AddColumns('id','exptime','expuser','instance');
		$ds->AddDataFilter('instance',$this->iid);
		
		$dg = new DataGrid($ds);
		$dg->AddColumn('Export Time','exptime');
		$dg->AddColumn('Exported By','expuser');
		$dg->AddColumn('','id',"<a href=\"pluginadmin.php?ras=dodownload&expid={0}&pid={$this->pid}&iid={$this->iid}&name=".urlencode($this->InstanceName)."\">Download Again</a><br />\r\n");
		$dg->AddLookup('expuser','Exported By:','zcm_passwd','id','username');
		$dg->insertlabel = '';
		$dg->Render();
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
		$dg->AddInput('validator','Regex Validator:',4096,60);
		$dg->AddInput('validatormsg','Validator Message:',4096,60);
		$dg->AddUpDownColumn('disporder');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Field';
		$dg->AddConstant('instance',$this->iid);
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
}

function FormFactory()
{
	return new Form();
}
?>
