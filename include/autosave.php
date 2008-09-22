<?
//Set these to change the max auto-drafts and saved drafts to keep before expiring them.
$maxauto = 10;
$maxsaved = 10;

//Authenticate requests to work with auto-save data
require_once("../ZymurgyAuth.php");
$zauth = new ZymurgyAuth();
$zauth->Authenticate("../login.php"); //Ok, I can't really direct users to the login script through json, but it should send a message to hackers to piss off.

require_once('../cmo.php');
/**
 * If this is a post, save the draft information for the form.  Remove old drafts.
 * If there is a get variable called listdrafts, return JSON for each available draft for the form name provided.
 * If there is a get variable called fetchdraft, return the JSON data previously saved for that draft.
 */
if ($_SERVER['REQUEST_METHOD']=='POST')
{
	//Save new draft
	$sql = "insert into zcm_draft (saved,form,json) values (now(),'".
		Zymurgy::$db->escape_string($_POST['form'])."','".
		Zymurgy::$db->escape_string($_POST['json'])."')";
	Zymurgy::$db->query($sql) or die("Unable to save draft ($sql): ".Zymurgy::$db->error());
	
	//Delete old drafts when lists are requested.
	exit;
}
if (array_key_exists('listdrafts',$_GET))
{
	$sql = "select id,unix_timestamp(saved) as saved,keeper from zcm_draft where form='".Zymurgy::$db->escape_string($_GET['listdrafts'])."' order by saved desc";
	$ri = Zymurgy::$db->query($sql) or die("Unable to fetch draft list ($sql): ".Zymurgy::$db->error());
	$drafts = array();
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$drafts[$row['id']] = $row;
	}
	//For each draft, either add it to the json, or add it to the expired list and nuke it.
	$json = array();
	$expired = array();
	$autocount = 0;
	$savedcount = 0;
	foreach($drafts as $key=>$row) 
	{
		if ($row['keeper']==1)
		{
			//This is a saved draft, as opposed to an auto-saved draft.  Saved drafts are only stored when a user saves an update.
			$description = "Saved at";
			if ($savedcount == $maxsaved)
			{
				$expired[] = $key;
				continue;
			}
			else 
			{
				$savedcount++;
			}
		}
		else 
		{
			//This is a auto-saved draft, as opposed to an saved draft.  Auto-saved drafts are saved every minute during editing.
			$description = "Auto-saved at";
			if ($autocount == $maxauto)
			{
				$expired[] = $key;
				continue;
			}
			else 
			{
				$autocount++;
			}
		}
		$json[] = "\"{$row['id']}\":\"$description ".date("Y-m-d",$row['saved'])." at ".date("g:i a",$row['saved'])."\"";
	}
	echo '{'.implode(',',$json).'}';
	foreach ($expired as $oldkey)
	{
		Zymurgy::$db->query("delete from zcm_draft where id=$oldkey");
	}
	exit;	
}
if (array_key_exists('fetchdraft',$_GET))
{
	$sql = "select * from zcm_draft where id=".(0 + $_GET['fetchdraft']);
	$ri = Zymurgy::$db->query($sql) or die("Unable to fetch draft ($sql): ".Zymurgy::$db->error());
	$row = Zymurgy::$db->fetch_array($ri) or die("No such draft available.");
	echo $row['json'];
	exit;
}
?>