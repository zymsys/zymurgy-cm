<?php
/**
 * The BoboTea plugin is a sample class that you can use to create your own
 * plugins for Zymurgy:CM. It contains the minimum necessary methods required,
 * as well as commented-out sample code.
 *
 * @package Zymurgy_Plugins
 */
class CommentThread extends PluginBase
{
	/**
	 * Return the user-friendly name of the plugin to display on the Plugin
	 * Management screen.
	 *
	 * @return string
	 */
	public function GetTitle()
	{
		return 'CommentThread Plugin';
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
			<h3>CommentThread Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>Allows for comment threads</p>
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
		return 'DROP TABLE zcm_comments_plugin';
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
//		$configItems = array();
//
//		$configItems[] = array(
//			"name" => 'Sample Setting',
//			"default" => '',
//			"inputspec" => 'input.50.200',
//			"authlevel" => 0);
//
//		return $configItems;

		return array();
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
		$tableDefinitions = array(
			array(
				"name" => "zcm_comments_plugin",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "AUTO_INCREMENT"),
					DefineTableField("instance", "INT(11)", "NOT NULL"),
					DefineTableField("userid", "int(11)", "NOT NULL"),
					DefineTableField("subject", "VARCHAR(80)", "NULL"),
					DefineTableField("time", "TIMESTAMP", "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
					DefineTableField("comment", "TEXT", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => false, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
	}
	private function post(){
		if (!$_POST['comment']) return;
		if ($_POST['thread'] != $this->iid) return;
		$subject = htmlspecialchars(trim($_POST['subject']));
		$comment = htmlspecialchars(trim($_POST['comment']));
		$uid = Zymurgy::$member['id'];
		$iid = $this->iid;
		
		$comment = '<p>'.$comment.'</p>';
		// strip out CRs
		$comment = preg_replace('@\r@', '', $comment);
		// replace newline with <br />
		$comment = preg_replace('@\n@', "<br />\n", $comment);
		// replace multiple <br /> with new paragraphs
		$comment = preg_replace('@(<br />\n){2,}@', "</p>\n<p>", $comment);
		
		$subject = Zymurgy::$db->escape_string($subject);
		$comment = Zymurgy::$db->escape_string($comment);
		
		Zymurgy::$db->run( <<<SQL
INSERT INTO zcm_comments_plugin (instance, userid, subject, comment)
VALUES("$iid", "$uid", "$subject", "$comment");
SQL
		);
	}

	function Render()
	{
		$loggedin = Zymurgy::memberauthenticate();
		
		if ($loggedin
			&& $_SERVER['REQUEST_METHOD']== 'POST'
			&& $_POST['action'] == 'zcm_post_comment'){
				
			$this->post();
		}
		
		$iid = $this->iid;
		echo "\n<div id=\"zcm_comment_thread_$iid\" class=\"zcm_comment_thread\">\n";
		
		$results = Zymurgy::$db->run( <<<SQL
SELECT zcm_comments_plugin.*,  zcm_member.username,
	UNIX_TIMESTAMP(zcm_comments_plugin.time) AS timestamp
FROM zcm_comments_plugin INNER JOIN zcm_member
    ON zcm_comments_plugin.userid = zcm_member.id
WHERE instance = $iid;
SQL
		);	
		
		while ($row = Zymurgy::$db->fetch_array($results)){
			$heading = "";
			if ($row['subject']) $heading = "<h3>{$row['subject']}</h3>";
			$date = date('M j, Y, g:i A', $row['timestamp']);
			echo <<<HTML
<div class="comment" id="comment{$row['id']}">
$heading
<div class="comment-info">Posted by {$row['username']} on $date.</div>
{$row['comment']}
</div>


HTML;
		}
		
		if ($loggedin){
		echo <<<HTML
<form id="zcm_comment_post_$iid" action="?" method="post" class="zcm_commentform">
  <h3>Post a Comment</h3>
  <div class="subjectwrap">
    <label for="subject">Subject (optional):</label>
    <input type="text" name="subject" />
  </div>
  <div class="commentwrap">
  	<label for="comment">Your comment:</label>
  	<textarea name="comment"></textarea>
  </div>
  <input type="hidden" name="thread" value='$iid' />
  <div class="submitwrap">
  <input type="hidden" name="action" value="zcm_post_comment" />
  </div>
  <input type="submit" value="Post Comment" />
</form>


HTML;
		}
		echo "</div>\n";
	}

	/**
	 * Render the screen displayed when the user selects the instance in the
	 * Plugin Management section of Zymurgy:CM, or when they select
	 * "Edit Gadget" in the pages section of Zymurgy:CM.
	 *
	 */
	function RenderAdmin()
	{
		echo "There are no settings for this plugin.";
	}
}

/**
 * Each plugin also requires a corresponding Factory function. The Factory
 * function is used by the base Zymurgy:CM system to instantiate a new
 * instance of the class for use.
 *
 * @return BoboTea
 */
function CommentThreadFactory()
{
	return new CommentThread();
}
?>