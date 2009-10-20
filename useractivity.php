<?
/**
 * 
 * @package Zymurgy
 * @subpackage auth
 */
include 'header.php';
include 'datagrid.php';

$ds = new DataSet('zcm_memberaudit','id');
$ds->AddColumns('id','member','audittime','remoteip','realip','audit');

$dg = new DataGrid($ds);
$dg->AddColumn('Member','member');
$dg->AddColumn('Audit Time','audittime');
$dg->AddColumn('Remote IP','remoteip');
$dg->AddColumn('Real IP','realip');
$dg->AddLookup('member','Member','zcm_member','id','email');
$dg->AddEditor('audittime','Audit Time','datetime');
$dg->AddInput('remoteip','Remote IP:',15,15);
$dg->AddInput('realip','Real IP:',15,15);
$dg->AddTextArea('audit','Audit:');
$dg->Render();

include('footer.php');
?>