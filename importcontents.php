<?php
require_once 'header.php';
$showform = true;
$validationerror = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$remoteurl = "http://{$_POST['domain']}/zymurgy";
	//Authenticate with remote server and get an auth token
	$authtokenjson = file_get_contents("{$remoteurl}/auth.php?u=".
		urlencode($_POST['username'])."&p=".urlencode($_POST['password']));
	$authtokenobject = json_decode($authtokenjson);
	if (property_exists($authtokenobject, 'errormsg'))
	{
		$validationerror = $authtokenobject->errormsg;
	}
	else 
	{
		//We're validated; backup the local database before destroying it.
		$backuppath = Zymurgy::$root."/UserFiles/backups/";
		$backupfile = $backuppath.Zymurgy::$config['mysqldb'].date('YmdHis').'.sql';
		@mkdir($backuppath);
		$connectparams = '-h'.Zymurgy::$config['mysqlhost'].
			' -u'.Zymurgy::$config['mysqluser'].
			' -p'.Zymurgy::$config['mysqlpass'].
			' '.Zymurgy::$config['mysqldb'];
		echo "<div>Dumping existing database to $backupfile</div>";
		ob_flush();
		system("mysqldump $connectparams > $backupfile");
		echo "<div>Backup complete; downloading remote data...</div>";
		ob_flush();
		set_time_limit(0);
		$rawdata = file_get_contents("{$remoteurl}/exportcontents.php?authtoken=".$authtokenobject->authtoken);
		$data = json_decode($rawdata);
		echo "<div>Download complete; importing to local database...</div>";
		ob_flush();
		foreach ($data as $tname=>$tdata)
		{
			echo "<div>Importing $tname</div>";
			ob_flush();
			$pk = $tdata->pk;
			if (array_key_exists('recreate', $_POST) && ($_POST['recreate'] == 'on'))
			{
				Zymurgy::$db->run("DROP TABLE IF EXISTS `$tname`");
				Zymurgy::$db->run($tdata->create);
			}
			foreach ($tdata->rows as $row)
			{
				$fields = array();
				$values = array();
				$updates = array();
				foreach ($row as $field=>$value)
				{
					if ($value !== NULL)
					{
						$fields[] = "`$field`";
						$values[] = "'".Zymurgy::$db->escape_string($value)."'";
						$updates[] = "`$field`='".Zymurgy::$db->escape_string($value)."'";
					}
				}
				$sql = "INSERT INTO `$tname` (".implode(",", $fields).") VALUES (".
					implode(",",$values).") ON DUPLICATE KEY UPDATE ".implode(",", $updates);
				/*if ($tname == 'zcm_member')
				{
					echo "<div>$sql</div>\n";
				}*/
				Zymurgy::$db->run($sql);
			}
		}
		echo "<div>Import complete.  Please <a href=\"/zymurgy/installer/upgrade.php\">upgrade</a> to ensure compatibility with this version of Zymurgy:CM.</div>";
		$showform = false;
	}
}
else 
{
	$_POST = array(
		'domain' => '',
		'username' => '',
		'password' => '',
		'recreate' => ''
	);
}
if ($showform)
{
	?>
	<style type="text/css">
	th {
		text-align:right;
		font-weight:normal;
	}
	</style>
	<div style="color:red"><?php echo $validationerror; ?></div>
	<form method="post" action="importcontents.php">
	<table>
		<tr>
			<th>Remote Zymurgy:CM Domain:</th>
			<td><input type="text" name="domain" value="<?php echo htmlspecialchars($_POST['domain']); ?>"></td>
		</tr>
		<tr>
			<th>Remote Webmaster Account:</th>
			<td><input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username']); ?>"></td>
		</tr>
		<tr>
			<th>Remote Webmaster Password:</th>
			<td><input type="password" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>"></td>
		</tr>
		<tr>
			<th>Re-create local tables?:</th>
			<td><input type="checkbox" name="recreate" <?php echo ($_POST['recreate'] == 'on') ? 'checked="checked"' : ''; ?>></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="submit"><br>
			Warning: Depending on the size of the remote database this operation could take a long time.
			</td>
		</tr>
	</table>
	</form>
	<?php 
}
require_once 'footer.php';
?>