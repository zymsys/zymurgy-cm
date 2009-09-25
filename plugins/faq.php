<?
class Faq extends PluginBase
{
	function GetTitle()
	{
		return 'FAQ Plugin';
	}

	function GetUninstallSQL()
	{
		return 'drop table faq; drop table faq_cat';
	}
	
	function RemoveInstance()
	{
		$sql = "delete from faq where instance={$this->iid}";
		Zymurgy::$db->query($sql);
		$sql = "delete from faq_cat where instance={$this->iid}";
		Zymurgy::$db->query($sql);
		parent::RemoveInstance();
	}

	function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "FAQ Title",
			"default" => "Frequently Asked Questions",
			"inputspec" => "input.50.200",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Title Tag",
			"default" => "h1",
			"inputspec" => "input.50.50",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Category Tag",
			"default" => "h2",
			"inputspec" => "input.50.50",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Question Tag",
			"default" => "h4",
			"inputspec" => "input.50.50",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Answer Tag",
			"default" => "p",
			"inputspec" => "input.50.50",
			"authlevel" => 0);
			/*
		$configItems[] = array(
			"name" => "Bullet",
			"default" => "->",
			"inputspec" => "input.20.20",
			"authlevel" => 0);*/
		/*$configItems[] = array(
			"name" => "Jump to Category",
			"default" => 'yes',
			"inputspec" => 'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			"authlevel" => 0);*/
		$configItems[] = array(
			"name" => "Show Category Anchor Links",
			"default" => 'yes',
			"inputspec" => 'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			"authlevel" => 0);
			
		$configItems[] = array(
			"name" => "Show Question Anchor Links",
			"default" => 'yes',
			"inputspec" => 'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Answer Editor",
			"default" => "HTML",
			"inputspec" => 'radio.'.serialize(array('HTML'=>'HTML','text'=>'Text')));
			/*
		$configItems[] = array(
			"name" => "List Questions",
			"default" => 'yes',
			"inputspec" => 'radio.'.serialize(array('yes'=>'Yes','no'=>'No')),
			"authlevel" => 0);*/
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
		return array();
	}

	function Initialize()
	{
		 Zymurgy::$db->query("CREATE TABLE `faq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `disporder` int(10) unsigned NOT NULL,
  `instance` int(10) unsigned NOT NULL,
  `category` int(10) unsigned NOT NULL,
  `question` text default '',
  `answer` text default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");

		 Zymurgy::$db->query("CREATE TABLE `faq_cat` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `disporder` int(10) unsigned NOT NULL,
  `instance` int(10) unsigned NOT NULL,
  `name` text default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
		 echo "Initialized";
	}
	
	function OpenTag($tag)
	{
		return "<".$this->GetConfigValue($tag).">";
	}
	
	function CloseTag($tag)
	{
		return "</".$this->GetConfigValue($tag).">";
	}
	
	function Render()
	{
		$showlinkcats = ($this->GetConfigValue('Show Category Anchor Links') == 'yes');
		$showlinkques = ($this->GetConfigValue('Show Question Anchor Links') == 'yes');
		
		echo "<div class=\"faq\">";
		echo "<div class=\"faq_title\">";
		if ($showlinkcats){
			echo "<a name=\"top\"></a>";
		}
		echo $this->OpenTag('Title Tag').$this->GetConfigValue('FAQ Title').$this->CloseTag('Title Tag');
		echo "</div>";
		echo "<div class=\"faq_contents\">";
		$sql_cat = "Select id,name from faq_cat where instance=".$this->iid." order by disporder";
		
		//The links at the top of the faq only render if the category setting is turned on
		//The category setting overides the question setting.
		if ($showlinkcats){
			
			$ri_cat = Zymurgy::$db->query($sql_cat);
			
			while(($cats = Zymurgy::$db->fetch_array($ri_cat)) !== false){
				echo "<div class=\"faq_toplink_cat\">";
				echo "<a  id=\"faq_toplink_cat\" href=\"#".$cats["name"]."\">".$cats["name"]."</a><br>";
				if ($showlinkques){
					$sql_que = "select question from faq where ".
						"(instance=".$this->iid." and category=".$cats["id"].") order by disporder";
					$ri_que = Zymurgy::$db->query($sql_que);
					echo "<div class=\"faq_toplink_ques\">";
					echo "<ul id=\"faq_toplink_ques\">";
					while ($ques = Zymurgy::$db->fetch_array($ri_que)){
						echo "<li>";
						echo "<a href=\"#".$ques["question"]."\">".$ques["question"]."</a><br>";
						echo "</li>";
					}
					echo "</ul></div>";
					Zymurgy::$db->free_result($ri_que);
				}
				echo "</div>";
			}
			Zymurgy::$db->free_result($ri_cat);
		}
		echo "</div>";
		echo "<div class=\"faq_main\">";
		$ri_cat = Zymurgy::$db->query($sql_cat);
		
		//Now render the actual faq categories
		while(($cats = Zymurgy::$db->fetch_array($ri_cat)) !== false){
			echo "<div class=\"faq_main_cat\">";
			if ($showlinkcats){
				echo "<a id=\"faq_cat_anchor\" href =\"#top\" name=\"".$cats["name"]."\">";
			}
			echo $this->OpenTag('Category Tag').$cats["name"].$this->CloseTag('Category Tag');
			if ($showlinkcats){
				echo "</a>";
			}
			echo "</div><br>";
			$sql_que = "select question,answer from faq where ".
						"(instance=".$this->iid." and category=".$cats["id"].") order by disporder";
			$ri_que = Zymurgy::$db->query($sql_que);
			
			//and now render the faq questions for each category
			while(($ques = Zymurgy::$db->fetch_array($ri_que)) !== false){
				echo "<div class=\"faq_main_ques\">";
				if($showlinkcats && $showlinkques){
					echo "<a id=\"faq_ques_anchor\" name=\"".$ques["question"]."\" href=\"#top\">";
				}
				echo $this->OpenTag('Question Tag').$ques["question"].$this->CloseTag('Question Tag');
				if ($showlinkcats && $showlinkques){
					echo "</a>";
				}
				echo "</div><br>";
				echo $this->OpenTag('Answer Tag').$ques["answer"].$this->CloseTag('Answer Tag')."<br>";
			}
			Zymurgy::$db->free_result($ri_que);
		}
		echo "</div>";
		echo "</div>";
	}
	
	function RenderAdmin()
	{
		echo "<b>".$this->GetConfigValue('FAQ Title')."</b>";
		//Give them the option of switching between Category View and Question View
		//FIXME: This needs to not show up on the Add Faq page.
		
		if ((!array_key_exists("action", $_GET)) && (!array_key_exists("editkey",$_GET)) && (!array_key_exists("deletekey",$_GET))){
			echo "<p>This plugin allows you to maintain a list of frequently asked ".
			"questions, complete with categories and subcategories.</p>";
		}
				
		//echo "<a href=\"pluginadmin.php?pid=".$this->pid."&iid=".$this->iid."&cat=".(($_GET["cat"] == 'true')?"false":"true")."\">Switch to ".
		//(($_GET["cat"]=="true")?" Question ":" Category ")."View</a>";
		
		//If the tables aren't there already, make them.
		Zymurgy::$db->query("select id from faq") or die($this->Initialize());
		
		//Default is show questions. If Option to see cats is filled, show that instead.
			if (array_key_exists("cat",$_GET)){
				echo "<a href=\"pluginadmin.php?pid=".$this->pid."&iid=".$this->iid.
				 "\">Go back to categories</a>";
		
				$ds = new DataSet('faq','id');
				$ds->AddColumns('id','question','answer','category','instance','disporder');
				$ds->AddDataFilter('instance',$this->iid);
				$ds->AddDataFilter('category',$_GET["cat"]);

				$dg = new DataGrid($ds);
				$dg->AddTextArea('question','Question:');
								
				//not included, but a nice sql injection...
				//$dg->AddLookup('category','Category','faq_cat where instance='.$this->iid,'id','name');
				
				$dg->AddColumn('Question','question');
				$dg->AddEditColumn();
				$dg->AddDeleteColumn();
				$dg->AddUpDownColumn('disporder');
				
				//They get to choose in the config whether or not to edit it with FCK
				if ($this->GetConfigValue('Answer Editor') == 'HTML'){
					$dg->AddHtmlEditor('answer','Answer:');
				}else{
					$dg->AddTextArea('answer','Answer:');
				}
				$dg->AddConstant('instance',$this->iid);
				$dg->AddConstant('category',$_GET["cat"]);
				$dg->insertlabel = 'Add new FAQ';
				$dg->Render();
			}else{ //(if $_GET["CAT"] isn't defined...)
				
				$ds = new DataSet('faq_cat','id');
				
				$ds->AddColumns('id','name','instance','disporder');
				$ds->AddDataFilter('instance',$this->iid);
				
				$dg = new DataGrid($ds);
				
				$dg->AddColumn('Name', 'name');
				$dg->AddButton("Questions","pluginadmin.php?pid=".$this->pid."&iid=".$this->iid."&cat={0}".$this->id);
				$dg->AddEditColumn();
				$dg->AddDeleteColumn();
				$dg->AddUpDownColumn('disporder');
				
				$dg->AddConstant('instance',$this->iid);
				$dg->AddTextArea('name', 'Name:');
				$dg->insertlabel = 'Add New Category';
				
				$dg->Render();
			}


	}
}
	
	

	function AdminMenuText()
	{
		return 'FAQ';
	}

	function FaqFactory()
	{
		return new Faq();
	}
?>