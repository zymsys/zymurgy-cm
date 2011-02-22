<?php 
require_once 'cmo.php';
require_once Zymurgy::$root."/zymurgy/model.php";
Zymurgy::headtags(false);
$result = new stdClass();
$table = Zymurgy::$db->escape_string($_GET['table']);
if (array_key_exists('id', $_GET))
{
	$id = $_GET['id'];
}
else 
{
	$id = false;
}
try {
	$m = ZymurgyModel::factory($table);
	switch ($_SERVER['REQUEST_METHOD'])
	{
		case 'POST':
			$result->success = $m->write($_POST);
			$newid = Zymurgy::$db->insert_id();
			if ($newid) $result->newid = $newid;
			break;
		case 'DELETE':
			$result->success = $m->delete($id);
			break;
		default:
			$result->data = $m->read($id);
			$result->success = is_array($result->data);
			break;
	}
	$result->affectedrows = Zymurgy::$db->affected_rows();
	if (($result->affectedrows == 0) && ($m->getMemberTableName()))
	{
		$result->warning = "Zero rows affected.  Possibly limited by member data ownership (".
			$m->getMemberTableName().").";
	}
}
catch (Exception $e) {
	$result->errormsg = $e->getMessage();
}
echo json_encode($result)."\n";
?>