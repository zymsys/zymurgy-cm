<?php
	class Member
	{
		private $m_id;
		private $m_email;
		private $m_password;
		private $m_registration_date;
		private $m_last_authorized;

		private $m_groups = array();

		private $m_errors = array();

		public function get_id()
		{
			return $this->m_id;
		}

		public function set_id($newValue)
		{
			$this->m_id = $newValue;
		}

		public function get_email()
		{
			return $this->m_email;
		}

		public function set_email($newValue)
		{
			$this->m_email = $newValue;
		}

		public function get_password()
		{
			return $this->m_password;
		}

		public function set_password($newValue)
		{
			$this->m_password = $newValue;
		}

		public function get_registration_date()
		{
			return $this->m_registration_date;
		}

		public function set_registration_date($newValue)
		{
			$this->m_registration_date = $newValue;
		}

		public function get_last_authorized()
		{
			return $this->m_last_authorized;
		}

		public function set_last_authorized($newValue)
		{
			$this->m_last_authorized = $newValue;
		}

		public function get_group_count()
		{
			return count($this->m_groups);
		}

		public function get_group($index)
		{
			return $this->m_groups[$index];
		}

		public function add_group($newGroup)
		{
			$this->m_groups[] = $newGroup;

			return count($this->m_groups);
		}

		public function remove_group($index)
		{
			unset($this->m_groups[$index]);
		}

		public function clear_groups()
		{
			$this->m_groups = array();
		}

		public function get_errors()
		{
			return $this->m_errors;
		}

		public function validate()
		{
			$isValid = true;

			if(strlen($this->m_email) <= 0)
			{
				$this->m_errors[] = "E-mail Address is required.";
				$isValid = false;
			}

			if(strlen($this->m_password) <= 0)
			{
				$this->m_errors[] = "Password is required.";
				$isValid = false;
			}

			return $isValid;
		}
	}

	class MemberPopulator
	{
		public static function PopulateAll()
		{
			return MemberPopulator::PopulateMultiple("1 = 1");
		}

		public static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `id`, `email`, `password`, `regtime`, `lastauth` ".
				"FROM `zcm_member` WHERE $criteria";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve members: ".Zymurgy::$db->error().", $sql");

			$members = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$member = new Member();

				$member->set_id($row["id"]);
				$member->set_email($row["email"]);
				$member->set_password($row["password"]);
				$member->set_registration_date($row["regtime"]);
				$member->set_last_authorized($row["lastauth"]);

				$members[] = $member;
			}

			return $members;
		}

		public static function PopulateByID($id)
		{
			$sql = "SELECT `id`, `email`, `password`, `regtime`, `lastauth` ".
				"FROM `zcm_member` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"'";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve member: ".Zymurgy::$db->error().", $sql");

			$member = new Member();

			if(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$member->set_id($row["id"]);
				$member->set_email($row["email"]);
				$member->set_password($row["password"]);
				$member->set_registration_date($row["regtime"]);
				$member->set_last_authorized($row["lastauth"]);

				GroupPopulator::PopulateByMemberID($member);
			}

			return $member;
		}

		public static function PopulateFromForm()
		{
			$member = new Member();

			$member->set_id($_POST["id"]);
			$member->set_email($_POST["email"]);
			$member->set_password($_POST["password"]);

			return $member;
		}

		public static function SaveMember($member)
		{
			if($member->get_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_member` ( `email`, `password`, `regtime` ) VALUES ( '".
					Zymurgy::$db->escape_string($member->get_email()).
					"', '".
					Zymurgy::$db->escape_string($member->get_password()).
					"', NOW())";

				Zymurgy::$db->query($sql)
					or die("Could not insert member record: ".Zymurgy::$db->error().", $sql");

				$member->set_id(
					Zymurgy::$db->insert_id());
			}
			else
			{
				$sql = "UPDATE `zcm_member` SET `email` = '".
					Zymurgy::$db->escape_string($member->get_email()).
					"', `password` = '".
					Zymurgy::$db->escape_string($member->get_password()).
					"' WHERE `id` = '".
					Zymurgy::$db->escape_string($member->get_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update member record: ".Zymurgy::$db->error().", $sql");
			}
		}

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

	class Group
	{
		private $m_id;
		private $m_name;

		private $m_errors = array();

		public function get_id()
		{
			return $this->m_id;
		}

		public function set_id($newValue)
		{
			$this->m_id = $newValue;
		}

		public function get_name()
		{
			return $this->m_name;
		}

		public function set_name($newValue)
		{
			$this->m_name = $newValue;
		}

		public function get_errors()
		{
			return $this->m_errors;
		}

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

	class GroupPopulator
	{
		public static function PopulateAll()
		{
			return GroupPopulator::PopulateMultiple("1 = 1");
		}

		public static function PopulateAllMemberNotIn($memberID)
		{
			return GroupPopulator::PopulateMultiple(
				"NOT EXISTS(SELECT 1 FROM `zcm_membergroup` WHERE `memberid` = '".
				Zymurgy::$db->escape_string($memberID).
				"' AND `zcm_membergroup`.`groupid` = `zcm_groups`.`id`)");
		}

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

		public static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `id`, `name` FROM `zcm_groups` WHERE $criteria";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve list of groups: ".Zymurgy::$db->error().", $sql");

			$groups = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$group = new Group();

				$group->set_id($row["id"]);
				$group->set_name($row["name"]);

				$groups[] = $group;
			}

			return $groups;
		}

		public static function PopulateByID($id)
		{
			$sql = "SELECT `id`, `name` FROM `zcm_groups` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve group: ".Zymurgy::$db->error().", $sql");

			$group = new Group();

			if(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$group->set_id($row["id"]);
				$group->set_name($row["name"]);
			}

			return $group;
		}

		public static function PopulateFromForm()
		{
			$group = new Group();

			$group->set_id($_POST["id"]);
			$group->set_name($_POST["name"]);

			return $group;
		}

		public static function SaveGroup($group)
		{
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

		public static function DeleteGroup($id)
		{
			$sql = "DELETE FROM `zcm_membergroup` WHERE `groupid` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete member group associations: ".Zymurgy::$db->error().", $sql");

			$sql = "DELETE FROM `zcm_groups` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete group record: ".Zymurgy::$db->error().", $sql");
		}
	}

	class MemberView
	{
		public static function DisplayList($members)
		{
			$breadcrumbTrail = "Members";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>E-mail Address</td>");
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
					$member->get_email().
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
			echo("<td colspan=\"4\"><a style=\"color: white;\" href=\"editmember.php?action=add_member\">".
				"Add Member".
				"</a></td>");

			echo("</table>");

			include("footer.php");
		}

		public static function DisplayEditForm(
			$member,
			$action)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_members\">".
				"Members".
				"</a> &gt; ".
				"Edit Member";

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
			echo("<td>E-mail Address:</td>\n");
			echo("<td>");
			$widget->Render("input.30.100", "email", $member->get_email());
			echo("</td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			echo("<td>Password:</td>\n");
			echo("<td>");
			$widget->Render("password.20.50", "password", $member->get_password());
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

			echo("</table>\n");
			echo("</form>\n");

			include("footer.php");
		}

		static function DisplayDeleteForm($member)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_members\">".
				"Members".
				"</a> &gt; ".
				"Delete Member";

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
	}

	class GroupView
	{
		public static function DisplayList($groups)
		{
			$breadcrumbTrail = "Membership Groups";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Group Name</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($groups as $group)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><a href=\"editmember.php?action=edit_group&amp;id=".
					$group->get_id().
					"\">".
					$group->get_name().
					"</td>");
				echo("<td><a href=\"editmember.php?action=delete_group&amp;id=".
					$group->get_id().
					"\">Delete</a></td>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"2\"><a style=\"color: white;\" href=\"editmember.php?action=add_group\">".
				"Add Membership Group".
				"</a></td>");

			echo("</table>");

			include("footer.php");
		}

		public function DisplayEditForm($group, $action)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_groups\">".
				"Membership Groups".
				"</a> &gt; ".
				"Edit Membership Group";

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

		static function DisplayDeleteForm($group)
		{
			$breadcrumbTrail = "<a href=\"editmember.php?action=list_groups\">".
				"Membership Groups".
				"</a> &gt; ".
				"Delete Membership Group";

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

	class MembershipController
	{
		public function Execute($action)
		{
			if(method_exists($this, $action))
			{
				call_user_method($action, $this);
			}
			else
			{
				die("Unsupported action ".$action);
			}
		}

		public function list_members()
		{
			$members = MemberPopulator::PopulateAll();

			MemberView::DisplayList($members);
		}

		public function add_member()
		{
			$member = new Member();

			MemberView::DisplayEditForm($member, "act_add_member");
		}

		public function edit_member()
		{
			$member = MemberPopulator::PopulateByID($_GET["id"]);

			MemberView::DisplayEditForm($member, "act_edit_member");
		}

		public function act_add_member()
		{
			$this->update_member("act_add_member");
		}

		public function act_edit_member()
		{
			$this->update_member("act_edit_member");
		}

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

		private function delete_member()
		{
			$member = MemberPopulator::PopulateByID($_GET["id"]);

			MemberView::DisplayDeleteForm($member);
		}

		private function act_delete_member()
		{
			MemberPopulator::DeleteMember($_POST["id"]);

			header("Location: editmember.php?action=list_members");
		}

		private function add_member_group()
		{
			$groups = GroupPopulator::PopulateAllMemberNotIn($_GET["id"]);

			MemberView::DisplayGroups($groups, $_GET["id"], array());
		}

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

		private function delete_member_group()
		{
			MemberPopulator::DeleteMemberFromGroup(
				$_GET["memberid"],
				$_GET["groupid"]);

				header("Location: editmember.php?action=edit_member&id=".
					$_GET["memberid"]);
		}

		private function list_groups()
		{
			$groups = GroupPopulator::PopulateAll();

			GroupView::DisplayList($groups);
		}

		private function add_group()
		{
			$group = new Group();

			GroupView::DisplayEditForm($group, "act_add_group");
		}

		private function edit_group()
		{
			$group = GroupPopulator::PopulateByID($_GET["id"]);

			GroupView::DisplayEditForm($group, "act_edit_group");
		}

		private function act_add_group()
		{
			return $this->update_group("act_add_group");
		}

		private function act_edit_group()
		{
			return $this->update_group("act_edit_group");
		}

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

		private function delete_group()
		{
			$group = GroupPopulator::PopulateByID($_GET["id"]);

			GroupView::DisplayDeleteForm($group);
		}

		private function act_delete_group()
		{
			GroupPopulator::DeleteGroup($_POST["id"]);

			header("Location: editmember.php?action=list_groups");
		}
	}
?>