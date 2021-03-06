<?php
/**
 * Contains the information and validation routines for a Member.
 *
 * @package Zymurgy
 * @subpackage auth
 */
	class Member
	{
		private $m_id;
		private $m_username;
		private $m_email;
		private $m_password;
		private $m_fullname;
		private $m_registration_date;
		private $m_last_authorized;

		private $m_groups = array();

		private $m_errors = array();

		/**
		 * Get the member's ID, as stored in the database.
		 *
		 * @return int The ID
		 */
		public function get_id()
		{
			return $this->m_id;
		}

		/**
		 * Set the member's ID, as stored in the database.
		 *
		 * @param int $newValue The ID
		 */
		public function set_id($newValue)
		{
			$this->m_id = $newValue;
		}

		/**
		 * Get the member's username.
		 *
		 * @return string
		 */
		public function get_username()
		{
			return $this->m_username;
		}

		/**
		 * Set the member's username.
		 *
		 * @param string $newValue
		 */
		public function set_username($newValue)
		{
			$this->m_username = $newValue;
		}

		/**
		 * Get the member's e-mail address.
		 *
		 * @return string
		 */
		public function get_email()
		{
			return $this->m_email;
		}

		/**
		 * Set the member's e-mail address.
		 *
		 * @param string $newValue
		 */
		public function set_email($newValue)
		{
			$this->m_email = $newValue;
		}

		/**
		 * Get the member's salted and hashed password.
		 *
		 * @return string
		 */
		public function get_password()
		{
			return $this->m_password;
		}

		/**
		 * Set the member's password from plain text.
		 *
		 * @param string $newValue
		 */
		public function set_passwordplain($newValue)
		{
			$salt = uniqid();
			$this->m_password = $salt.md5($salt.$newValue);
		}

		/**
		 * Set the member's salted and hashed password.
		 *
		 * @param string $newValue
		 */
		public function set_password($newValue)
		{
			$this->m_password = $newValue;
		}

		/**
		 * Get the member's full name.
		 *
		 * @return string
		 */
		public function get_fullname()
		{
			return $this->m_fullname;
		}

		/**
		 * Set the member's full name.
		 *
		 * @param string $newValue
		 */
		public function set_fullname($newValue)
		{
			$this->m_fullname = $newValue;
		}

		/**
		 * Get the user's registration date.
		 *
		 * @return unixdate
		 */
		public function get_registration_date()
		{
			return $this->m_registration_date;
		}

		/**
		 * Set the user's registration date.
		 *
		 * @param unixdate $newValue
		 */
		public function set_registration_date($newValue)
		{
			$this->m_registration_date = $newValue;
		}

		/**
		 * Get the date the user was last logged in.
		 *
		 * @return unixdate
		 */
		public function get_last_authorized()
		{
			return $this->m_last_authorized;
		}

		/**
		 * Set the date the user was last logged in.
		 *
		 * @param unixdate $newValue
		 */
		public function set_last_authorized($newValue)
		{
			$this->m_last_authorized = $newValue;
		}

		/**
		 * Get the number of groups the user belongs to.
		 *
		 * @return int
		 */
		public function get_group_count()
		{
			return count($this->m_groups);
		}

		/**
		 * Get the information on a group that the user belongs to, at the specified index.
		 *
		 * @param int $index
		 * @return Group
		 */
		public function get_group($index)
		{
			return $this->m_groups[$index];
		}

		/**
		 * Add the member to a group.
		 *
		 * @param Group $newGroup
		 * @return int The number of groups the user belongs to after adding the specified group.
		 */
		public function add_group($newGroup)
		{
			$this->m_groups[] = $newGroup;

			return count($this->m_groups);
		}

		/**
		 * Remove the user from the group at the specified index.
		 *
		 * @param int $index
		 */
		public function remove_group($index)
		{
			unset($this->m_groups[$index]);
		}

		/**
		 * Clear the list of groups that the user belongs to.
		 *
		 */
		public function clear_groups()
		{
			$this->m_groups = array();
		}

		/**
		 * Return the list of errors for the data currently set in the class.
		 *
		 * @return unknown
		 */
		public function get_errors()
		{
			return $this->m_errors;
		}

		/**
		 * Validate the contents of the class, and populate the errors array
		 * with the list of errors, if any. The list can then be retrieved using
		 * the get_errors() method.
		 *
		 * @return boolean True, if all of the data in the class is valid. Otherwise, false.
		 */
		public function validate()
		{
			$isValid = true;

			if(strlen($this->m_username) <= 0)
			{
				$this->m_errors[] = "Username is required.";
				$isValid = false;
			}

			if(strlen($this->m_email) <= 0)
			{
				$this->m_errors[] = "E-mail Address is required.";
				$isValid = false;
			}
			
			if ($this->m_id <= 0)
			{
				if(strlen($this->m_email) <= 0)
				{
					$this->m_errors[] = "Password is required.";
					$isValid = false;
				}
			}
						
			return $isValid;
		}
	}

	/**
	 * Contains a set of static methods used to populate either a single
	 * Member object, or an array of Member objects. Most of the methods
	 * populate the Member object from the database, but the object may
	 * also be populated from a web form.
	 *
	 */
	class MemberPopulator
	{
		/**
		 * Retrieve a list of all of the members in the database.
		 *
		 * @return array
		 */
		public static function PopulateAll()
		{
			return MemberPopulator::PopulateMultiple("1 = 1");
		}

		public static function PopulateByGroup($groupID)
		{
			$filter = "EXISTS( SELECT 1 FROM `zcm_membergroup` WHERE `zcm_membergroup`.`memberid` = `zcm_member`.`id` AND `zcm_membergroup`.`groupid` = '".
				Zymurgy::$db->escape_string($groupID).
				"')";

			return MemberPopulator::PopulateMultiple($filter);
		}

		/**
		 * Retrieve a list of all of the members in the database that match the
		 * specified criteria.
		 *
		 * @param string $criteria The list of criteria to match against, in SQL
		 * WHERE clause format.
		 * @return array
		 */
		public static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `id`, `username`, `email`, `password`, `fullname`, `regtime`, ".
				"`lastauth` FROM `zcm_member` WHERE $criteria";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve members: ".Zymurgy::$db->error().", $sql");

			$members = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$member = new Member();

				$member->set_id($row["id"]);
				$member->set_username($row["username"]);
				$member->set_email($row["email"]);
				$member->set_password($row["password"]);
				$member->set_fullname($row["fullname"]);
				$member->set_registration_date($row["regtime"]);
				$member->set_last_authorized($row["lastauth"]);

				$members[] = $member;
			}

			return $members;
		}

		/**
		 * Retrieve the information on a single member in the database, based on the
		 * member's ID.
		 *
		 * @param int $id
		 * @return Member
		 */
		public static function PopulateByID($id)
		{
			$sql = "SELECT `id`, `username`, `email`, `password`, `fullname`, `regtime`, ".
				"`lastauth` FROM `zcm_member` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"'";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve member: ".Zymurgy::$db->error().", $sql");

			$member = new Member();

			if(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$member->set_id($row["id"]);
				$member->set_username($row["username"]);
				$member->set_email($row["email"]);
				$member->set_password($row["password"]);
				$member->set_fullname($row["fullname"]);
				$member->set_registration_date($row["regtime"]);
				$member->set_last_authorized($row["lastauth"]);

				GroupPopulator::PopulateByMemberID($member);
			}

			return $member;
		}

		/**
		 * Retrieve the information on a single member in the database, based on
		 * the information in the POST request.
		 *
		 * @return Member
		 */
		public static function PopulateFromForm()
		{
			$member = new Member();

			$member->set_id($_POST["id"]);
			$member->set_username($_POST["username"]);
			$member->set_email($_POST["email"]);
			if (!empty($_POST['password']))
			{
				$member->set_passwordplain($_POST["password"]);
			}
			$member->set_fullname($_POST["fullname"]);

			return $member;
		}

		/**
		 * Save the member's information to the database.
		 *
		 * @param Member $member
		 */
		public static function SaveMember($member)
		{
			if($member->get_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_member` ( `username`, `email`, `password`, `fullname`, ".
					"`regtime` ) VALUES ( '".
					Zymurgy::$db->escape_string($member->get_username()).
					"', '".
					Zymurgy::$db->escape_string($member->get_email()).
					"', '".
					Zymurgy::$db->escape_string($member->get_password()).
					"', '".
					Zymurgy::$db->escape_string($member->get_fullname()).
					"', NOW())";

				Zymurgy::$db->query($sql)
					or die("Could not insert member record: ".Zymurgy::$db->error().", $sql");

				$member->set_id(
					Zymurgy::$db->insert_id());
			}
			else
			{
				$sql = "UPDATE `zcm_member` SET `username` = '".
					Zymurgy::$db->escape_string($member->get_username()).
					"', `email` = '".
					Zymurgy::$db->escape_string($member->get_email()).
					"', ";
				$passwd = $member->get_password();
				if (!empty($passwd)) 
				{
					$sql .= "`password` = '".
						Zymurgy::$db->escape_string($passwd)."', ";
				}
				$sql .=	"`fullname` = '".
					Zymurgy::$db->escape_string($member->get_fullname()).
					"' WHERE `id` = '".
					Zymurgy::$db->escape_string($member->get_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update member record: ".Zymurgy::$db->error().", $sql");
			}
		}

		/**
		 * Delete a single member from the database, based on the member's ID.
		 *
		 * @param int $id
		 */
		public static function DeleteMember($id)
		{
			$sql = "DELETE FROM `zcm_memberaudit` WHERE `member` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete member audit: ".Zymurgy::$db->error().", $sql");

			$sql = "DELETE FROM `zcm_membergroup` WHERE `memberid` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete member group associations: ".Zymurgy::$db->error().", $sql");

			$sql = "DELETE FROM `zcm_member` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete member record: ".Zymurgy::$db->error().", $sql");
		}

		/**
		 * Add a member to a group in the database, based on the member's ID
		 * and the group's ID.
		 *
		 * @param int $memberID
		 * @param int $groupID
		 */
		public static function AddMemberToGroup(
			$memberID,
			$groupID)
		{
			$sql = "INSERT IGNORE INTO `zcm_membergroup` ( `memberid`, `groupid` ) VALUES ( '".
				Zymurgy::$db->escape_string($memberID).
				"', '".
				Zymurgy::$db->escape_string($groupID).
				"')";

			Zymurgy::$db->query($sql)
				or die("Could not add member to group: ".Zymurgy::$db->error().", $sql");
		}

		/**
		 * Remove a member from a group in the database, based on the member's ID
		 * and the group's ID.
		 *
		 * @param int $memberID
		 * @param int $groupID
		 */
		public static function DeleteMemberFromGroup(
			$memberID,
			$groupID)
		{
			$sql = "DELETE FROM `zcm_membergroup` WHERE `memberid` = '".
				Zymurgy::$db->escape_string($memberID).
				"' AND `groupid` = '".
				Zymurgy::$db->escape_string($groupID).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete member from group: ".Zymurgy::$db->error().", $sql");
		}
	}

	/**
	 * Contains the information and validation routines for a membership group.
	 *
	 */
	class Group
	{
		private $m_id;
		private $m_name;
		private $m_builtin;

		private $m_errors = array();

		/**
		 * Get the membership group's ID, as stored in the database.
		 *
		 * @return int The ID
		 */
		public function get_id()
		{
			return $this->m_id;
		}

		/**
		 * Set the membership group's ID, as stored in the database.
		 *
		 * @param int $newValue
		 */
		public function set_id($newValue)
		{
			$this->m_id = $newValue;
		}

		/**
		 * Get the membership group's name.
		 *
		 * @return string
		 */
		public function get_name()
		{
			return $this->m_name;
		}

		/**
		 * Set the membership group's name.
		 *
		 * @param string $newValue
		 */
		public function set_name($newValue)
		{
			$this->m_name = $newValue;
		}

		/**
		 * Get the membership group's built-in flag. Built-in groups may
		 * not be modified using the Zymurgy:CM web front-end.
		 *
		 * @return boolean
		 */
		public function get_builtin()
		{
			return $this->m_builtin;
		}

		/**
		 * Set the membership group's built-in flag.
		 *
		 * @param boolean $newValue
		 */
		public function set_builtin($newValue)
		{
			// echo("Setting builtin to: $newValue (".intval($newValue).")<br>");

			$this->m_builtin = (boolean) $newValue; // intval($newValue) > 0;
		}

		/**
		 * Return the list of errors for the data currently set in the class.
		 *
		 * @return unknown
		 */
		public function get_errors()
		{
			return $this->m_errors;
		}

		/**
		 * Validate the contents of the class, and populate the errors array
		 * with the list of errors, if any. The list can then be retrieved using
		 * the get_errors() method.
		 *
		 * @return boolean True, if all of the data in the class is valid. Otherwise, false.
		 */
		public function validate()
		{
			$isValid = true;

			if(strlen($this->m_name) <= 0)
			{
				$this->m_errors[] = "Name is required.";
				$isValid = false;
			}

			return $isValid;
		}
	}

	/**
	 * Contains a set of static methods used to populate either a single
	 * Group object, or an array of Group objects. Most of the methods
	 * populate the Group object from the database, but the object may
	 * also be populated from a web form.
	 *
	 */
	class GroupPopulator
	{
		/**
		 * Retrieve a list of all of the membership groups in the database.
		 *
		 * @return array
		 */
		public static function PopulateAll()
		{
			return GroupPopulator::PopulateMultiple("1 = 1");
		}

		/**
		 * Retrieve a list of all of the membership groups the specified
		 * member does NOT belong to. This is typically when presenting the
		 * admin with a list of the groups the member can be added to.
		 *
		 * @param int $memberID
		 * @return array
		 */
		public static function PopulateAllMemberNotIn($memberID)
		{
			return GroupPopulator::PopulateMultiple(
				"NOT EXISTS(SELECT 1 FROM `zcm_membergroup` WHERE `memberid` = '".
				Zymurgy::$db->escape_string($memberID).
				"' AND `zcm_membergroup`.`groupid` = `zcm_groups`.`id`)");
		}

		/**
		 * Retrieve a list of all of the membership group the specified
		 * member belongs to.
		 *
		 * @param Member $member
		 */
		public static function PopulateByMemberID(&$member)
		{
			$groups = GroupPopulator::PopulateMultiple(
				"EXISTS(SELECT 1 FROM `zcm_membergroup` WHERE `memberid` = '".
				Zymurgy::$db->escape_string($member->get_id()).
				"' AND `zcm_membergroup`.`groupid` = `zcm_groups`.`id`)");

			foreach($groups as $group)
			{
				$member->add_group($group);
			}
		}

		/**
		 * Retrieve a list of all of membership groups based on the specified
		 * criteria.
		 *
		 * @param string $criteria The criteria to match against in SQL WHERE
		 * clause format.
		 * @return array
		 */
		public static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `id`, `name`, `builtin` FROM `zcm_groups` WHERE $criteria";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve list of groups: ".Zymurgy::$db->error().", $sql");

			$groups = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$group = new Group();

				$group->set_id($row["id"]);
				$group->set_name($row["name"]);
				$group->set_builtin($row["builtin"]);

				$groups[] = $group;
			}

			return $groups;
		}

		/**
		 * Retrieve the information on a single group based on its ID in the
		 * database.
		 *
		 * @param int $id
		 * @return Group
		 */
		public static function PopulateByID($id)
		{
			$sql = "SELECT `id`, `name`, `builtin` FROM `zcm_groups` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve group: ".Zymurgy::$db->error().", $sql");

			$group = new Group();

			if(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$group->set_id($row["id"]);
				$group->set_name($row["name"]);
				$group->set_builtin($row["builtin"]);
			}

			return $group;
		}

		/**
		 * Retrieve the information on a single group based on the information
		 * in a form POST.
		 *
		 * @return Group
		 */
		public static function PopulateFromForm()
		{
			$group = new Group();

			$group->set_id($_POST["id"]);
			$group->set_name($_POST["name"]);

			return $group;
		}

		/**
		 * Save the spceified group to the database.
		 *
		 * @param Group $group
		 */
		public static function SaveGroup($group)
		{
			// ZK: Note that the Zymurgy:CM GUI does not allow users to
			// modify built-in groups. Therefore, the built-in field is
			// not sent as part of the insert/update query.

			if($group->get_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_groups` ( `name` ) VALUES ( '".
					Zymurgy::$db->escape_string($group->get_name()).
					"' )";

				Zymurgy::$db->query($sql)
					or die("Could not insert group record: ".Zymurgy::$db->error().", $sql");

				$group->set_id(
					Zymurgy::$db->insert_id());
			}
			else
			{
				$sql = "UPDATE `zcm_groups` SET `name` = '".
					Zymurgy::$db->escape_string($group->get_name()).
					"' WHERE `id` = '".
					Zymurgy::$db->escape_string($group->get_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update group record: ".Zymurgy::$db->error().", $sql");
			}
		}

		/**
		 * Remove a group from the database based on its ID.
		 *
		 * @param int $id
		 */
		public static function DeleteGroup($id)
		{
			$sql = "DELETE FROM `zcm_membergroup` WHERE `groupid` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete member group associations: ".Zymurgy::$db->error().", $sql");

			// ZK: Note that the Zymurgy:CM GUI does not allow users to
			// modify built-in groups. The DELETE query has the extra
			// parameter to make sure that the code cannot delete built-in
			// groups by mistake.

			$sql = "DELETE FROM `zcm_groups` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"' AND NOT `builtin` = 1";

			Zymurgy::$db->query($sql)
				or die("Could not delete group record: ".Zymurgy::$db->error().", $sql");
		}
	}

	/**
	 * Contains methods used to render Member management screens in the
	 * Zymurgy:CM back-end.
	 *
	 */
	class MemberView
	{
		/**
		 * Display a list of members, with links to the edit and delete screens.
		 *
		 * @param array $members
		 */
		public static function DisplayList($members, $groups, $groupID)
		{
			$breadcrumbTrail = "Members";
			$wikiArticleName = "Members";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			$groupList = array();

			foreach($groups as $group)
			{
				$groupList[] = "<option value=\"".
					$group->get_id().
					"\"".
					($groupID == $group->get_id() ? " SELECTED" : "").
					">".
					$group->get_name().
					"</option>";
			}

			$groupListText = implode("\n", $groupList);

			echo <<<BLOCK
				<form name="frmFilter" method="POST">
					<input type="hidden" name="action" value="list_members">
					<table>
						<tr>
							<td>Only show members in group:</td>
							<td>
								<select name="group">
									<option value="">(all)</option>
									{$groupListText}
								</select>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><input type="submit" value="Filter">
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
					</table>
				</form>
BLOCK;

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Username</td>");
			echo("<td>E-mail Address</td>");
			echo("<td>Full Name</td>");
			echo("<td>Registration Date</td>");
			echo("<td>Last Authorized</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($members as $member)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><a href=\"editmember.php?action=edit_member&amp;id=".
					$member->get_id().
					"\">".
					$member->get_username().
					"</td>");
				echo("<td><a href=\"editmember.php?action=edit_member&amp;id=".
					$member->get_id().
					"\">".
					$member->get_email().
					"</td>");
				echo("<td>".
					$member->get_fullname().
					"</td>");
				echo("<td>".
					$member->get_registration_date().
					"</td>");
				echo("<td>".
					$member->get_last_authorized().
					"</td>");
				echo("<td><a href=\"editmember.php?action=delete_member&amp;id=".
					$member->get_id().
					"\">Delete</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"6\"><a style=\"color: white;\" href=\"editmember.php?action=add_member\">".
				"Add Member".
				"</a></td>");

			echo("</table>");

			include("footer.php");
		}

		/**
		 * Display the edit form for the specified member. The same form is used
		 * for adding and editing members.
		 *
		 * @param Member $member
		 * @param String $action The action to send to the controller when the form
		 * is submitted. Valid values are "act_add_member" and "act_edit_member".
		 */
		public static function DisplayEditForm(
			$member,
			$action)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_members\">".
				"Members".
				"</a> &gt; ".
				"Edit Member";
			$wikiArticleName = "Members#Editing_Members";

			include("header.php");
			include('datagrid.php');

			// echo("<pre>");
			// print_r($member);
			// echo("</pre>");

			DumpDataGridCSS();

			$widget = new InputWidget();

			$errors = $member->get_errors();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">\n");
				echo("<div style=\"color: red\">Error</div>\n");
				echo("<ul>\n");

				foreach($errors as $error)
				{
					echo("<li>$error</li>\n");
				}

				echo("</ul>\n");
				echo("</div>\n");
			}

			echo("<form name=\"frm\" action=\"editmember.php\" method=\"POST\" enctype=\"multipart/form-data\">\n");
			echo("<input type=\"hidden\" name=\"action\" value=\"$action\">\n");
			echo("<input type=\"hidden\" name=\"id\" value=\"".$member->get_id()."\">\n");
			echo("<input type=\"hidden\" name=\"regtime\" value=\"".$member->get_registration_date()."\">\n");
			echo("<input type=\"hidden\" name=\"lastauth\" value=\"".$member->get_last_authorized()."\">\n");

			echo("<table>");

			echo("<tr>\n");
			echo("<td>Username:</td>\n");
			echo("<td>");
			$widget->Render("input.20.80", "username", $member->get_username());
			echo("</td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			echo("<td>E-mail Address:</td>\n");
			echo("<td>");
			$widget->Render("input.30.80", "email", $member->get_email());
			echo("</td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			echo("<td>Password:</td>\n");
			echo("<td>");
			$widget->Render("password.20.32", "password", '');
			echo("</td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			echo("<td>Full Name:</td>\n");
			echo("<td>");
			$widget->Render("input.30.100", "fullname", $member->get_fullname());
			echo("</td>\n");
			echo("</tr>\n");


			if($action == "act_edit_member")
			{
				echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

				echo("<tr>\n");
				echo("<td>Registered:</td>\n");
				echo("<td>".$member->get_registration_date()."</td>\n");
				echo("</tr>\n");

				echo("<tr>\n");
				echo("<td>Last Authorized:</td>\n");
				echo("<td>".$member->get_last_authorized()."</td>\n");
				echo("</tr>\n");

				echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

				echo("<tr>\n");
				echo("<td valign=\"top\">Groups:</td>\n");
				echo("<td>\n");

				echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");

				echo("<tr class=\"DataGridHeader\">");
				echo("<td>Name</td>");
				echo("<td>&nbsp;</td>");
				echo("</tr>");

				$groupCount = $member->get_group_count();

				for($groupIndex = 0; $groupIndex < $groupCount; $groupIndex++)
				{
					$group = $member->get_group($groupIndex);

					echo("<tr class=\"".($groupIndex % 2 ? "DataGridRowAlternate" : "DataGridRow")."\">\n");
					echo("<td>".
						$group->get_name().
						"</td>");
					echo("<td><a href=\"editmember.php?action=delete_member_group&amp;memberid=".
						$member->get_id().
						"&amp;groupid=".
						$group->get_id().
						"\">Delete</a></td>");
					echo("</tr>\n");
				}

				echo("<tr class=\"DataGridHeader\">");
				echo("<td colspan=\"2\"><a style=\"color: white;\" ".
					"href=\"editmember.php?action=add_member_group&amp;id=".
					$member->get_id().
					"\">".
					"Add Member to Membership Group".
					"</a></td>");

				echo("</table>");

				echo("</td>\n");
				echo("</tr>\n");
			}

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>&nbsp;</td>");
			echo("<td><input type=\"submit\" value=\"".
				"Save".
				"\"></td>");
			echo("</tr>");

			if($action == "act_edit_member")
			{
				echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

				echo("<tr>");
				echo("<td>&nbsp;</td>");
				echo("<td>");

				MemberView::DisplayCommands($member);

				echo("</td>\n");
				echo("</tr>\n");
			}

			echo("</table>\n");
			echo("</form>\n");

			include("footer.php");
		}

		/**
		 * Display the confirmation form for deleting the specified member.
		 *
		 * @param Member $member
		 */
		static function DisplayDeleteForm($member)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_members\">".
				"Members".
				"</a> &gt; ".
				"Delete Member";
			$wikiArticleName = "Members#Deleting_Members";

			include("header.php");

			echo("<form name=\"frm\" action=\"editmember.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_member\">");
			echo("<input type=\"hidden\" name=\"id\" value=\"".
				$member->get_id()."\">");

			echo("<p>".
				"Are you sure you want to delete this member:".
				"</p>");
			echo("<p>".
				"E-mail Address:".
				" ".
				$member->get_email().
				"</p>");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"".
				"Yes".
				"\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"".
				"No".
				"\" onclick=\"window.location.href='editmember.php?action=list_members';\">");
			echo("</p>");

			echo("</form>");

			include("footer.php");
		}

		/**
		 * Display the form used to assign groups to a specified member.
		 *
		 * @param array $groups
		 * @param int $memberID
		 * @param array $errors
		 */
		public static function DisplayGroups($groups, $memberID, $errors)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_members\">".
				"Members".
				"</a> &gt; <a href=\"editmember.php?action=edit_member&amp;id=".
				$memberID.
				"\">".
				"Edit Member".
				"</a> &gt; ".
				"Add Member to Membership Group";
			$wikiArticleName = "Members#Adding_Members_to_Membership_Groups";

			include("header.php");
			include('datagrid.php');

			// echo("<pre>");
			// print_r($member);
			// echo("</pre>");

			DumpDataGridCSS();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">\n");
				echo("<div style=\"color: red\">Error</div>\n");
				echo("<ul>\n");

				foreach($errors as $error)
				{
					echo("<li>$error</li>\n");
				}

				echo("</ul>\n");
				echo("</div>\n");
			}

			echo("<form name=\"frm\" action=\"editmember.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_add_member_group\">");
			echo("<input type=\"hidden\" name=\"id\" value=\"".
				$memberID."\">");

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");

			echo("<tr class=\"DataGridHeader\">");
			echo("<td>&nbsp;</td>");
			echo("<td>Name</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($groups as $group)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">\n");
				echo("<td><input type=\"radio\" name=\"groupid\" id=\"group".
					$group->get_id().
					"\" value=\"".
					$group->get_id().
					"\"></td>\n");
				echo("<td><label for=\"group".
					$group->get_id().
					"\">".
					$group->get_name().
					"</label></td>\n");
				echo("</tr>\n");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"2\">&nbsp;</td>");
			echo("</tr>");

			echo("</table>\n");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"".
				"Save".
				"\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"".
				"Cancel".
				"\" onclick=\"window.location.href='editmember.php?action=edit_member&amp;id=".
				$memberID.
				"';\">");
			echo("</p>");

			echo("</form>\n");

			include("footer.php");
		}

		/**
		 * Display the Commands menu for the Edit Member screen.
		 *
		 * @param Member $member The member to display the commands menu for
		 */
		public static function DisplayCommands($member)
		{
			return; //No more send password - need password reset email feature instead.
			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");

			echo("<tr class=\"DataGridHeader\"><td>Commands</td></tr>\n");
			echo("<tr><td><a href=\"editmember.php?action=send_password&amp;id=".
				$member->get_id().
				"\">Send Password</a></td></tr>");

			echo("</table>\n");
		}

		/**
		 * E-mail the member's password to them.
		 *
		 * @deprecated
		 * @param Member $member The member to send the password to
		 */
		public static function SendPassword($member)
		{
			$sql = "SELECT `password` FROM `zcm_member` WHERE `email` = '".
				Zymurgy::$db->escape_string($member->get_email()).
				"'";
			$password = Zymurgy::$db->get($sql);

			if(strlen($password) > 0)
			{
				$body = "Site: {0}\n".
					"Username: {1}\n".
					"Password: {2}\n";

				$body = str_replace("{0}", Zymurgy::$config["sitehome"], $body);
				$body = str_replace("{1}", $member->get_username(), $body);
				$body = str_replace("{2}", $password, $body);

				mail(
					$member->get_email(),
					"Forgot password request for ".Zymurgy::$config["sitehome"],
					$body,
					"From: webmaster@".str_replace("www.", "", Zymurgy::$config["sitehome"]));
			}
			else
			{
				die("Password could not be retrieved for ".$member->get_email());
			}
		}

		/**
		 * Display the "Send Password" message to the user that clicked on the
		 * "Send Password" menu command in Zymurgy:CM.
		 *
		 * @deprecated
		 * @param Member $member The member the password was sent to
		 */
		public static function DisplayPasswordSentMessage($member)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_members\">".
				"Members".
				"</a> &gt; <a href=\"editmember.php?action=edit_member&amp;id=".
				$member->get_id().
				"\">".
				"Edit Member".
				"</a> &gt; ".
				"Send Password";

			include("header.php");

			echo("<p>The password for this member has been sent to ".
				$member->get_email().
				"</p>");

			include("footer.php");
		}
	}

	/**
	 * Contains methods used to render Membership Group management screens in the
	 * Zymurgy:CM back-end.
	 *
	 */
	class GroupView
	{
		/**
		 * Display the list of membership groups, with links to the edit and
		 * delete screens.
		 *
		 * @param array $groups
		 */
		public static function DisplayList($groups)
		{
			$breadcrumbTrail = "Membership Groups";
			$wikiArticleName = "Membership_Groups";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Group Name</td>");
			echo("<td>Built-in</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($groups as $group)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				if($group->get_builtin())
				{
					echo("<td>".
						$group->get_name().
						"</td>");
					echo("<td>Yes</td>");
					echo("<td>&nbsp;</td>");
				}
				else
				{
					echo("<td><a href=\"editmember.php?action=edit_group&amp;id=".
						$group->get_id().
						"\">".
						$group->get_name().
						"</td>");
					echo("<td>&nbsp;</td>");
					echo("<td><a href=\"editmember.php?action=delete_group&amp;id=".
						$group->get_id().
						"\">Delete</a></td>");
				}

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"3\"><a style=\"color: white;\" href=\"editmember.php?action=add_group\">".
				"Add Membership Group".
				"</a></td>");

			echo("</table>");

			include("footer.php");
		}

		/**
		 * Display the edit form for the specified group. The same form is used
		 * for adding and editing groups.
		 *
		 * @param Group $group
		 * @param string $action The action to send to the controller when the
		 * form is submitted. Valid values are "act_add_group" and "act_edit_group".
		 */
		public function DisplayEditForm($group, $action)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_groups\">".
				"Membership Groups".
				"</a> &gt; ".
				"Edit Membership Group";
			$wikiArticleName = "Membership_Groups#Editing_Membership_Groups";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			$widget = new InputWidget();

			$errors = $group->get_errors();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">\n");
				echo("<div style=\"color: red\">Error</div>\n");
				echo("<ul>\n");

				foreach($errors as $error)
				{
					echo("<li>$error</li>\n");
				}

				echo("</ul>\n");
				echo("</div>\n");
			}

			echo("<form name=\"frm\" action=\"editmember.php\" method=\"POST\" enctype=\"multipart/form-data\">\n");
			echo("<input type=\"hidden\" name=\"action\" value=\"$action\">\n");
			echo("<input type=\"hidden\" name=\"id\" value=\"".$group->get_id()."\">\n");

			echo("<table>");

			echo("<tr>\n");
			echo("<td>Name:</td>\n");
			echo("<td>");
			$widget->Render("input.30.100", "name", $group->get_name());
			echo("</td>\n");
			echo("</tr>\n");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>&nbsp;</td>");
			echo("<td><input type=\"submit\" value=\"".
				"Save".
				"\"></td>");
			echo("</tr>");

			echo("</table>\n");
			echo("</form>\n");

			include("footer.php");
		}

		/**
		 * Display the Delete form for the specified membership group.
		 *
		 * @param Group $group
		 */
		static function DisplayDeleteForm($group)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_groups\">".
				"Membership Groups".
				"</a> &gt; ".
				"Delete Membership Group";
			$wikiArticleName = "Membership_Groups#Deleting_Membership_Groups";

			include("header.php");

			echo("<form name=\"frm\" action=\"editmember.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_group\">");
			echo("<input type=\"hidden\" name=\"id\" value=\"".
				$group->get_id()."\">");

			echo("<p>".
				"Are you sure you want to delete this membership group:".
				"</p>");
			echo("<p>".
				"Name:".
				" ".
				$group->get_name().
				"</p>");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"".
				"Yes".
				"\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"".
				"No".
				"\" onclick=\"window.location.href='editmember.php?action=list_groups';\">");
			echo("</p>");

			echo("</form>");

			include("footer.php");
		}
	}

	/**
	 * HTTP Controller class.
	 *
	 * This class is responsible for instantiating member methods based on
	 * the expected HTTP GET and POST variables, and either passing these members into
	 * the View classes for display, or forwarding the requests to the appropriate URL.
	 */
	class MembershipController
	{
		/**
		 * Main entry method.
		 *
		 * @param string $action
		 */
		public function Execute($action)
		{
			if(method_exists($this, $action))
			{
				call_user_func(array($this,$action));
			}
			else
			{
				die("Unsupported action ".$action);
			}
		}

		/**
		 * Display a list of all of the members in the system.
		 *
		 */
		private function list_members()
		{
			$members = array();

			if(isset($_POST["group"]) && $_POST["group"] > 0)
			{
				$groupID = $_POST["group"];
				$members = MemberPopulator::PopulateByGroup($groupID);
			}
			else
			{
				$groupID = 0;
				$members = MemberPopulator::PopulateAll();
			}

			$groups = GroupPopulator::PopulateAll();

			MemberView::DisplayList(
				$members,
				$groups,
				$groupID);
		}

		/**
		 * Display the add member screen.
		 *
		 */
		private function add_member()
		{
			$member = new Member();

			MemberView::DisplayEditForm($member, "act_add_member");
		}

		/**
		 * Display the edit member screen for the specified member.
		 *
		 */
		private function edit_member()
		{
			$member = MemberPopulator::PopulateByID($_GET["id"]);

			MemberView::DisplayEditForm($member, "act_edit_member");
		}

		/**
		 * Process the submitted add member screen.
		 *
		 */
		private function act_add_member()
		{
			$this->update_member("act_add_member");
		}

		/**
		 * Process the submitted edit member screen.
		 *
		 */
		private function act_edit_member()
		{
			$this->update_member("act_edit_member");
		}

		/**
		 * Insert/update the member. This is used by the act_add_member and
		 * act_edit_member methods, and is not to be called directly.
		 *
		 * @param string $action
		 */
		private function update_member($action)
		{
			if($action == null)
			{
				die("update_member may not be called via the controller action");
			}

			$member = MemberPopulator::PopulateFromForm();

			if(!$member->validate($action))
			{
				MemberView::DisplayEditForm($member, $action);
			}
			else
			{
				if(MemberPopulator::SaveMember($member))
				{
					MemberView::DisplayEditForm($member, $action);
				}
				else
				{
					header("Location: editmember.php?action=list_members");
				}
			}
		}

		/**
		 * Display the Delete Member confirmation screen.
		 *
		 */
		private function delete_member()
		{
			$member = MemberPopulator::PopulateByID($_GET["id"]);

			MemberView::DisplayDeleteForm($member);
		}

		/**
		 * Delete the member and forward to the updated list.
		 *
		 */
		private function act_delete_member()
		{
			MemberPopulator::DeleteMember($_POST["id"]);

			header("Location: editmember.php?action=list_members");
		}

		/**
		 * Display the screen to assign members to membership groups.
		 *
		 */
		private function add_member_group()
		{
			$groups = GroupPopulator::PopulateAllMemberNotIn($_GET["id"]);

			MemberView::DisplayGroups($groups, $_GET["id"], array());
		}

		/**
		 * Add the member to a group.
		 *
		 */
		private function act_add_member_group()
		{
			if(!isset($_POST["groupid"]))
			{
				$groups = GroupPopulator::PopulateAllMemberNotIn($_POST["id"]);

				MemberView::DisplayGroups(
					$groups,
					$_POST["id"],
					array("A membership group must be selected."));
			}
			else
			{
				MemberPopulator::AddMemberToGroup(
					$_POST["id"],
					$_POST["groupid"]);

				header("Location: editmember.php?action=edit_member&id=".
					$_POST["id"]);
			}
		}

		/**
		 * Remove the member from a group.
		 *
		 */
		private function delete_member_group()
		{
			MemberPopulator::DeleteMemberFromGroup(
				$_GET["memberid"],
				$_GET["groupid"]);

				header("Location: editmember.php?action=edit_member&id=".
					$_GET["memberid"]);
		}

		/**
		 * Send the member's password to them.
		 * @deprecated
		 */
		private function send_password()
		{
			$member = MemberPopulator::PopulateByID($_GET["id"]);

			MemberView::SendPassword($member);
			MemberView::DisplayPasswordSentMessage($member);
		}

		/**
		 * Display a list of all of the membership groups.
		 *
		 */
		private function list_groups()
		{
			$groups = GroupPopulator::PopulateAll();

			GroupView::DisplayList($groups);
		}

		/**
		 * Display the screen to add a membership group.
		 *
		 */
		private function add_group()
		{
			$group = new Group();

			GroupView::DisplayEditForm($group, "act_add_group");
		}

		/**
		 * Display a screen to edit a membership group.
		 *
		 */
		private function edit_group()
		{
			$group = GroupPopulator::PopulateByID($_GET["id"]);

			GroupView::DisplayEditForm($group, "act_edit_group");
		}

		/**
		 * Add the membership group.
		 *
		 */
		private function act_add_group()
		{
			$this->update_group("act_add_group");
		}

		/**
		 * Update the membership group.
		 *
		 */
		private function act_edit_group()
		{
			$this->update_group("act_edit_group");
		}

		/**
		 * Insert/update the membership group. This is used by the act_add_group and
		 * act_edit_group methods, and is not to be called directly.
		 *
		 * @param string $action
		 */
		private function update_group($action)
		{
			if($action == null)
			{
				die("update_group may not be called via the controller action");
			}

			$group = GroupPopulator::PopulateFromForm();

			if(!$group->validate($action))
			{
				GroupView::DisplayEditForm($group, $action);
			}
			else
			{
				if(GroupPopulator::SaveGroup($group))
				{
					GroupView::DisplayEditForm($group, $action);
				}
				else
				{
					header("Location: editmember.php?action=list_groups");
				}
			}
		}

		/**
		 * Display the delete group confirmation screen.
		 *
		 */
		private function delete_group()
		{
			$group = GroupPopulator::PopulateByID($_GET["id"]);

			GroupView::DisplayDeleteForm($group);
		}

		/**
		 * Delete the membership group.
		 *
		 */
		private function act_delete_group()
		{
			GroupPopulator::DeleteGroup($_POST["id"]);

			header("Location: editmember.php?action=list_groups");
		}
	}
?>
