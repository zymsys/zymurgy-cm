<?php 
/**
 * To be included at the top of PHPUnit test files to get Z:CM going, etc.
 */

class TestHelper
{
	/**
	 * Expose our "singleton" to the world
	 * 
	 * @var TestHelper
	 */
	public static $helper;
	
	//Public values for important db keys
	//Members (admin, privledged, minimal, registered)
	public $member_admin;
	public $member_priv;
	public $member_min;
	public $member_reg;
	//Groups (privledged, minimal)
	public $group_priv;
	public $group_min;
	//ACLs (member data, global data)
	public $acl_member;
	public $acl_global;
	//Tables (member root, secondary, tertiary and non-member root, secondary, tertiary and non-acl)
	public $ct_mroot;
	public $ct_msec;
	public $ct_mter;
	public $ct_root;
	public $ct_sec;
	public $ct_ter;
	public $ct_noacl;
	
	//Inner workings
	private $backupfile;
	private $tablekeys = array();
	private $cleanupsql = array();
	
	function become($member)
	{
		Zymurgy::$member = $member;
	}
	
	function getcmo()
	{
		$pp = explode('/', getcwd());
		array_pop($pp); //zymurgy
		$_SERVER["APPL_PHYSICAL_PATH"] = implode('/', $pp);
		require_once 'cmo.php';
	}
	
	function backupdb()
	{
		$dumppath = Zymurgy::$root."/UserFiles/UnitTests";
		@mkdir($dumppath,0777,true);
		$this->backupfile = "$dumppath/".date('YmdHisu').".sql";
		 
		$cmd = "mysqldump -h".Zymurgy::$config['mysqlhost'].
			" -u".Zymurgy::$config['mysqluser'].
			" -p".Zymurgy::$config['mysqlpass'].
			" ".Zymurgy::$config['mysqldb'].
			" > ".$this->backupfile;
		system($cmd);
		
		//Stat it and make sure it exists and is not zero bytes.  Barf if so.
		$fi = stat($this->backupfile);
		if ($fi === false)
		{
			throw new Exception("Unable to stat ".$this->backupfile, 0);
		}
		if ($fi['size'] == 0)
		{
			throw new Exception("Shouldn't be zero bytes: ".$this->backupfile, 0);
		}
	}
	
	function remembertablekey($table,$key)
	{
		if (!array_key_exists($table, $this->tablekeys))
		{
			$this->tablekeys[$table] = array();
		}
		$this->tablekeys[$table][] = $key;
	}
	
	function cleanupdb()
	{
		foreach ($this->tablekeys as $table=>$keys)
		{
			Zymurgy::$db->run("DELETE FROM `$table` WHERE `id` IN (".
				implode(',', $keys).")");
		}
		$this->tablekeys = array();
		foreach ($this->cleanupsql as $sql)
		{
			Zymurgy::$db->run($sql);
		}
		$this->cleanupsql = array();
	}
	
	function buildtable($tname,$acl,$ismember,$parentid,$parentname)
	{
		$ismember = $ismember ? 1 : 0;
		//First the table entry:
		Zymurgy::$db->run("INSERT INTO `zcm_customtable` (`tname`,`detailfor`,`hasdisporder`,`ismember`,`idfieldname`,`acl`,`globalacl`) ".
			"VALUES ('$tname',$parentid,0,$ismember,'id',$acl,0)");
		$ct_id = Zymurgy::$db->insert_id();
		$this->remembertablekey('zcm_customtable', $ct_id);
		//Then the field entires:
		$testfields = array('a','b','c');
		foreach ($testfields as $field)
		{
			Zymurgy::$db->run("INSERT INTO zcm_customfield (tableid,cname,inputspec,caption,indexed,gridheader,acl,globalacl) ".
				"VALUES ($ct_id,'$field','input.20.20','$field:','N','$field',$acl,0)");
			$this->remembertablekey('zcm_customfield', Zymurgy::$db->insert_id());
		}
		//Finally we actually create the table:
		$sql = "CREATE TABLE `$tname` (`id` bigint(20) NOT NULL AUTO_INCREMENT,";
		if (!empty($parentname))
		{
			$sql .= "`$parentname` bigint(20) DEFAULT NULL,"; 
		}
		$sql .= "`a` varchar(20) DEFAULT NULL,
		  `b` varchar(20) DEFAULT NULL,
		  `c` varchar(20) DEFAULT NULL,";
		if ($ismember)
		{
			$sql .= "`member` bigint(20) DEFAULT NULL,";
		}
		$sql .= "PRIMARY KEY (`id`))";
		Zymurgy::$db->run($sql);
		$this->cleanupsql[] = "DROP TABLE $tname";
		return $ct_id;
	}
	
	function buildMember($name)
	{
		Zymurgy::$db->run("INSERT INTO `zcm_member` (username) values ('$name')");
		$member = Zymurgy::$db->insert_id();
		$this->remembertablekey('zcm_member', $member);
		$m = Zymurgy::$db->get("SELECT * FROM `zcm_member` WHERE `id`=".$member);
		$m['groups'] = array();
		return $m;
	}
	
	function buildGroup($name)
	{
		Zymurgy::$db->run("INSERT INTO `zcm_groups` (name) values ('$name')");
		$group = Zymurgy::$db->insert_id();
		$this->remembertablekey('zcm_groups', $group);
		return $group;
	}
	
	function addMemberToGroup(&$member,$group)
	{
		$memberid = $member['id'];
		Zymurgy::$db->run("INSERT INTO zcm_membergroup (memberid,groupid) VALUES ($memberid,$group)");
		$this->cleanupsql[] = "DELETE FROM zcm_membergroup WHERE (memberid=$memberid) AND (groupid=$group)";
		$member['groups'][$group] = Zymurgy::$db->get("SELECT `name` FROM `zcm_groups` WHERE `id`=$group");
	}
	
	function buildACL($name)
	{
		Zymurgy::$db->run("INSERT INTO `zcm_acl` (`name`) VALUES ('$name')");
		$acl = Zymurgy::$db->insert_id();
		$this->remembertablekey('zcm_acl',$acl);
		return $acl;
	}
	
	function addACLPerms($acl,$group,$perms)
	{
		foreach ($perms as $perm)
		{
			Zymurgy::$db->run("INSERT INTO zcm_aclitem (zcm_acl,`group`,permission) VALUES ($acl,$group,'$perm')");
			$this->remembertablekey('zcm_aclitem', Zymurgy::$db->insert_id());
		}
	}
	
	function buildtestdata()
	{
		//Test members
		$this->member_admin = $this->buildMember('zcm_unittest_admin');
		$this->member_priv = $this->buildMember('zcm_unittest_priv');
		$this->member_min = $this->buildMember('zcm_unittest_min');
		$this->member_reg = $this->buildMember('zcm_unittest_reg');
		//Test groups
		$this->group_priv = $this->buildGroup('zcm_unittest_priv');
		$this->group_min = $this->buildGroup('zcm_unittest_min');
		//Add test member to test group
		$this->addMemberToGroup($this->member_admin, 3); //Built in webmaster group
		$this->addMemberToGroup($this->member_priv, $this->group_priv);
		$this->addMemberToGroup($this->member_min, $this->group_min);
		//Reg isn't added to a group because they're just registered not privledged.
		//Test ACLs
		$this->acl_member = $this->buildACL('zcm_unittest_member');
		$this->acl_global = $this->buildACL('zcm_unittest_global');
		Zymurgy::Dbg($this->acl_global);
		//Add ACL items
		//Member Data:
		$this->addACLPerms($this->acl_member, 3, array('Read','Write','Delete')); //3 == Webmaster
		$this->addACLPerms($this->acl_member, $this->group_priv, array('Read','Write'));
		$this->addACLPerms($this->acl_member, $this->group_min, array('Read'));
		$this->addACLPerms($this->acl_member, 4, array('Read')); //4 == Registered User
		//Global Data:
		$this->addACLPerms($this->acl_global, $this->group_priv, array('Read','Write'));
		$this->addACLPerms($this->acl_global, 4, array('Read')); //4 == Registered User
		//Build test custom tables
		$this->ct_mroot = $this->buildtable('zcm_unittest_mroot', $this->acl_member, true, 0, '');
		$this->ct_msec = $this->buildtable('zcm_unittest_msec', $this->acl_member, false, $this->ct_mroot, 'zcm_unittest_mroot');
		$this->ct_mter = $this->buildtable('zcm_unittest_mter', $this->acl_member, false, $this->ct_msec, 'zcm_unittest_msec');
		$this->ct_root = $this->buildtable('zcm_unittest_root', $this->acl_global, false, 0, '');
		$this->ct_sec = $this->buildtable('zcm_unittest_sec', $this->acl_global, false, $this->ct_mroot, 'zcm_unittest_root');
		$this->ct_ter = $this->buildtable('zcm_unittest_ter', $this->acl_global, false, $this->ct_msec, 'zcm_unittest_sec');
		$this->ct_noacl = $this->buildtable('zcm_unittest_noacl', 0, false, 0, '');
	}
	
	function dumptable($tname)
	{
		$this->dumpsql("SELECT * FROM `$tname`");
	}
	
	function dumpsql($sql)
	{
		echo "Dump for: $sql\n";
		$ri = Zymurgy::$db->run($sql);
		$count = 0;
		while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
		{
			$count += 1;
			echo "Row $count\n";
			foreach ($row as $cname=>$value)
			{
				echo "\t$cname: [$value]\n";
			}
		}
		echo "Query returned $count row(s).\n";
	}
	
	function __construct()
	{
		$this->getcmo();
		$this->backupdb();
		$this->buildtestdata();
	}
	
	function __destruct()
	{
		$this->cleanupdb();
	}
}
TestHelper::$helper = new TestHelper();
?>