<?
include 'sitepageutil.php';
include 'header.php';
include 'datagrid.php';

$templatecount = Zymurgy::$db->get("select count(*) from zcm_template");
if ($templatecount == 0)
{
	//Create default template
	Zymurgy::$db->run("insert into zcm_template (name,path) values ('Simple','/zymurgy/templates/simple.php')");
	$defaulttemplate = 1;
}
else if ($templatecount==1)
{
	$defaulttemplate = Zymurgy::$db->get("select min(id) from zcm_template");
}

//The values array contains tablename.columnname keys with the proposed new values for the updated row.
//TODO: If the nav name has changed, create a redirect from the old nav name to maintain links to the old address.
function OnBeforeInsertUpdate($values)
{
	return $values; // Change values you want to alter before the update occurs.
}

$ds = new DataSet('zcm_sitepage','id');
$ds->AddColumns('id','disporder','linktext','parent','retire','golive','softlaunch','template');
$ds->OnBeforeUpdate = $ds->OnBeforeInsert = 'OnBeforeInsertUpdate';
$ds->AddDataFilter('parent',$p);

$dg = new DataGrid($ds);
$dg->AddConstant('parent',$p);
$dg->AddColumn('Menu Text','linktext');
$dg->AddColumn('Page Contents','id','<a href="sitepagetext.php?p={0}&pp=1">Page Contents</a>');
$dg->AddColumn('Gadgets','id','<a href="sitepageextra.php?p={0}&pp=1">Gadgets</a>');
$dg->AddColumn('Sub-Pages','id','<a href="sitepage.php?p={0}">Sub-Pages</a>');
/*TODO: I'm not showing these in the grid, but I'd love to show a status column with 'Soft Launch', 'Live' or 'Retired'.  Need to extend the template feature to allow this, or some other stroke of genius.
$dg->AddColumn('Retire','retire');
$dg->AddColumn('Golive','golive');
$dg->AddColumn('Softlaunch','softlaunch');*/
$dg->AddUpDownColumn('disporder');
if ($templatecount > 1)
{
	$dg->AddEditor('template','Template:','lookup.zcm_template.id.name.name');
}
else 
{
	$dg->AddConstant('template',$defaulttemplate);
}
$dg->AddInput('linktext','Menu Text:',40,40);
$dg->AddEditor('retire','Retire After:','datetime');
$dg->AddEditor('golive','Go Live:','datetime');
$dg->AddEditor('softlaunch','Soft Launch:','datetime');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Page';
$dg->Render();

include('footer.php');
?>
