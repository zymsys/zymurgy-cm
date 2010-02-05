<?
/**
 *
 * @package Zymurgy_Plugins
 */
class InputSpecGadget extends PluginBase
{
	function GetTitle()
	{
		return 'Input Spec Plugin';
	}

	function GetDescription()
	{
		return <<<BLOCK
			<h3>Input Specifier (inputspec) Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>Use this plugin to render data using the appropriate
			InputWidget. This is used primarily to show items, such as dates,
			that need to be differently based on localization rules.</p>
BLOCK;
	}

	function VerifyTableDefinitions()
	{
		require_once(Zymurgy::$root."/zymurgy/installer/upgradelib.php");

		$tableDefinitions = array(
			array(
				"name" => "zcm_inputspecplugin",
				"columns" => array(
					DefineTableField("id", "INT(10)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("value", "TEXT", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
	}

	function GetUninstallSQL()
	{
		return 'drop table zcm_inputspecplugin';
	}

	function GetDefaultConfig()
	{
		return array();
	}

	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function GetConfigItems()
	{
		$configItems = array();
		$configItems[] = array(
			"name" => 'Item Name',
			"default" => '',
			"inputspec" => 'input.30.30',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Input Spec',
			"default" => '',
			"inputspec" => 'inputspec',
			"authlevel" => 0);
		return $configItems;
	}

	function Initialize()
	{
		$this->VerifyTableDefinitions();
	}

	function Render()
	{
		$inputspec = $this->GetConfigValue('Input Spec');
		if (empty($inputspec))
		{
			return '';
		}
		$iw = new InputWidget();
		$value = Zymurgy::$db->get("select value from zcm_inputspecplugin where instance=".$this->iid);
		return $iw->Display($inputspec,'{0}',$value);
	}

	function RenderAdmin()
	{
		$inputspec = $this->GetConfigValue('Input Spec');
		if (empty($inputspec))
		{
			echo 'Please configure the input specifier first.';
		}
		else
		{
			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$iw = InputWidget::GetFromInputSpec($inputspec);

				if($iw->SupportsFlavours())
				{
//					echo("Flavoured inputspec detected.");

					$flavourTextID = $iw->StoreFlavouredValueFromPost('InputSpec', $_POST["InputSpec"]);
					$_POST["InputSpec"] = $flavourTextID;
				}

				$id = Zymurgy::$db->get("select id from zcm_inputspecplugin where instance=".$this->iid);
				if ($id)
					Zymurgy::$db->run("update zcm_inputspecplugin set value='".Zymurgy::$db->escape_string($_POST['InputSpec'])."' where id=$id");
				else
					Zymurgy::$db->run("insert into zcm_inputspecplugin (instance,value) values ({$this->iid}, '".Zymurgy::$db->escape_string($_POST['InputSpec'])."')");
			}
			$iw = new InputWidget();
			$value = Zymurgy::$db->get("select value from zcm_inputspecplugin where instance=".$this->iid);
?>
<form action="<?= $_SERVER["REQUEST_URI"] ?>" method="POST">
	<table>
		<tr>
			<td><?= $this->GetConfigValue("Item Name") ?>:</td>
			<td><? $iw->Render($this->GetConfigValue("Input Spec"), "InputSpec", $value); ?></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="submit" value="Save"></td>
		</tr>
	</table>
</form>
<?
		}
	}
}

function InputSpecGadgetFactory()
{
	return new InputSpecGadget();
}
?>