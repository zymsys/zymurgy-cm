<?
/**
 * 
 * @package Zymurgy
 * @subpackage auth
 */
$adminlevel = 1;

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"usermng.php\">User Management</a> &gt; Edit";
}
else 
{
	$breadcrumbTrail = "User Management";	
}

include('header.php');
include('datagrid.php');

function abortmsg($msg)
{
	$_GET['abort'] = $msg;
}

function OnDelete($values)
{
	global $zauth;
	
	if ($values['zcm_passwd.admin'] > $zauth->authinfo['admin'])
	{
		abortmsg("You aren't allowed to delete a more privileged administrative account.<p>");
		return false; //Override delete
	}
	if ($values['zcm_passwd.id'] == 1)
	{
		abortmsg("The first user cannot be deleted but may be edited.<p>");
		return false; //Override delete
	}
	if ($values['zcm_passwd.username'] == $zauth->authinfo['userid'])
	{
		abortmsg("You aren't allowed to delete your own account.<p>");
		return false; //Override delete
	}
}

function OnPreRenderEdit($values)
{
	if (!empty($values['zcm_passwd.password']))
	{
		$values['zcm_passwd.password'] = '     ';
	}
	return $values;
}

function OnBeforeUpdate($values)
{
	global $ds;
	global $zauth;
	
	if ($values['zcm_passwd.admin'] > $zauth->authinfo['admin'])
	{
		$values['zcm_passwd.admin'] = $zauth->authinfo['admin']; //Can't elevate above own priv level
	}	
	if ($values['zcm_passwd.id'] == 1)
	{
		$values['zcm_passwd.admin'] = 2; //First user is always admin, no matter what.
	}
	if ($values['zcm_passwd.password']=='     ')
	{
		//Password wasn't updated, set back to default value
		//echo "<pre>"; print_r($ds->rows[0]->originalvalues['zcm_passwd.password']); echo "</pre>"; exit;
		$ovalues = array_values($ds->rows);
		//echo "<pre>"; print_r($ovalues); echo "</pre>"; exit;
		$values['zcm_passwd.password'] = $ovalues[0]->originalvalues['zcm_passwd.password'];
	}
	return $values;
}

if (array_key_exists('abort',$_GET))
{
	echo "<p>".$_GET['abort']."</p>";
}

$ds = new DataSet('zcm_passwd','id');
$ds->AddColumn('id',false);
$ds->AddColumn('username',true);
$ds->AddColumn('password',true);
$ds->AddColumn('email',true);
$ds->AddColumn('fullname',true);
$ds->AddColumn('admin',false);
$ds->OnDelete = "OnDelete";
$ds->OnPreRenderEdit = "OnPreRenderEdit";
$ds->OnBeforeUpdate = "OnBeforeUpdate";

$dg = new DataGrid($ds);
$dg->SaveDrafts = false; //Not with password data included.
$dg->AddColumn('Login Name','username');
$dg->AddColumn('EMail','email','<a href="mailto:{0}">{0}</a>');
$dg->AddColumn('Full Name','fullname');
$dg->AddColumn('','password','');
$dg->AddColumn('','admin','');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->AddInput('username','Login Name:',20,20);
$dg->AddEditor('password','Password:','password.20.20');
$dg->AddInput('email','EMail:',80,20);
$dg->AddInput('fullname','Full Name:',60,30);
$adminopts = array(0=>'Normal User',1=>'Administrator');
if ($zauth->authinfo['admin']>=2)
{
	$adminopts[2] = 'Webmaster';
}
if (array_key_exists('editkey',$_GET))
{
	//We're editing.  Should we show the auth level editor?
	$k = 0 + $_GET['editkey'];
	if ($k!=1)
	{
		//We're not editing the first user, so maybe we should show it.  Do we have better or equal auth?
		$theirauth = Zymurgy::$db->get("select admin from zcm_passwd where id=$k");
		echo "[$theirauth]";
		if ($theirauth <= $zauth->authinfo['admin'])
		{
			$dg->AddRadioEditor('admin','Authorization Level:',$adminopts);
		}
	}
}
$dg->insertlabel = 'Add a New User';
$dg->Render();

include('footer.php');
?>
