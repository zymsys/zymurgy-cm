<?php
	class Member
	{
		private $m_id;
		private $m_email;
		private $m_password;
		private $m_registration_date;
		private $m_last_authorized;

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
	}
?>