<?
/**
 * Displays a rotating/random image from a series/gallery configured in the
 * plugin. This is useful for random elements on a template, or as a
 * super-simple ad banner system.
 *
 * @package Zymurgy_Plugins
 */
class RandomImage extends PluginBase
{
	/**
	 * Return the user-friendly name of the plugin to display on the Plugin
	 * Management screen.
	 *
	 * @return string
	 */
	public function GetTitle()
	{
		return 'Rotating Image Plugin';
	}

	/**
	 * Return the user-friendly description of the plugin to display on the
	 * Plugin Details and Add Plugin screens.
	 *
	 * @return string
	 */
	public function GetDescription()
	{
		return <<<BLOCK
			<h3>Rotating Image Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>Displays a rotating/random image from a series/gallery
			configured in the plugin. This is useful for random elements on a
			template, or as a super-simple ad banner system.</p>
BLOCK;
	}

	/**
	 * Return the SQL scripts to run when uninstalling the plugin.
	 *
	 * If your plugin creates tables to store data specific to the plugin,
	 * include the "DROP TABLE" statements for those tables here.
	 *
	 */
	public function GetUninstallSQL()
	{
//		return 'drop table bobotea';
		return "";
	}

	/**
	 * Return the list of settings for the plugin to display when the user
	 * clicks on "Default Settings" on the Plugin Management screen, the
	 * "Edit Settings" menu item for a plugin instance, or the "Edit Gadget"
	 * link in the pages system.
	 *
	 * @return mixed
	 */
	public function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "Image width",
			"default" => 728,
			"inputspec" => "input.3.3",
			"authlevel" => 2);
		$configItems[] = array(
			"name" => "Image height",
			"default" => 90,
			"inputspec" => "input.3.3",
			"authlevel" => 2);

		return $configItems;
	}

	/**
	 * Return the list of default configuration settings for this plugin that
	 * must be set before attempting to use an otherwise empty instance. Most
	 * plugins do not need to return anything.
	 *
	 * @return mixed
	 */
	function GetDefaultConfig()
	{
		return array();
	}

	/**
	 * Return the list of menu items to display at the bottom of the plugin's
	 * instance screen.
	 *
	 * @return mixed
	 */
	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

//		$this->BuildMenuItem(
//			$r,
//			"View form details",
//			"pluginadmin.php?pid={pid}&iid={iid}&name={name}",
//			0);

		return $r;
	}

	/**
	 * Perform any tasks that need to be performed when the plugin is first
	 * installed. This includes, but is not limited to, creating database
	 * tables
	 *
	 */

	function Initialize()
	{
		$this->VerifyTableDefinitions();
	}

	private function VerifyTableDefinitions()
	{
		require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');

		$tableDefinitions = array(
			array(
				"name" => "zcm_galleryimage",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INT(11)", "DEFAULT NULL"),
					DefineTableField("image", "VARCHAR(60)", "DEFAULT NULL"),
					DefineTableField("link", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("caption", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("disporder", "INT(11)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => false, "type" => ""),
					array("columns" => "disporder", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
	}

	/**
	 * Render the contents of the plugin, as it should appear on the front-end
	 * web site.
	 *
	 * @return string
	 */
	function Render()
	{
		$sql = "SELECT `id`, `image` FROM `zcm_galleryimage` WHERE `instance` = '".
			Zymurgy::$db->escape_string($this->iid).
			"' ORDER BY rand() LIMIT 0, 1";
		$image = Zymurgy::$db->get($sql);

		return "<img src=\"/zymurgy/file.php?dataset=zcm_galleryimage&amp;datacolumn=image&amp;id=".
			$image["id"].
			"&amp;mime=".
			$image["image"].
			"&amp;w=".
			$this->GetConfigValue("Image width").
			"&amp;h=".
			$this->GetConfigValue("Image height").
			"\">";
	}

	/**
	 * Render the screen displayed when the user selects the instance in the
	 * Plugin Management section of Zymurgy:CM, or when they select
	 * "Edit Gadget" in the pages section of Zymurgy:CM.
	 *
	 */
	function RenderAdmin()
	{
		$ds = new DataSet(
			'zcm_galleryimage',
			'id');
		$ds->AddColumns(
			'id',
			'instance',
			'image',
			'caption',
			'link',
			'disporder');
		$ds->AddDataFilter(
			'instance',
			$this->iid);

		$dg = new DataGrid($ds);
		$dg->AddThumbColumn(
			'Image',
			'image',
			$this->GetConfigValue('Image width'),
			$this->GetConfigValue('Image height'));
		$dg->AddAttachmentEditor(
			'image',
			'Image:');
		$dg->AddColumn(
			'',
			'id',
			"<a href=\"javascript:void()\" onclick=\"aspectcrop_popup('zcm_galleryimage.image','".
				$this->GetConfigValue('Image width').
				'x'.
				$this->GetConfigValue('Image height').
				"',{0},'zcm_galleryimage.image',true)\">Adjust Image</a>");

		$dg->AddUpDownColumn('disporder');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Image';
		$dg->AddConstant('instance',$this->iid);

		$dg->Render();
	}
}

/**
 * Each plugin also requires a corresponding Factory function. The Factory
 * function is used by the base Zymurgy:CM system to instantiate a new
 * instance of the class for use.
 *
 * @return BoboTea
 */
function RandomImageFactory()
{
	return new RandomImage();
}
?>