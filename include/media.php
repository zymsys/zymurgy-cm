<?php
/**
 * 1 reference left from inputWidget.php
 *
 * Zymurgy:CM Media File Component
 * Z:CM View and Controller classes
 *
 * This component has been built using a self-contained MVC structure. To insert
 * media file code from within Zymurgy:CM, the controller should be instantiated
 * and called as follows:
 *
 * -----
 * $mediaController = new MediaFileController();
 * $mediaController->Execute($action);
 * -----
 *
 * The valid values for $action are as follows:
 *  - Installation Actions
 *  	- install (when explicitly enabled - see comment in Controller class)
 *  	- uninstall (when explicitly enabled - see comment in Controller class)
 *  - Media File Actions
 * 		- list_media_files
 * 		- add_media_file
 * 		- act_add_media_file
 * 		- edit_media_file
 * 		- act_edit_media_file
 * 		- delete_media_file
 * 		- act_delete_media_file
 * 		- add_media_file_relatedmedia
 * 		- act_add_media_file_relatedmedia
 * 		- delete_media_file_relation
 * 		- download_media_file
 * 		- stream_media_file
 * 	- Media Package Actions
 * 		- list_media_packages
 * 		- add_media_package
 * 		- act_add_media_package
 * 		- edit_media_package
 * 		- act_edit_media_package
 * 		- delete_media_package
 * 		- act_delete_media_package
 * 		- list_media_package_files
 * 		- add_media_package_file
 * 		- act_add_media_package_file
 * 		- move_media_package_file_up
 * 		- move_media_package_file_down
 * 		- delete_media_package_file
 * 	- Media Relations
 * 		- list_media_relations
 * 		- add_media_relation
 * 		- act_add_media_relation
 * 		- edit_media_relation
 * 		- act_edit_media_relation
 * 		- delete_media_relation
 * 		- act_delete_media_relation
 *
 * @package Zymurgy
 * @access private
 */

	// Include the model
	require_once(Zymurgy::getFilePath("~include/media.model.php"));

	class MediaFileInstaller
	{
		static function Upgrade()
		{
			require_once(Zymurgy::getFilePath("~installer/upgradelib.php"));

			$tableDefinitions = array();

			$tableDefinitions = array_merge(
				$tableDefinitions,
				MediaFilePopulator::GetTableDefinitions());
			$tableDefinitions = array_merge(
				$tableDefinitions,
				MediaPackagePopulator::GetTableDefinitions());
			$tableDefinitions = array_merge(
				$tableDefinitions,
				MediaPackageTypePopulator::GetTableDefinitions());
			$tableDefinitions = array_merge(
				$tableDefinitions,
				MediaRestrictionPopulator::GetTableDefinitions());
			$tableDefinitions = array_merge(
				$tableDefinitions,
				MediaRelationPopulator::GetTableDefinitions());

			ProcessTableDefinitions($tableDefinitions);

			echo("-- Configuring built-in Zymurgy:CM media types<br>");

			$sql = "INSERT INTO `zcm_media_relation` ( `relation_type`, `relation_type_label`, ".
				"`allowed_mimetypes`, `builtin` ) SELECT 'image', 'Image', 'image/jpeg,image/gif', 1 ".
				"FROM DUAL ".
				"WHERE NOT EXISTS( SELECT 1 FROM `zcm_media_relation` WHERE `relation_type` = ".
				"'image' )";
			mysql_query($sql)
				or die("Could not add Image: ".mysql_error().", $sql");
			$imageID = mysql_insert_id();

			$sql = "INSERT INTO `zcm_media_package_type` ( `package_type`, `package_type_label`, ".
				"`builtin` ) SELECT 'zcmimages', 'Zymurgy:CM Image Library', 1 FROM DUAL ".
				"WHERE NOT EXISTS( SELECT 1 FROM `zcm_media_package_type` WHERE `package_type` = ".
				"'zcmimages' )";
			mysql_query($sql)
				or die("Could not add Zymurgy:CM Image Library: ".mysql_error().", $sql");
			$imageLibraryID = mysql_insert_id();

			$sql = "INSERT IGNORE INTO `zcm_media_package_type_allowed_relation` ( ".
				"`media_package_type_id`, `media_relation_id`, `max_instances` ) VALUES ( '".
				mysql_escape_string($imageLibraryID).
				"', '".
				mysql_escape_string($imageID).
				"', 0)";
			mysql_query($sql)
				or die("Could not associate Images with Zymurgy:CM Image Library: ".mysql_error().", $sql");
		}

		static function Uninstall()
		{
			$sql = "DROP TABLE IF EXISTS `zcm_media_package_type_allowed_relation`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE IF EXISTS `zcm_media_package_type`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE IF EXISTS `zcm_media_file_package`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE IF EXISTS `zcm_media_package`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE IF EXISTS `zcm_media_file_relation`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE IF EXISTS `zcm_media_file`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE IF EXISTS `zcm_media_relation`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE IF EXISTS `zcm_media_restriction`";
			Zymurgy::$db->query($sql);
		}
	}

	class MediaFileView
	{
		static function DisplayList(
			$mediaFiles,
			$mediaRelations,
			$selectedMediaRelationType,
			$members,
			$selectedMemberID)
		{
			$breadcrumbTrail = Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.MediaFiles");

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<form name=\"filter\" action=\"media.php\" method=\"POST\">\n");
			echo("<input type=\"hidden\" name=\"action\" value=\"list_media_files\">\n");
			echo("<table>\n");

			echo("<tr>\n");
			echo(
				"<td>".
				Zymurgy::GetLocaleString("MediaFileView.DisplayList.Filter.ContentType")
				."</td>\n");
			echo("<td><select name=\"relation_type\">\n");
			echo(
				"<option value=\"\">".
				Zymurgy::GetLocaleString("Common.AllOption").
				"</option>\n");
			foreach($mediaRelations as $mediaRelation)
			{
				echo("<option value=\"".
					$mediaRelation->get_relation_type().
					"\"".
					($mediaRelation->get_relation_type() == $selectedMediaRelationType
						? " SELECTED"
						: "").
					">".
					$mediaRelation->get_relation_label().
					"</option>\n");
			}
			echo("</select></td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			echo(
				"<td>".
				Zymurgy::GetLocaleString("MediaFileView.DisplayList.Filter.Owner").
				"</td>\n");
			echo("<td><select name=\"member_id\">\n");
			echo(
				"<option value=\"\">".
				Zymurgy::GetLocaleString("Common.AllOption").
				"</option>\n");
			foreach($members as $member)
			{
				echo("<option value=\"".
					$member->get_member_id().
					"\"".
					($member->get_member_id() == $selectedMemberID
						? " SELECTED"
						: "").
					">".
					$member->get_email().
					"</option>\n");
			}
			echo("</select></td>\n");
			echo("</tr>\n");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>\n");

			echo("<tr>\n");
			echo("<td>&nbsp;</td>\n");
			echo(
				"<td><input type=\"submit\" value=\"".
				Zymurgy::GetLocaleString("Common.Filter").
				"\"></td>\n");
			echo("</tr>\n");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>\n");

			echo("</table>\n");
			echo("</form>\n");

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayList.Header.DisplayName");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayList.Header.ContentType");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayList.Header.MimeType");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayList.Header.Owner");
			echo("<td colspan=\"2\">&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaFiles as $mediaFile)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><a href=\"media.php?action=edit_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">".$mediaFile->get_display_name()."</td>");

				if($mediaFile->get_relation() == null)
				{
					echo("<td>&nbsp;</td>");
				}
				else
				{
					echo("<td>".$mediaFile->get_relation()->get_relation_label()."</td>");
				}

				echo("<td>".$mediaFile->get_mimetype()."</td>");

				if($mediaFile->get_member() == null)
				{
					echo("<td>&nbsp;</td>");
				}
				else
				{
					echo("<td>".$mediaFile->get_member()->get_email()."</td>");
				}

				echo("<td><a href=\"media.php?action=download_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">".
					Zymurgy::GetLocaleString("MediaFileView.DisplayList.Table.Download").
					"</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">".
					Zymurgy::GetLocaleString("MediaFileView.DisplayList.Table.Delete").
					"</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"6\"><a style=\"color: white;\" href=\"media.php?action=add_media_file\">".
				Zymurgy::GetLocaleString("MediaFileView.DisplayList.Footer.Add").
				"</a></td>");

			echo("</table>");

			include("footer.php");
		}

		static function DisplayTableHeader($key)
		{
			echo(
				"<td>".
				Zymurgy::GetLocaleString($key).
				"</td>");
		}

		static function RenderThumberJavascript($forceRefresh)
		{
			echo("<script type=\"text/javascript\">\n");
			echo("function mf_aspectcrop_popup(media_file_id, thumbnail) {\n");
			echo("url = '/zymurgy/media.php?action=edit_media_file_thumbnail&media_file_id={0}&thumbnail={1}&refresh=$forceRefresh';\n");
			echo("url = url.replace(\"{0}\", media_file_id);\n");
			echo("url = url.replace(\"{1}\", thumbnail);\n");
			echo("window.open(url, \"thumber\", \"scrollbars=no,width=780,height=500\");\n");
			echo("}\n");
			echo("</script>\n");
		}

		static function DisplayEditForm($mediaFile, $mediaRelations, $members, $action)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_files\">".
				Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.MediaFiles").
				"</a> &gt; ".
				Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.Edit");

			include("header.php");
			include('datagrid.php');

			MediaFileView::RenderThumberJavascript("true");

			DumpDataGridCSS();

			$widget = new InputWidget();

			// echo("<pre>");
			// print_r($mediaFile);
			// echo("</pre>");

			$errors = $mediaFile->get_errors();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">");
				echo("<div style=\"color: red\">Error</div>");
				echo("<ul>");

				foreach($errors as $error)
				{
					echo("<li>$error</li>");
				}

				echo("</ul>");
				echo("</div>");
			}

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\" enctype=\"multipart/form-data\">");
			echo("<input type=\"hidden\" name=\"action\" value=\"$action\">");
			echo("<input type=\"hidden\" name=\"media_file_id\" value=\"".$mediaFile->get_media_file_id()."\">");
			echo("<input type=\"hidden\" name=\"mimetype\" value=\"".$mediaFile->get_mimetype()."\">");
			echo("<input type=\"hidden\" name=\"extension\" value=\"\"".$mediaFile->get_extension()."\">");
			// echo("<input type=\"hidden\" name=\"member_id\" value=\"".$mediaFile->get_member()->get_member_id()."\">");
			echo("<table>");

			echo("<tr>");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayEditForm.Field.DisplayName");
			echo("<td>");
			$widget->Render("input.30.100", "display_name", $mediaFile->get_display_name());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayEditForm.Field.Price");
			echo("<td>");
			$widget->Render("input.10.9", "price", $mediaFile->get_price());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayEditForm.Field.Owner");
			echo("<td>");
			echo("<select name=\"member_id\">");

			foreach($members as $member)
			{
				echo("<option ".
					($member->get_member_id() == $mediaFile->get_member()->get_member_id() ? "SELECTED" : "").
					" value=\"".
					$member->get_member_id().
					"\">".
					$member->get_email().
					"</option>");
			}

			echo("</select>");
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayEditForm.Field.ContentType");
			echo("<td>");
			echo("<select name=\"media_relation_id\">");

			foreach($mediaRelations as $mediaRelation)
			{
				echo("<option ".
					($mediaFile->get_relation() !== null && $mediaRelation->get_media_relation_id() == $mediaFile->get_relation()->get_media_relation_id() ? "SELECTED" : "").
					" value=\"".
					$mediaRelation->get_media_relation_id().
					"\">".
					$mediaRelation->get_relation_label().
					"</option>");
			}

			echo("</select>");
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayEditForm.Field.File");
			echo("<td>");
			$widget->Render("attachment", "file", "");
			echo("</td>");
			echo("</tr>");

			if($action == "act_edit_media_file")
			{
				echo("<tr>");
				echo("<td>&nbsp;</td>");
				MediaFileView::DisplayTableHeader("MediaFileView.DisplayEditForm.EditFile");
				echo("</tr>");
			}

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayEditForm.Field.MimeType");
			echo("<td>".$mediaFile->get_mimetype()."</td>");
			echo("</tr>");

			if($mediaFile->get_relation() !== null
				&& strlen($mediaFile->get_relation()->get_thumbnails()) > 0)
			{
				echo("<tr>");
				echo("<td valign=\"bottom\">".
					Zymurgy::GetLocaleString("MediaFileView.DisplayEditForm.Field.Thumbnails").
					"</td>");
				echo("<td>");

				$thumbnails = explode(",", $mediaFile->get_relation()->get_thumbnails());

				foreach($thumbnails as $thumbnail)
				{
					$thumbnailItems = explode("x", $thumbnail);

					echo("<a href=\"javascript:mf_aspectcrop_popup(".
						$mediaFile->get_media_file_id().
						", '".
						$thumbnail.
						"');\">");
					echo("<img src=\"media.php?action=stream_media_file&amp;media_file_id=".
						$mediaFile->get_media_file_id().
						"&suffix=thumb".
						$thumbnail.
						"\" width=\"".
						$thumbnailItems[0].
						"\" height=\"".
						$thumbnailItems[1].
						"\" alt=\"Update ".
						$thumbnail.
						" Thumbnail\">");
					echo("</a>&nbsp;");
				}

				echo("</td>");
				echo("</tr>");
			}

			if($action == "act_edit_media_file")
			{
				echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

				echo("<tr>");
				echo("<td valign=\"top\">".
					Zymurgy::GetLocaleString("MediaFileView.DisplayEditForm.Field.RelatedMedia").
					"</td>");
				echo("<td>");
				MediaFileView::DisplayRelatedMedia($mediaFile);
				echo("</td>");
				echo("</tr>");
			}

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>&nbsp;</td>");
			echo("<td><input type=\"submit\" value=\"".
				Zymurgy::GetLocaleString("MediaFileView.DisplayEditForm.Submit").
				"\"></td>");
			echo("</tr>");

			echo("</table>");
			echo("</form>");

			include("footer.php");
		}

		static function DisplayRelatedMedia($mediaFile)
		{
			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayRelatedMedia.Header.DisplayName");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayRelatedMedia.Header.MimeType");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayRelatedMedia.Header.Owner");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayRelatedMedia.Header.Relation");
			echo("<td colspan=\"2\">&nbsp;</td>");
			echo("</tr>");

			$totalRelated = $mediaFile->get_relatedmedia_count();

			for($cntr = 0; $cntr < $totalRelated; $cntr++)
			{
				$relatedFile = $mediaFile->get_relatedmedia($cntr);

				echo("<tr class=\"".($cntr % 2 ? "DataGridRowAlternate" : "DataGridRow")."\">");
				echo("<td><label for=\"media_file_id_".
					$relatedFile->get_media_file_id().
					"\">".
					$relatedFile->get_display_name().
					"</td>");
				echo("<td>".$relatedFile->get_mimetype()."</td>");
				echo("<td>".$relatedFile->get_member()->get_email()."</td>");
				echo("<td>".$relatedFile->get_relation()->get_relation_label()."</td>");
				echo("<td><a href=\"media.php?action=download_media_file&amp;media_file_id=".
					$relatedFile->get_media_file_id()."\">".
					Zymurgy::GetLocaleString("MediaFileView.DisplayRelatedMedia.Table.Download").
					"</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_file_relation&amp;media_file_id=".
					$mediaFile->get_media_file_id().
					"&amp;related_media_file_id=".
					$relatedFile->get_media_file_id().
					"\">".
					Zymurgy::GetLocaleString("MediaFileView.DisplayRelatedMedia.Table.Delete").
					"</a></td>");
				echo("</tr>");
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"6\"><a style=\"color: white;\" href=\"media.php?action=".
				"add_media_file_relatedmedia&amp;media_file_id=".
				$mediaFile->get_media_file_id().
				"\">".
				Zymurgy::GetLocaleString("MediaFileView.DisplayRelatedMedia.Footer.Add").
				"</td>");

			echo("</table>");
		}

		public static function DisplayAddDialog(
			$mediaFile,
			$mediaPackage,
			$mediaRelation,
			$member,
			$imageList)
		{
			$output = "";

			$output .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";

 			$output .= "<html>\n";
 			$output .= "<head>\n";
 			$output .= "<title>Add File to Library</title>\n";
			$output .= "<style type=\"text/css\">\n";
			$output .= "body {\n";
			$output .= "font-family: ".(isset(Zymurgy::$config["font"]) ? Zymurgy::$config["font"] : "Verdana, Arial, Helvetica, sans-serif").";\n";
			$output .= "font-size:small;\n";
			$output .= "}\n";
			$output .= "</style>\n";
			$output .= "</head>\n";

			$output .= "<body>\n";

			$output .= "<form name=\"frm\" action=\"media.php\" method=\"POST\" enctype=\"multipart/form-data\">\n";

			$output .= "<input type=\"hidden\" name=\"action\" value=\"act_add_media_file_dialog\">\n";
			$output .= "<input type=\"hidden\" name=\"media_file_id\" value=\"0\">\n";
			$output .= "<input type=\"hidden\" name=\"mimetype\" value=\"\">\n";
			$output .= "<input type=\"hidden\" name=\"extension\" value=\"\">\n";
			$output .= "<input type=\"hidden\" name=\"price\" value=\"\">\n";
			$output .= "<input type=\"hidden\" name=\"member_id\" value=\"".
				$member->get_member_id().
				"\">\n";
			$output .= "<input type=\"hidden\" name=\"media_package_id\" value=\"".
				$mediaPackage->get_media_package_id().
				"\">\n";
			$output .= "<input type=\"hidden\" name=\"media_relation_id\" value=\"".
				$mediaRelation->get_media_relation_id().
				"\">\n";
			$output .= "<input type=\"hidden\" name=\"imagelist\" value=\"".
				$imageList.
				"\">\n";

			$output .= "<table>\n";

			$output .= "<tr>\n";
			$output .= "<td>File:</td>\n";
			$output .= "<td><input type=\"file\" name=\"file\" size=\"20\"></td>\n";
			$output .= "</tr>\n";

			$output .= "<tr>\n";
			$output .= "<td>Display Name:</td>\n";
			$output .= "<td><input type=\"text\" name=\"display_name\" size=\"30\" value=\"".
				$mediaFile->get_display_name().
				"\"></td>\n";
			$output .= "</tr>\n";

			$output .= "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";

			$output .= "<tr>\n";
			$output .= "<td>&nbsp;</td>\n";
			$output .= "<td><input type=\"submit\" name=\"cmdOK\" value=\"Add\" style=\"width: 80px;\"> <input type=\"button\" name=\"cmdCancel\" Value=\"Cancel\" onclick=\"window.close();\" style=\"width: 80px;\"></td>\n";
			$output .= "</tr>\n";

			$output .= "</table>\n";

			if(count($mediaFile->get_errors()) > 0)
			{
				$output .= "<ul><li>";
				$output .= implode("</li><li>", $mediaFile->get_errors());
				$output .= "</li></ul>\n";
			}

			$output .= "</form>\n";

			$output .= "</body>\n";

			$output .= "</html>\n";

 			echo($output);
		}

		public static function DisplayMediaFileAddedDialog(
			$imagelist)
		{
			$element = str_replace("Editor", "", $imagelist);
			$element = str_replace("Dialog", "", $element);

			echo("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" ");
			echo("\"http://www.w3.org/TR/html4/loose.dtd\">\n");
        	echo("<html>\n");
 			echo("<head>\n");

			echo Zymurgy::YUI("assets/skins/sam/skin.css");
			echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
			echo Zymurgy::YUI("element/element-min.js");
			echo Zymurgy::YUI("connection/connection-min.js");
			echo Zymurgy::YUI("container/container-min.js");
			echo Zymurgy::YUI("button/button-min.js");
			echo Zymurgy::YUI("dragdrop/dragdrop-min.js");
			echo Zymurgy::YUI("editor/editor-min.js");


 			echo("<script type=\"text/javascript\">\n");
?>
			var loadObject = {
				targetElement: "<?= $element ?>_dlgBody",
				url: "/zymurgy/media.php?action=insert_image_into_yuihtml" +
					"&editor_id=<?= $element ?>Editor",
				handleSuccess:function(o)
				{
					// alert("Success");
					opener.document.getElementById(this.targetElement).innerHTML = o.responseText;

					window.close();
				},
				handleFailure:function(o)
				{
					// alert("Failure");
					opener.document.getElementById(this.targetElement).innerHTML =
						o.status + ": " + o.responseText;
				},
				startRequest:function()
				{
					opener.document.getElementById(this.targetElement).innerHTML = "Updating...";

					YAHOO.util.Connect.asyncRequest(
						"GET",
						this.url,
						loadCallback,
						null);
				}
			};

			// alert("-- AJAX connection declared");

			var loadCallback =
			{
				success: loadObject.handleSuccess,
				failure: loadObject.handleFailure,
				scope: loadObject
			};

			// alert("-- Callback declared");

			function init() {
				loadObject.startRequest();
			}
<?
			echo("</script>\n");
			echo("</head>\n");
			echo("<body onLoad=\"init();\">\n");
			echo("Please wait while we update the list of images...");
 			echo("</body>\n");
 			echo("</html>\n");
		}

		static function DisplayDeleteFrom($mediaFile)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_files\">".
				Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.MediaFiles").
				"</a> &gt; ".
				Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.Delete");

			include("header.php");

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_media_file\">");
			echo("<input type=\"hidden\" name=\"media_file_id\" value=\"".
				$mediaFile->get_media_file_id()."\">");

			echo("<p>".
				Zymurgy::GetLocaleString("MediaFileView.DisplayDeleteForm.Confirm").
				"</p>");
			echo("<p>".
				Zymurgy::GetLocaleString("MediaFileView.DisplayDeleteForm.Field.DisplayName").
				" ".
				$mediaFile->get_display_name().
				"</p>");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"".
				Zymurgy::GetLocaleString("Common.Yes").
				"\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"".
				Zymurgy::GetLocaleString("Common.No").
				"\" onclick=\"window.location.href='media.php?action=list_media_files';\">");
			echo("</p>");

			echo("</form>");

			include("footer.php");
		}

		static function DisplayListOfFilesToAdd($mediaFile, $mediaFiles, $mediaRelations)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_files\">".
				Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.MediaFiles").
				"</a> &gt; <a href=\"media.php?action=edit_media_file&amp;media_file_id=".
				$mediaFile->get_media_file_id().
				"\">".
				Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.Edit").
				"</a> &gt; ".
				Zymurgy::GetLocaleString("MediaFileView.BreadcrumbTrail.AddRelated");

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_add_media_file_relatedmedia\">");
			echo("<input type=\"hidden\" name=\"media_file_id\" value=\"".
				$mediaFile->get_media_file_id()."\">");

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>&nbsp;</td>");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayListOfFilesToAdd.Header.DisplayName");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayListOfFilesToAdd.Header.MimeType");
			MediaFileView::DisplayTableHeader("MediaFileView.DisplayListOfFilesToAdd.Header.Owner");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaFiles as $relatedFile)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><input type=\"radio\" id=\"media_file_id_".
					$relatedFile->get_media_file_id().
					"\" name=\"related_media_file_id\" value=\"".
					$relatedFile->get_media_file_id().
					"\">");
				echo("<td><label for=\"media_file_id_".
					$relatedFile->get_media_file_id().
					"\">".
					$relatedFile->get_display_name().
					"</td>");
				echo("<td>".$relatedFile->get_mimetype()."</td>");
				echo("<td>".$relatedFile->get_member()->get_email()."</td>");
				echo("<td><a href=\"media.php?action=download_media_file&amp;media_file_id=".
					$relatedFile->get_media_file_id()."\">".
					Zymurgy::GetLocaleString("MediaFileView.DisplayListOfFilesToAdd.Table.Download").
					"</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"5\">&nbsp;</td>");

			echo("</table>");

			echo("<p>".
				Zymurgy::GetLocaleString("MediaFileView.DisplayListOfFilesToAdd.Field.Relation").
				": <select name=\"media_relation_id\">");

			foreach($mediaRelations as $mediaRelation)
			{
				echo("<option value=\"".
					$mediaRelation->get_media_relation_id().
					"\">".
					$mediaRelation->get_relation_label().
					"</option>");
			}

			echo("</select></p>");
			echo("<p><input style=\"width: 80px;\" type=\"submit\" value=\"".
				Zymurgy::GetLocaleString("MediaFileView.DisplayListOfFilesToAdd.Submit").
				"\">&nbsp;");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"".
				Zymurgy::GetLocaleString("MediaFileView.DisplayListOfFilesToAdd.Cancel").
				"\" onclick=\"window.location.href='media.php?action=edit_media_file".
				"&media_file_id=".$mediaFile->get_media_file_id()."';\">&nbsp;</p>");

			include("footer.php");
		}

		static function DisplayThumber($mediaFile, $thumbnail, $refreshParent)
		{
			list ($width,$height) = explode('x', $thumbnail, 2);
			$minwidth = $initwidth = $width;
			$minheight = $initheight = $height;

			$uploadfolder = Zymurgy::$config["Media File Local Path"];
			$filepath = $uploadfolder.
				"/".
				$mediaFile->get_media_file_id();

			$work = array();
			list($work['w'], $work['h'], $type, $attr) = getimagesize($filepath."aspectcropNormal.jpg");
			$raw = array();
			list($raw['w'], $raw['h'], $type, $attr) = getimagesize($filepath."raw.jpg");

			$xfactor = $raw['w'] / $work['w'];
			$yfactor = $raw['h'] / $work['h'];

			//Adjust min's for raw image size
			$minwidth *= $work['w'] / $raw['w'];
			$minheight *= $work['h'] / $raw['h'];

			if (($width>$work['w']) || ($height>$work['h']))
			{
				//Supplied im,age is too small for thumb size.  Shrink selector and relax minimum size requirement.
				$xfactor = $yfactor = 1;
				if ($width>$work['w'])
					$xfactor = $work['w']/$width;
				if ($height>$work['h'])
					$yfactor = $work['h']/$height;
				$factor = min($xfactor,$yfactor);
				$minwidth = $initwidth = round($width * $factor);
				$minheight = $initheight = round($height * $factor);
				//$minwidth = $minheight = 20;
			}
			//Do we have room for a nice 10px gap to start, or will we push right to the edge?
			//echo "if (min({$work['w']} - $initwidth,{$work['h']} - $initheight) < 0)"; exit;
			if (min($work['w'] - $initwidth,$work['h'] - $initheight) < 10)
			{
				$initx = 0;
				$inity = 0;
			}
			else
			{
				$initx = 10;
				$inity = 10;
			}

			//Try to load previous cropping area
			$shfn = $filepath."thumb".$thumbnail.".jpg.sh";
			if (file_exists($shfn))
			{
				$fc = file_get_contents($shfn);
				$fc = explode("\n",$fc);
				$fc = explode('(',$fc[0]);
				$fc = explode(',',$fc[1]);
				$lastcrop = array();
				for ($n = 0; $n < 6; $n++)
				{
					$lc = explode(':',$fc[$n]);
					$lastcrop[$lc[0]] = $lc[1];
				}
				$initx = round($lastcrop['sx'] / $xfactor);
				$inity = round($lastcrop['sy'] / $yfactor);
				$initwidth = round($lastcrop['sw'] / $xfactor);
				$initheight = round($lastcrop['sh'] / $yfactor);
			}

			echo("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" ");
			echo("\"http://www.w3.org/TR/html4/loose.dtd\">\n");
        	echo("<html>\n");
 			echo("<head>\n");
 			echo("<title>Zymurgy:CM Thumbnail Selection Tool</title>");
 			echo Zymurgy::YUI("yahoo/yahoo-min.js");
 			echo Zymurgy::YUI("dom/dom-min.js");
 			echo Zymurgy::YUI("event/event-min.js");
 			echo Zymurgy::YUI("dragdrop/dragdrop-min.js");
 			echo("<script type=\"text/javascript\" src=\"include/aspectCrop.js\"></script>\n");
 			echo("<script type=\"text/javascript\" src=\"include/DDResize.js\"></script>\n");
 			echo("<style type=\"text/css\">\n");
 			echo("body { background: white; font-family: Arial, Helvetica, Sans-Serif; margin: 10px; padding: 0px; }\n");
 			echo("div#divForm { position: absolute; left: ".($work['w'] + 20)."px; top: 10px; width: 110px; height: 30px; overflow: crop; z-index: 9; }\n");
 			echo("img#imgBackground { width: ".$work['w']."px; height: ".$work['h']."px; }\n");
 			echo("#panelDiv { position: absolute; height: 150px; width: 300px; top: 10px; left: 10px; background-color: #F7F7F7; overflow: hidden; z-index: 10; }\n");
 			echo("#handleDiv { position: absolute; bottom: 0px; right: 0px; width: 16px; height: 16px; background-image: url(images/resizeHandle.gif); font-size: 1px; z-index: 20; }\n");
 			echo("#theimage { position: absolute; top: 10px; left: 10px; }\n");
 			echo("img#imgCropped { position: absolute; left: 0px; top: 0px; width: ".$width."px; height: ".$height."px; z-index: 10; }\n");
 			echo("</style>\n");
 			echo("<script type=\"text/javascript\">\n");
  			echo("var dd, dd2; // Yahoo! Drag and Drop objects\n");
 			echo("var offsetX = 10, offsetY = 10; // position of the background image\n");
			echo("var initX = $initx, initY = $inity; // initial position of the crop box\n");
  			echo("var initWidth = $initwidth, initHeight = $initheight; // initial width and height of the crop box\n");
			echo("var minWidth = $minwidth, minHeight = $minheight; // minimum width and height of the crop box\n");
 			echo("var aspectRatio = parseFloat(minWidth) / parseFloat(minHeight);\n");
 			echo("var granularity = 1; // granularity of the drag-drop constraint area\n");
			echo("var borderWidth = 0; // some of the calculations take this into account\n");
  			echo("function init() {\n");
 			echo("//dd is the resize handle\n");
	        echo("dd = new YAHOO.example.DDResize(\"panelDiv\", \"handleDiv\", \"panelresize\");\n");
			echo("//dd2 is the selected region of the image\n");
	        echo("dd2 = new YAHOO.util.DDProxy(\"panelDiv\", \"paneldrag\");\n");
	        echo("dd2.addInvalidHandleId(\"handleDiv\"); //Don't let the handle drag the image\n");
			echo("panelDiv = document.getElementById(\"panelDiv\");\n");
	        echo("panelDiv.style.left = (initX + offsetX) + \"px\";\n");
	        echo("panelDiv.style.top = (initY + offsetY) + \"px\";\n");
	        echo("panelDiv.style.width = initWidth + \"px\";\n");
	        echo("panelDiv.style.height = initHeight + \"px\";\n");
			echo("// Called when selected thumb area is dragged.\n");
 			echo("updateCropArea = function(e) {\n");
 			echo("var pd = document.getElementById(\"panelDiv\");\n");
 			echo("var imgX = pd.offsetLeft - offsetX + borderWidth;\n");
 			echo("var imgY = pd.offsetTop - offsetY + borderWidth;\n");
 	  		echo("cropImage('imgCropped', imgX, imgY);\n");
	 		echo("setConstraints(dd2, dd, \"panelDiv\", \"imgBackground\", initX, initY, imgX, imgY, borderWidth);\n");
 			echo("};\n");
 			echo("updateCropArea();\n");
			echo("document.getElementById(\"imgBackground\").style.width =\n");
 			echo("document.getElementById(\"imgCropped\").style.width = '".$work['w']."px';\n");
 			echo("document.getElementById(\"imgBackground\").style.height =\n");
 			echo("document.getElementById(\"imgCropped\").style.height = '".$work['h']."px';\n");
			echo("dd.onMouseUp = updateCropArea;\n");
 			echo("dd2.onMouseUp = updateCropArea;\n");
			echo("}\n");
			echo("</script>\n");
			echo("</head>\n");
			echo("<body onLoad=\"init();\">\n");
			echo("<div id=\"divForm\">\n");
			echo("<form name=\"frmCrop\" method=\"POST\" action=\"".$_SERVER['REQUEST_URI']."\">\n");
			echo("<input type=\"hidden\" name=\"action\" value=\"act_edit_media_file_thumbnail\">\n");
			echo("<input type=\"hidden\" name=\"refreshParent\" value=\"$refreshParent\">\n");
 			echo("<input type=\"hidden\" name=\"cropX\" value=\"10\">\n");
 			echo("<input type=\"hidden\" name=\"cropY\" value=\"10\">\n");
 			echo("<input type=\"hidden\" name=\"cropWidth\" value=\"200\">\n");
 			echo("<input type=\"hidden\" name=\"cropHeight\" value=\"100\">\n");
 			echo("<input type=\"hidden\" name=\"cropScale\" value=\"1.0\">\n");
 			echo("<input type=\"button\" name=\"cmdSubmit\" value=\"Save Image\" onClick=\"submitForm();\">\n");
 			echo("<input type=\"button\" name=\"cmdClear\" value=\"Clear Image\" onClick=\"clearImage();\">\n");
  			echo("</form>\n");
    		echo("</div>\n");
  			echo("<img id=\"imgBackground\" src=\"media.php?action=stream_media_file&media_file_id=".
  				$mediaFile->get_media_file_id().
  				"&suffix=aspectcropDark\">\n");
 			echo("<div id=\"panelDiv\">\n");
	 		echo("<img id=\"imgCropped\" src=\"media.php?action=stream_media_file&media_file_id=".
  				$mediaFile->get_media_file_id().
  				"&suffix=aspectcropNormal\">\n");
        	echo("<div id=\"handleDiv\"></div>\n");
    		echo("</div>\n");
 			echo("</body>\n");
 			echo("</html>\n");
		}

		static function DisplayThumbCreated($refreshParent)
		{
			echo("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" ");
			echo("\"http://www.w3.org/TR/html4/loose.dtd\">\n");
        	echo("<html>\n");
 			echo("<head>\n");
 			echo("<script type=\"text/javascript\">\n");

 			if($refreshParent)
 			{
 				echo("window.opener.location.reload();\n");
 			}

 			echo("window.close();\n");
 			echo("</script>\n");
			echo("</head>\n");
			echo("<body onLoad=\"init();\">\n");
			echo("&nbsp;");
 			echo("</body>\n");
 			echo("</html>\n");
		}

		static function DownloadMediaFile($mediaFile, $fileContent)
		{
			header('Content-Description: File Transfer');
			header('Content-Type: '.$mediaFile->get_mimetype());
			header('Content-Disposition: attachment; filename="'.
				$mediaFile->get_display_name().".".
				$mediaFile->get_extension()).'"';
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: '.strlen($fileContent));
			ob_clean();
			flush();
			print($fileContent);
		}

		static function StreamMediaFile($mediaFile, $fileContent)
		{
			// header('Content-Description: File Transfer');
			header('Content-Type: '.$mediaFile->get_mimetype());
			// header('Content-Disposition: attachment; filename="'.
			// 	$mediaFile->get_display_name().".".
			// 	$mediaFile->get_extension()).'"';
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: '.strlen($fileContent));
			ob_clean();
			flush();
			print($fileContent);
		}
	}

	class MediaPackageView
	{
		static function DisplayList(
			$mediaPackages,
			$mediaPackageTypes,
			$selectedMediaPackageType,
			$members,
			$selectedMember)
		{
			$breadcrumbTrail = Zymurgy::GetLocaleString("MediaPackageView.BreadcrumbTrail.MediaPackages");

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();
			echo("<form name=\"filter\" action=\"media.php\" method=\"POST\">\n");
			echo("<input type=\"hidden\" name=\"action\" value=\"list_media_packages\">\n");
			echo("<table>\n");

			echo("<tr>\n");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayList.Field.PackageType");
			echo("<td><select name=\"relation_type\">\n");
			echo("<option value=\"\">".
				Zymurgy::GetLocaleString("Common.AllOption").
				"</option>\n");
			foreach($mediaPackageTypes as $mediaRelation)
			{
				echo("<option value=\"".
					$mediaRelation->get_package_type().
					"\"".
					($mediaRelation->get_package_type() == $selectedMediaPackageType
						? " SELECTED"
						: "").
					">".
					$mediaRelation->get_package_label().
					"</option>\n");
			}
			echo("</select></td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayList.Field.Owner");
			echo("<td><select name=\"member_id\">\n");
			echo("<option value=\"\">".
				Zymurgy::GetLocaleString("Common.AllOption").
				"</option>\n");
			foreach($members as $member)
			{
				echo("<option value=\"".
					$member->get_member_id().
					"\"".
					($member->get_member_id() == $selectedMember
						? " SELECTED"
						: "").
					">".
					$member->get_email().
					"</option>\n");
			}
			echo("</select></td>\n");
			echo("</tr>\n");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>\n");

			echo("<tr>\n");
			echo("<td>&nbsp;</td>\n");
			echo("<td><input type=\"submit\" value=\"".
				Zymurgy::GetLocaleString("Common.Filter").
				"\"></td>\n");
			echo("</tr>\n");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>\n");

			echo("</table>\n");
			echo("</form>\n");

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayList.Header.DisplayName");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayList.Header.PackageType");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayList.Header.Owner");
			echo("<td colspan=\"2\">&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaPackages as $mediaPackage)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><a href=\"media.php?action=edit_media_package&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."\">".$mediaPackage->get_display_name()."</td>");

				if($mediaPackage->get_packagetype() == null)
				{
					echo("<td>&nbsp;</td>");
				}
				else
				{
					echo("<td>".$mediaPackage->get_packagetype()->get_package_label()."</td>");
				}

				echo("<td>".$mediaPackage->get_member()->get_email()."</td>");
				echo("<td><a href=\"media.php?action=list_media_package_files&amp;media_package_id=".
					$mediaPackage->get_media_package_id().
					"\">".
					Zymurgy::GetLocaleString("MediaPackageView.DisplayList.Table.Files").
					"</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_package&amp;media_package_id=".
					$mediaPackage->get_media_package_id().
					"\">".
					Zymurgy::GetLocaleString("MediaPackageView.DisplayList.Table.Delete").
					"</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"5\"><a style=\"color: white;\" href=\"media.php?action=add_media_package\">".
				Zymurgy::GetLocaleString("MediaPackageView.DisplayList.Footer.Add").
				"</td>");

			echo("</table>");

			include("footer.php");
		}

		public static function DisplayTableHeader($key)
		{
			echo("<td>".
				Zymurgy::GetLocaleString($key).
				"</td>\n");
		}

		static function DisplayEditForm(
			$mediaPackage,
			$members,
			$packageTypes,
			$action)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_packages\">".
				Zymurgy::GetLocaleString("MediaPackageView.BreadcrumbTrail.MediaPackages").
				"</a> &gt; ".
				Zymurgy::GetLocaleString("MediaPackageView.BreadcrumbTrail.Edit");

			include("header.php");
			$widget = new InputWidget();

			$errors = $mediaPackage->get_errors();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">");
				echo("<div style=\"color: red\">Error</div>");
				echo("<ul>");

				foreach($errors as $error)
				{
					echo("<li>$error</li>");
				}

				echo("</ul>");
				echo("</div>");
			}

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\" enctype=\"multipart/form-data\">");
			echo("<input type=\"hidden\" name=\"action\" value=\"$action\">");
			echo("<input type=\"hidden\" name=\"media_package_id\" value=\"".$mediaPackage->get_media_package_id()."\">");
			// echo("<input type=\"hidden\" name=\"member_id\" value=\"".$mediaPackage->get_member()->get_member_id()."\">");
			echo("<table>");

			echo("<tr>");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayEditForm.Field.DisplayName");
			echo("<td>");
				$widget->Render("input.30.100", "display_name", $mediaPackage->get_display_name());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayEditForm.Field.Price");
			echo("<td>");
				$widget->Render("input.10.9", "price", $mediaPackage->get_price());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayEditForm.Field.Owner");
			echo("<td>");
			echo("<select name=\"member_id\">");

			foreach($members as $member)
			{
				echo("<option ".
					($member->get_member_id() == $mediaPackage->get_member()->get_member_id() ? "SELECTED" : "").
					" value=\"".
					$member->get_member_id().
					"\">".
					$member->get_email().
					"</option>");
			}

			echo("</select>");
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			MediaPackageView::DisplayTableHeader("MediaPackageView.DisplayEditForm.Field.PackageType");
			echo("<td>");
			echo("<select name=\"media_package_type_id\">");

			foreach($packageTypes as $packageType)
			{
				$selected = $mediaPackage->get_packagetype() !== null
					&& $packageType->get_media_package_type_id() == $mediaPackage->get_packagetype()->get_media_package_type_id()
					? "SELECTED"
					: "";

				echo("<option ".
					$selected.
					" value=\"".
					$packageType->get_media_package_type_id().
					"\">".
					$packageType->get_package_label().
					"</option>");
			}

			echo("</select>");
			echo("</td>");
			echo("</tr>");

			// echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			// echo("<tr>");
			// echo("<td>Owner:</td>");
			// echo("<td>".$mediaPackage->get_member()->get_email()."</td>");
			// echo("</tr>");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>&nbsp;</td>");
			echo("<td><input type=\"submit\" value=\"".
				Zymurgy::GetLocaleString("MediaPackageView.DisplayEditForm.Submit").
				"\"></td>");
			echo("</tr>");

			echo("</table>");
			echo("</form>");

			echo("<table class=\"DataGrid\" style=\"margin-top: 20px;\">\n");
			echo("<tr><th style=\"background-color: #A0A0A0; border-bottom: 1px solid black;\">".
				Zymurgy::GetLocaleString("MediaPackageView.DisplayEditForm.Commands").
				"</th></tr>\n");
			echo("<tr><td style=\"border-bottom: 1px solid black;\"><a ".
				"style=\"text-decoration: none;\" href=\"media.php?action=".
				"list_media_package_files&media_package_id=".
				$mediaPackage->get_media_package_id().
				"\">".
				Zymurgy::GetLocaleString("MediaPackageView.DisplayEditForm.CommandItems.Files").
				"</a></td></tr>\n");
			echo("</table>");

			include("footer.php");
		}

		static function DisplayDeleteFrom($mediaPackage)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_packages\">Media Packages</a> &gt; Delete Media Package";

			include("header.php");

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_media_package\">");
			echo("<input type=\"hidden\" name=\"media_package_id\" value=\"".
				$mediaPackage->get_media_package_id()."\">");

			echo("<p>Are you sure you want to delete the following media package:</p>");
			echo("<p>Display Name: ".$mediaPackage->get_display_name()."</p>");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"Yes\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"No\" onclick=\"window.location.href='media.php?action=list_media_packages';\">");
			echo("</p>");

			echo("</form>");

			include("footer.php");
		}

		static function DisplayRelatedMedia($mediaPackage)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_packages\">Media Packages</a> &gt; <a href=\"media.php?action=edit_media_package&amp;media_package_id=".
				$mediaPackage->get_media_package_id().
				"\">Add/Edit Media Package</a> &gt; Related Media Files";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Media File Name</td>");
			echo("<td>Owner</td>");
			echo("<td>Relation</td>");
			echo("<td colspan=\"4\">&nbsp;</td>");
			echo("</tr>");

			for($cntr = 0; $cntr < $mediaPackage->get_media_file_count(); $cntr++)
			{
				$mediaFile = $mediaPackage->get_media_file($cntr);

				echo("<tr class=\"".($cntr % 2 ? "DataGridRowAlternate" : "DataGridRow")."\">");
				echo("<td>".$mediaFile->get_display_name()."</td>");
				echo("<td>".$mediaFile->get_member()->get_email()."</td>");

				if($mediaFile->get_relation() == null)
				{
					echo("<td>&nbsp;</td>");
				}
				else
				{
					echo("<td>".$mediaFile->get_relation()->get_relation_label()."</td>");
				}

				echo("<td><a href=\"media.php?action=move_media_package_file_up&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">".
					"<img src=\"images/Up.gif\" alt=\"Up\" border=\"0\"></a></td>");
				echo("<td><a href=\"media.php?action=move_media_package_file_down&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">".
					"<img src=\"images/Down.gif\" alt=\"Down\" border=\"0\"></a></td>");
				echo("<td><a href=\"media.php?action=download_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Download</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_package_file&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Delete</a></td>");
				echo("</tr>");
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"7\"><a style=\"color: white;\" href=\"media.php?action=add_media_package_file&amp;media_package_id=".$mediaPackage->get_media_package_id()."\">Add Media File to Package</td>");

			echo("</table>");

			include("footer.php");
		}

		static function DisplayListOfFilesToAdd(
			$mediaPackage,
			$mediaFiles,
			$mediaRelations,
			$selectedMediaRelationType,
			$members,
			$selectedMemberID)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_packages\">Media Packages</a> &gt; <a href=\"media.php?action=edit_media_package&amp;media_package_id=".
				$mediaPackage->get_media_package_id().
				"\">Add/Edit Media Package</a> &gt; <a href=\"media.php?action=list_media_package_files&amp;media_package_id=".
				$mediaPackage->get_media_package_id().
				"\">Related Media Files</a> &gt; Add Media File";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"add_media_package_file\">");
			echo("<input type=\"hidden\" name=\"media_package_id\" value=\"".
				$mediaPackage->get_media_package_id()."\">");
			echo("<table>\n");

			echo("<tr>\n");
			echo("<td>Package Type:</td>\n");
			echo("<td><select name=\"relation_type\">\n");
			echo("<option value=\"\">(all)</option>\n");
			foreach($mediaRelations as $mediaRelation)
			{
				echo("<option value=\"".
					$mediaRelation->get_relation_type().
					"\"".
					($mediaRelation->get_relation_type() == $selectedMediaRelationType
						? " SELECTED"
						: "").
					">".
					$mediaRelation->get_relation_label().
					"</option>\n");
			}
			echo("</select></td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			echo("<td>Owner:</td>\n");
			echo("<td><select name=\"member_id\">\n");
			echo("<option value=\"\">(all)</option>\n");
			foreach($members as $member)
			{
				echo("<option value=\"".
					$member->get_member_id().
					"\"".
					($member->get_member_id() == $selectedMemberID
						? " SELECTED"
						: "").
					">".
					$member->get_email().
					"</option>\n");
			}
			echo("</select></td>\n");
			echo("</tr>\n");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>\n");

			echo("<tr>\n");
			echo("<td>&nbsp;</td>\n");
			echo("<td><input type=\"submit\" value=\"Filter\"></td>\n");
			echo("</tr>\n");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>\n");

			echo("</table>\n");
			echo("</form>\n");

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_add_media_package_file\">");
			echo("<input type=\"hidden\" name=\"media_package_id\" value=\"".
				$mediaPackage->get_media_package_id()."\">");

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>&nbsp;</td>");
			echo("<td>Display Name</td>");
			echo("<td>Content Type</td>");
			echo("<td>Owner</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaFiles as $mediaFile)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><input type=\"radio\" id=\"media_file_id_".
					$mediaFile->get_media_file_id().
					"\" name=\"media_file_id\" value=\"".
					$mediaFile->get_media_file_id().
					"\">");
				echo("<td><label for=\"media_file_id_".
					$mediaFile->get_media_file_id().
					"\">".
					$mediaFile->get_display_name().
					"</td>");
				echo("<td>".$mediaFile->get_relation()->get_relation_label()."</td>");
				echo("<td>".$mediaFile->get_member()->get_email()."</td>");
				echo("<td><a href=\"media.php?action=download_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Download</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"5\">&nbsp;</td>");

			echo("</table>");

			echo("</select></p>");
			echo("<p><input style=\"width: 80px;\" type=\"submit\" value=\"Save\">&nbsp;");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"Cancel\" onclick=\"window.location.href='media.php?action=list_media_package_files&media_package_id=".$mediaPackage->get_media_package_id()."';\">&nbsp;</p>");

			include("footer.php");
		}

		static function DownloadMediaPackage($mediaPackage)
		{
			$uploadfolder = Zymurgy::$config["Media File Local Path"];
			$filepath = $uploadfolder.
				"/".
				$mediaPackage->get_media_package_id().
				".zip";

			header('Content-Description: File Transfer');
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="'.
				$mediaPackage->get_display_name().".zip");
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: '.filesize($filepath));
			ob_clean();
			flush();

			$file = fopen($filepath, "r");
			while(!feof($file))
			{
				print fread($file, 8192);
			}
			fclose($file);
		}
	}

	class MediaPackageTypeView
	{
		static function DisplayList($mediaPackageTypes)
		{
			$breadcrumbTrail = "Media Package Types";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Package Type</td>");
			echo("<td>Label</td>");
			echo("<td>Built-In</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaPackageTypes as $mediaPackageType)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");

				if($mediaPackageType->get_builtin())
				{
					echo("<td>".$mediaPackageType->get_package_type()."</td>");
				}
				else
				{
					echo("<td><a href=\"media.php?action=edit_media_package_type&amp;media_package_type_id=".
						$mediaPackageType->get_media_package_type_id()."\">".
						$mediaPackageType->get_package_type()."</a></td>");
				}

				echo("<td>".$mediaPackageType->get_package_label()."</td>");
				echo("<td>".($mediaPackageType->get_builtin() ? "Yes" : "&nbsp;")."</td>");

				if($mediaPackageType->get_builtin())
				{
					echo("<td>&nbsp;</td>");
				}
				else
				{
					echo("<td><a href=\"media.php?action=delete_media_package_type&amp;media_package_type_id=".
						$mediaPackageType->get_media_package_type_id()."\">Delete</a></td>");
				}

				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"4\"><a style=\"color: white;\" href=\"media.php?action=add_media_package_type\">Add Media Package Type</td>");

			echo("</table>");

			include("footer.php");
		}

		public static function DisplayEditForm($mediaPackageType, $action)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_package_types\">Media Package Types</a> &gt; Add/Edit Media Package Type";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			$widget = new InputWidget();

			// echo("<pre>");
			// print_r($mediaFile);
			// echo("</pre>");

			$errors = $mediaPackageType->get_errors();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">");
				echo("<div style=\"color: red\">Error</div>");
				echo("<ul>");

				foreach($errors as $error)
				{
					echo("<li>$error</li>");
				}

				echo("</ul>");
				echo("</div>");
			}

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\" enctype=\"multipart/form-data\">");
			echo("<input type=\"hidden\" name=\"action\" value=\"$action\">");
			echo("<input type=\"hidden\" name=\"media_package_type_id\" value=\"".$mediaPackageType->get_media_package_type_id()."\">");
			echo("<table>");

			echo("<tr>");
			echo("<td>Package Type:</td>");
			echo("<td>");
			$widget->Render("input.30.50", "package_type", $mediaPackageType->get_package_type());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			echo("<td>Label:</td>");
			echo("<td>");
			$widget->Render("input.30.50", "package_label", $mediaPackageType->get_package_label());
			echo("</td>");
			echo("</tr>");

			if($action == "act_edit_media_package_type")
			{
				echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

				echo("<tr>");
				echo("<td valign=\"top\">Allowed Relations:</td>");
				echo("<td>");
				MediaPackageTypeView::DisplayAllowedRelations($mediaPackageType);
				echo("</td>");
				echo("</tr>");
			}

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>&nbsp;</td>");
			echo("<td><input type=\"submit\" value=\"Save\"></td>");
			echo("</tr>");

			echo("</table>");
			echo("</form>");

			include("footer.php");
		}

		public static function DisplayAllowedRelations($mediaPackageType)
		{
			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Relation</td>");
			echo("<td>Max Instances</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$totalRelated = $mediaPackageType->get_allowedrelation_count();

			for($cntr = 0; $cntr < $totalRelated; $cntr++)
			{
				$relation = $mediaPackageType->get_allowedrelation($cntr);

				echo("<tr class=\"".($cntr % 2 ? "DataGridRowAlternate" : "DataGridRow")."\">");
				echo("<td><a href=\"media.php?action=edit_media_package_type_allowed_relation&amp;allowed_relation_id=".
					$relation->get_media_package_type_allowed_relation_id().
					"\">".
					$relation->get_relation()->get_relation_label().
					"</a></td>");
				echo("<td>".$relation->get_maxinstances()."</td>");
				echo("<td><a href=\"media.php?action=delete_media_package_type_allowed_relation&amp;allowed_relation_id=".
					$relation->get_media_package_type_allowed_relation_id().
					"\">Delete</a></td>");
				echo("</tr>");
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"6\"><a style=\"color: white;\" href=\"media.php?action=add_media_package_type_allowed_relation&amp;media_package_type_id=".
					$mediaPackageType->get_media_package_type_id().
					"\">Add relation</td>");

			echo("</table>");
		}

		public static function DisplayEditAllowedRelationForm(
			$allowedRelation,
			$packageType,
			$mediaRelations,
			$action)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_package_types\">Media Package Types</a> &gt; <a href=\"media.php?action=edit_media_package_type&amp;media_package_type_id=".
				$packageType->get_media_package_type_id().
				"\">Add/Edit Media Package Type</a> &gt; Add/Edit Allowed Relation";

			include("header.php");
			$widget = new InputWidget();

			// echo("<pre>");
			// print_r($mediaFile);
			// echo("</pre>");

			$errors = $allowedRelation->get_errors();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">");
				echo("<div style=\"color: red\">Error</div>");
				echo("<ul>");

				foreach($errors as $error)
				{
					echo("<li>$error</li>");
				}

				echo("</ul>");
				echo("</div>");
			}

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\" enctype=\"multipart/form-data\">");
			echo("<input type=\"hidden\" name=\"action\" value=\"$action\">");
			echo("<input type=\"hidden\" name=\"media_package_type_allowed_relation_id\" value=\"".$allowedRelation->get_media_package_type_allowed_relation_id()."\">");
			echo("<input type=\"hidden\" name=\"media_package_type_id\" value=\"".$packageType->get_media_package_type_id()."\">");
			echo("<table>");

			echo("<tr>");
			echo("<td>Relation:</td>");
			echo("<td><select name=\"media_relation_id\">");
			foreach($mediaRelations as $mediaRelation)
			{
				$selected = $allowedRelation->get_relation() !== null && $mediaRelation->get_media_relation_id == $allowedRelation->get_relation()->get_media_relation_id()
					? "SELECTED"
					: "";
				echo("<option $selected value=\"".
					$mediaRelation->get_media_relation_id().
					"\">".
					$mediaRelation->get_relation_label().
					"</option>");
			}
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			echo("<td>Max Instances:</td>");
			echo("<td>");
			$widget->Render("numeric.5.5", "max_instances", $allowedRelation->get_maxinstances());
			echo("</td>");
			echo("</tr>");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>&nbsp;</td>");
			echo("<td><input type=\"submit\" value=\"Save\"></td>");
			echo("</tr>");

			echo("</table>");
			echo("</form>");

			include("footer.php");
		}

		public static function DisplayDeleteForm($packageType)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_media_package_types\">Media Package Types</a> &gt; Delete Media Package Type";

			include("header.php");

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_media_package_type\">");
			echo("<input type=\"hidden\" name=\"media_package_type_id\" value=\"".
				$packageType->get_media_package_type_id()."\">");

			echo("<p>Are you sure you want to delete the following media package type:</p>");
			echo("<p>Relation Type: ".$packageType->get_package_type());
			echo("<br>Label: ".$packageType->get_package_label()."</p>");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"Yes\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"No\" onclick=\"window.location.href='media.php?action=list_media_package_types';\">");
			echo("</p>");

			echo("</form>");

			include("footer.php");
		}
	}

	class MediaRelationView
	{
		static function DisplayList($mediaRelations)
		{
			$breadcrumbTrail = "Media Relations";

			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Type</td>");
			echo("<td>Label</td>");
			echo("<td>Built-In</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaRelations as $mediaRelation)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");

				if($mediaRelation->get_builtin())
				{
					echo("<td>".$mediaRelation->get_relation_type()."</td>");
				}
				else
				{
					echo("<td><a href=\"media.php?action=edit_relation&amp;media_relation_id=".
						urlencode($mediaRelation->get_media_relation_id())."\">".
						$mediaRelation->get_relation_type()."</a></td>");
				}

				echo("<td>".$mediaRelation->get_relation_label()."</td>");
				echo("<td>".($mediaRelation->get_builtin() ? "Yes" : "&nbsp;")."</td>");

				if($mediaRelation->get_builtin())
				{
					echo("<td>&nbsp;</td>");
				}
				else
				{
					echo("<td><a href=\"media.php?action=delete_relation&amp;media_relation_id=".
						urlencode($mediaRelation->get_media_relation_id())."\">Delete</a></td>");
				}

				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"4\"><a style=\"color: white;\" href=\"media.php?action=add_relation\">Add Relation Type</td>");

			echo("</table>");

			include("footer.php");
		}

		static function DisplayEditForm($mediaRelation, $action)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_relations\">Media Relations</a> &gt; Add/Edit Relation";

			include("header.php");
			$widget = new InputWidget();

			// echo("<pre>");
			// print_r($mediaFile);
			// echo("</pre>");

			$errors = $mediaRelation->get_errors();

			if(count($errors) > 0)
			{
				echo("<div style=\"background-color: rgb(253, 238, 179); border: 2px solid red; padding: 10px; margin-bottom: 10px;\">");
				echo("<div style=\"color: red\">Error</div>");
				echo("<ul>");

				foreach($errors as $error)
				{
					echo("<li>$error</li>");
				}

				echo("</ul>");
				echo("</div>");
			}

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\" enctype=\"multipart/form-data\">");
			echo("<input type=\"hidden\" name=\"action\" value=\"$action\">");
			echo("<input type=\"hidden\" name=\"media_relation_id\" value=\"".$mediaRelation->get_media_relation_id()."\">");
			echo("<table>");

			echo("<tr>");
			echo("<td>Relation Type:</td>");
			echo("<td>");
			$widget->Render("input.30.50", "relation_type", $mediaRelation->get_relation_type());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			echo("<td>Label:</td>");
			echo("<td>");
			$widget->Render("input.30.50", "relation_label", $mediaRelation->get_relation_label());
			echo("</td>");
			echo("</tr>");

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>Allowed mimetypes:</td>");
			echo("<td>");
			$widget->Render("input.30.50", "allowed_mimetypes", $mediaRelation->get_allowed_mimetypes());
			echo("</td>");
			echo("</tr>");

			echo("<tr><td>&nbsp;</td><td colspan=\"2\">i.e. image/jpeg, audio/mpeg</td></tr>");
			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>Thumbnails to Generate:</td>");
			echo("<td>");
			$widget->Render("input.30.50", "thumbnails", $mediaRelation->get_thumbnails());
			echo("</td>");
			echo("</tr>");

			echo("<tr><td>&nbsp;</td><td colspan=\"2\">i.e. 120x90,800x600</td></tr>");
			echo("<tr><td>&nbsp;</td><td colspan=\"2\">Provide for image formats that will be displayed at a specified size</td></tr>");
			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>&nbsp;</td>");
			echo("<td><input type=\"submit\" value=\"Save\"></td>");
			echo("</tr>");

			echo("</table>");
			echo("</form>");

			include("footer.php");
		}

		static function DisplayDeleteFrom($mediaRelation)
		{
			$breadcrumbTrail = "<a href=\"media.php?action=list_relations\">Media Relations</a> &gt; Delete Relation";

			include("header.php");

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_relation\">");
			echo("<input type=\"hidden\" name=\"media_relation_id\" value=\"".
				$mediaRelation->get_media_relation_id()."\">");

			echo("<p>Are you sure you want to delete the following relation:</p>");
			echo("<p>Relation Type: ".$mediaRelation->get_relation_type());
			echo("<br>Label: ".$mediaRelation->get_relation_label()."</p>");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"Yes\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"No\" onclick=\"window.location.href='media.php?action=list_relations';\">");
			echo("</p>");

			echo("</form>");

			include("footer.php");
		}
	}

	class PageImageLibraryView
	{
		public static function DisplayNotConfiguredMessage()
		{
			echo "<p>The image library has not been properly configured in Zymurgy:CM.</p>";
		}

		public static function RenderJavascript()
		{
			$output = "";

			$output .= "<script type=\"text/javascript\">\n";

			$output .= "function InsertMediaFileInPage(editor) {\n";
			// $output .= "alert('InsertMediaFileInPage start');\n";
			$output .= "url = '/zymurgy/media.php?action={0}&amp;media_file_id={1}&amp;suffix=thumb{2}x{3}';";
			$output .= "url = url.replace(\"{0}\", \"stream_media_file\");\n";
			$output .= "url = url.replace(\"{1}\", document.getElementById(\"mediaFileID\").value);\n";
			$output .= "url = url.replace(\"{2}\", document.getElementById(\"mediaFileWidth\").value);\n";
			$output .= "url = url.replace(\"{3}\", document.getElementById(\"mediaFileHeight\").value);\n";
			// $output .= "alert(url);\n";
			$output .= "alt = document.getElementById(\"mediaFileAlt\").value;\n";
			$output .= "editor.toolbar.fireEvent('mediafileClick', { type: 'mediaFileClick', img: url, alt: alt } );\n";
			// $output .= "editor.toolbar.fireEvent('insertimageClick', { type: 'insertimageClick', target: editor.toolbar } );\n";
			// $output .= "alert('InsertMediaFileInPage fin');\n";
			$output .= "}\n";

			$output .= "function SelectFile(selectedImage, id, displayName, width, height) {\n";
			$output .= " document.getElementById(\"mediaFileID\").value = id;\n";
			$output .= " document.getElementById(\"mediaFileAlt\").value = displayName;\n";
			$output .= " document.getElementById(\"mediaFileWidth\").value = width;\n";
			$output .= " document.getElementById(\"mediaFileHeight\").value = height;\n";
			$output .= " imgList = document.getElementsByTagName(\"img\");\n";
			$output .= " YAHOO.log(imgList.length,'zcm');\n";
			$output .= " for (cntr = 0; cntr < imgList.length; cntr++) {\n";
			$output .= "  img = imgList[cntr];\n";
			$output .= "  YAHOO.log('Comparing ' + img.src + ' with ' + selectedImage.src,'zcm');\n";
			$output .= "  if ((selectedImage) && (img.src == selectedImage.src)) {\n";
			$output .= "   YAHOO.log('match found','zcm');\n";
			$output .= "   border = \"2px solid red\";\n";
			$output .= "  } else {\n";
			$output .= "   YAHOO.log('match not found','zcm');\n";
			$output .= "   border = \"2px solid white\";\n";
			$output .= "  }\n";
			$output .= "  img.style.border = border;\n";
			// $output .= "  alert(img.style.border + ' = ' + border);\n";
			$output .= " }\n";
			$output .= " YAHOO.log('SelectFile done','zcm');\n";
			$output .= "}\n";

			$output .= "</script>\n";

			return $output;
		}

		public static function DisplayImageList($mediaPackage, $yuiHtmlID)
		{
			echo("<input type=\"hidden\" name=\"mediaFileID\" id=\"mediaFileID\" value=\"\">\n");
			echo("Select your image:\n");
			echo("<div id=\"{$yuiHtmlID}_imagelist\" style=\"width: 100%; height: 67px; overflow: auto;\">\n");
			echo("<table cellspacing=\"3\" cellpadding=\"0\" border=\"0\">\n");

			echo("<tr>\n");

			for($cntr = 0; $cntr < $mediaPackage->get_media_file_count(); $cntr++)
			{
				$mediaFile = $mediaPackage->get_media_file($cntr);

				MediaController::EnsureLocalPathExists();
				$uploadfolder = Zymurgy::$config["Media File Local Path"];
				$filepath = $uploadfolder.
					"/".
					$mediaFile->get_media_file_id().
					"raw.jpg";

				if(!file_exists($filepath))
				{
					$filepath = str_replace("raw.jpg", ".jpg", $filepath);
				}

				list($width, $height, $type, $attr) = getimagesize($filepath);

				echo("<td><a href=\"javascript:SelectFile(document.getElementById('dlgImg_".
					$mediaFile->get_media_file_id().
					"'), ".
					$mediaFile->get_media_file_id().
					", '".
					$mediaFile->get_display_name().
					"', ".
					$width.
					", ".
					$height.
					");\"><img id=\"dlgImg_".
					$mediaFile->get_media_file_id().
					"\" src=\"/zymurgy/media.php?action=stream_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id().
					"&amp;suffix=thumb50x50\" height=\"50\" width=\"50\" border=\"0\" ".
					"style=\"border: 2px solid white;\"></a>\n");
				echo("</td>\n");
			}

			echo("<td><a href=\"javascript:;\" onclick=\"window.open('/zymurgy/media.php?action=add_media_file_dialog".
				"&amp;media_package_id=".
				$mediaPackage->get_media_package_id().
				"&amp;imagelist=".
				$yuiHtmlID.
				"', 'newMediaFileDialog', 'width=350,height=200');\"><img id=\"dlgImg_new\" src=\"/zymurgy/images/newImage.gif\" height=\"50\" ".
				"width=\"50\" style=\"border: 2px solid white;\"></a>\n");
			echo("</td>\n");

			echo("</tr>\n");

			echo("</table>\n");
			echo("</div>\n");

			echo("<table>\n");

			echo("<tr>\n");
			echo("<td width=\"25%\">Width:</td>");
			echo("<td width=\"15%\"><input type=\"text\" name=\"width\" id=\"mediaFileWidth\" size=\"5\" maxlength=\"3\" value=\"\"></td>\n");
			echo("<td width=\"15%\">Height:</td>");
			echo("<td width=\"15%\"><input type=\"text\" name=\"height\" id=\"mediaFileHeight\" size=\"5\" maxlength=\"3\" value=\"\"></td>\n");
			echo("<td width=\"30%\"><input id=\"mediaFileCrop\" type=\"button\" value=\"Crop...\" onClick=\"mf_aspectcrop_popup(document.getElementById('mediaFileID').value, document.getElementById('mediaFileWidth').value + 'x' + document.getElementById('mediaFileHeight').value, 'false');\"></td>\n");
			echo("</tr>\n");

			echo("<tr>\n");
			echo("<td>Alt&nbsp;Text:</td>");
			echo("<td colspan=\"4\"><input type=\"text\" name=\"alt\" id=\"mediaFileAlt\" size=\"45\" maxlength=\"100\"></td>\n");
			echo("</tr>\n");

			echo("</table>\n");
		}
	}

	class MediaController
	{
		function Execute($action)
		{
			// ZK: The Installer actions are disabled by default.
			// If you are working on the component and need to reset the
			// table definitions, uncomment the $action !== "install" line,
			// and use the following URLs:
			//
			// (ZymurgyRoot)/media.php?action=install
			// (ZymurgyRoot)/media.php?action=uninstall

			MediaController::EnsureLocalPathExists();
			if(
				// $action !== "install" && $action !== "uninstall" &&
				method_exists($this, $action))
			{
				call_user_func(array($this,$action));
				//call_user_method($action, $this);
			}
			else
			{
				die("Unsupported action ".$action);
			}
		}

		static function EnsureLocalPathExists()
		{
			if (!array_key_exists('Media File Local Path',Zymurgy::$config))
			{
				Zymurgy::$config['Media File Local Path'] = Zymurgy::$root.'/UserFiles/DefaultMediaFiles';
				if (!file_exists(Zymurgy::$config['Media File Local Path']))
				{
					mkdir(Zymurgy::$config['Media File Local Path']);
				}
			}
		}

		private function install()
		{
			MediaFileInstaller::Upgrade();
		}

		private function uninstall()
		{
			MediaFileInstaller::Uninstall();
		}

		private function list_media_files()
		{
			$mediaFiles = MediaFilePopulator::PopulateByOwner(
				isset($_POST["member_id"]) ? $_POST["member_id"] : 0,
				isset($_POST["relation_type"]) ? $_POST["relation_type"] : "");
			$mediaRelations = MediaRelationPopulator::PopulateAll();
			$members = MediaMemberPopulator::PopulateAll();

			MediaFileView::DisplayList(
				$mediaFiles,
				$mediaRelations,
				isset($_POST["relation_type"]) ? $_POST["relation_type"] : 0,
				$members,
				isset($_POST["member_id"]) ? $_POST["member_id"] : 0);
		}

		private function add_media_file()
		{
			$mediaFile = new MediaFile();
			$mediaFile->set_mimetype("n/a");
			$mediaFile->set_member(
				MediaMemberPopulator::PopulateByID(1));

			$mediaRelation = new MediaRelation();
			$mediaRelation->set_media_relation_id(0);
			$mediaFile->set_relation($mediaRelation);

			$mediaRelations = MediaRelationPopulator::PopulateAll();
			$members = MediaMemberPopulator::PopulateAll();

			MediaFileView::DisplayEditForm(
				$mediaFile,
				$mediaRelations,
				$members,
				"act_add_media_file");
		}

		private function edit_media_file()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_GET["media_file_id"]);

			if($mediaFile == null)
			{
				die("Invalid media_file_id. Aborting.");
			}

			MediaFilePopulator::PopulateRelatedMedia($mediaFile);
			$mediaRelations = MediaRelationPopulator::PopulateAll();
			$members = MediaMemberPopulator::PopulateAll();

			MediaFileView::DisplayEditForm(
				$mediaFile,
				$mediaRelations,
				$members,
				"act_edit_media_file");
		}

		private function act_add_media_file()
		{
			$this->update_media_file("act_add_media_file");
		}

		private function act_edit_media_file()
		{
			$this->update_media_file("act_edit_media_file");
		}

		private function update_media_file($action)
		{
			if($action == null)
			{
				die("update_media_file may not be called via the controller action");
			}

			$mediaFile = MediaFilePopulator::PopulateFromForm();

			if(!$mediaFile->validate($action))
			{
				$mediaRelations = MediaRelationPopulator::PopulateAll();
				MediaFileView::DisplayEditForm($mediaFile, $mediaRelations, $action);
			}
			else
			{
				if(MediaFilePopulator::SaveMediaFile($mediaFile))
				{
					$mediaRelations = MediaRelationPopulator::PopulateAll();
					MediaFileView::DisplayEditForm($mediaFile, $mediaRelations, $action);
				}
				else
				{
					header("Location: media.php?action=list_media_files");
				}
			}
		}

		private function add_media_file_dialog()
		{
			Zymurgy::memberauthenticate();

			$mediaPackage = MediaPackagePopulator::PopulateById(
				$_GET["media_package_id"]);
			$mediaRelation = MediaRelationPopulator::PopulateByType(
				"image");
			$member = MediaMemberPopulator::PopulateByID(
				Zymurgy::$member["id"]);

			MediaFileView::DisplayAddDialog(
				new MediaFile(),
				$mediaPackage,
				$mediaRelation,
				$member,
				$_GET["imagelist"]);
		}

		private function act_add_media_file_dialog()
		{
			Zymurgy::memberauthenticate();

			$mediaFile = MediaFilePopulator::PopulateFromForm();
			$mediaPackage = MediaPackagePopulator::PopulateById(
				$_POST["media_package_id"]);
			MediaPackagePopulator::PopulateMediaFiles($mediaPackage, "image");
			$mediaRelation = MediaRelationPopulator::PopulateByType(
				"image");
			$member = MediaMemberPopulator::PopulateByID(
				Zymurgy::$member["id"]);

			if(!$mediaFile->validate("act_add_media_file"))
			{
				MediaFileView::DisplayAddDialog(
					$mediaFile,
					$mediaPackage,
					$mediaRelation,
					$member,
					$_POST["imagelist"]);
			}
			else
			{
				if(MediaFilePopulator::SaveMediaFile($mediaFile))
				{
					MediaFileView::DisplayAddDialog(
						$mediaFile,
						$mediaPackage,
						$mediaRelation,
						$member,
						$_POST["imagelist"]);
				}
				else
				{
					MediaPackagePopulator::AddMediaFileToPackage(
						$mediaPackage->get_media_package_id(),
						$mediaFile->get_media_file_id(),
						$mediaRelation->get_media_relation_id(),
						$mediaPackage->get_media_file_count() + 1);

					MediaFileView::DisplayMediaFileAddedDialog(
						$_POST["imagelist"]);
				}
			}
		}

		private function delete_media_file()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_GET["media_file_id"]);
			MediaFileView::DisplayDeleteFrom($mediaFile);
		}

		private function act_delete_media_file()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_POST["media_file_id"]);
			MediaFilePopulator::DeleteMediaFile(
				$mediaFile);

			header("Location: media.php?action=list_media_files");
		}

		private function add_media_file_relatedmedia()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_GET["media_file_id"]);
			$mediaFiles = MediaFilePopulator::PopulateAllNotRelated(
				$mediaFile);
			$mediaRelations = MediaRelationPopulator::PopulateAll();

			MediaFileView::DisplayListOfFilesToAdd(
				$mediaFile,
				$mediaFiles,
				$mediaRelations);
		}

		private function act_add_media_file_relatedmedia()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_POST["media_file_id"]);

			MediaFilePopulator::AddRelatedMedia(
				$_POST["media_file_id"],
				$_POST["related_media_file_id"],
				$_POST["media_relation_id"]);

			header(
				"Location: media.php?action=edit_media_file&media_file_id=".
				$_POST["media_file_id"]);
		}

		private function delete_media_file_relation()
		{
			MediaFilePopulator::DeleteRelatedMedia(
				$_GET["media_file_id"],
				$_GET["related_media_file_id"]);

			header(
				"Location: media.php?action=edit_media_file&media_file_id=".
				$_GET["media_file_id"]);
		}

		private function edit_media_file_thumbnail()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_GET["media_file_id"]);
			MediaFilePopulator::InitializeThumbnail(
				$mediaFile,
				$_GET["thumbnail"]);
			MediaFileView::DisplayThumber(
				$mediaFile,
				$_GET["thumbnail"],
				$_GET["refresh"]);
		}

		private function act_edit_media_file_thumbnail()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_GET["media_file_id"]);
			MediaFilePopulator::MakeThumbnail(
				$mediaFile,
				$_GET["thumbnail"]);

			// print_r($_POST);

			MediaFileView::DisplayThumbCreated(
				$_POST["refreshParent"] == "true");
		}

		private function download_media_file()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_GET["media_file_id"]);
			$fileContent = MediaFilePopulator::GetFilestream(
				$mediaFile);

			MediaFileView::DownloadMediaFile($mediaFile, $fileContent);
		}

		private function stream_media_file()
		{
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_GET["media_file_id"]);
			$fileContent = MediaFilePopulator::GetFilestream(
				$mediaFile,
				$_GET["suffix"]);

			MediaFileView::StreamMediaFile($mediaFile, $fileContent);
		}

		private function list_media_packages()
		{
			$mediaPackages = MediaPackagePopulator::PopulateByOwner(
				isset($_POST["member_id"]) ? $_POST["member_id"] : 0,
				isset($_POST["package_type"]) ? $_POST["package_type"] : "");
			$packageTypes = MediaPackageTypePopulator::PopulateAll();
			$members = MediaMemberPopulator::PopulateAll();

			MediaPackageView::DisplayList(
				$mediaPackages,
				$packageTypes,
				isset($_POST["package_type"]) ? $_POST["package_type"] : 0,
				$members,
				isset($_POST["member_id"]) ? $_POST["member_id"] : 0);
		}

		private function add_media_package()
		{
			$mediaPackage = new MediaPackage();
			$members = MediaMemberPopulator::PopulateAll();
			$mediaPackage->set_member($members[0]);
			$packageTypes = MediaPackageTypePopulator::PopulateAll();

			MediaPackageView::DisplayEditForm(
				$mediaPackage,
				$members,
				$packageTypes,
				"act_add_media_package");
		}

		private function edit_media_package()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				$_GET["media_package_id"]);

			if($mediaPackage == null)
			{
				die("Invalid media_package_id. Aborting.");
			}

			$members = MediaMemberPopulator::PopulateAll();
			$packageTypes = MediaPackageTypePopulator::PopulateAll();

			MediaPackageView::DisplayEditForm(
				$mediaPackage,
				$members,
				$packageTypes,
				"act_edit_media_package");
		}

		private function act_add_media_package()
		{
			$this->update_media_package("act_add_media_package");
		}

		private function act_edit_media_package()
		{
			$this->update_media_package("act_edit_media_package");
		}

		private function update_media_package($action)
		{
			$mediaPackage = MediaPackagePopulator::PopulateFromForm();
			$members = MediaMemberPopulator::PopulateAll();
			$packageTypes = MediaPackageTypePopulator::PopulateAll();

			if(!$mediaPackage->validate($action))
			{
				MediaPackageView::DisplayEditForm(
					$mediaPackage,
					$members,
					$packageTypes,
					$action);
			}
			else
			{
				if(MediaPackagePopulator::SaveMediaPackage($mediaPackage))
				{
					MediaPackageView::DisplayEditForm(
						$mediaPackage,
						$members,
						$packageTypes,
						$action);
				}
				else
				{
					header("Location: media.php?action=list_media_packages");
				}
			}
		}

		private function delete_media_package()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				$_GET["media_package_id"]);

			MediaPackageView::DisplayDeleteFrom($mediaPackage);
		}

		private function act_delete_media_package()
		{
			MediaPackagePopulator::DeleteMediaPackage(
				$_POST["media_package_id"]);

			header("Location: media.php?action=list_media_packages");
		}

		private function list_media_package_files()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				$_GET["media_package_id"]);
			MediaPackagePopulator::PopulateMediaFiles($mediaPackage);

			MediaPackageView::DisplayRelatedMedia($mediaPackage);
		}

		private function add_media_package_file()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				isset($_POST["media_package_id"]) ? $_POST["media_package_id"] : $_GET["media_package_id"]);
			$mediaFiles = MediaFilePopulator::PopulateAllNotInPackage(
				$mediaPackage,
				isset($_POST["relation_type"]) ? $_POST["relation_type"] : "",
				isset($_POST["member_id"]) ? $_POST["member_id"] : 0);
			$mediaRelations = MediaRelationPopulator::PopulateAll();
			$members = MediaMemberPopulator::PopulateAll();

			MediaPackageView::DisplayListOfFilesToAdd(
				$mediaPackage,
				$mediaFiles,
				$mediaRelations,
				isset($_POST["relation_type"]) ? $_POST["relation_type"] : "",
				$members,
				isset($_POST["member_id"]) ? $_POST["member_id"] : 0);
		}

		private function act_add_media_package_file()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				$_POST["media_package_id"]);
			$mediaFile = MediaFilePopulator::PopulateByID(
				$_POST["media_file_id"]);
			MediaPackagePopulator::PopulateMediaFiles($mediaPackage);
			$disporder = $mediaPackage->get_media_file_count();

			MediaPackagePopulator::AddMediaFileToPackage(
				$mediaPackage->get_media_package_id(),
				$mediaFile->get_media_file_id(),
				$mediaFile->get_relation()->get_media_relation_id(),
				$disporder + 1);

			header(
				"Location: media.php?action=list_media_package_files&media_package_id=".
				$_POST["media_package_id"]);
		}

		private function move_media_package_file_up()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				$_GET["media_package_id"]);
			MediaPackagePopulator::PopulateMediaFiles($mediaPackage);

			$mediaPackage->MoveMediaFile($_GET["media_file_id"], -1);
			MediaPackagePopulator::SaveMediaFileOrder($mediaPackage);

			header(
				"Location: media.php?action=list_media_package_files&media_package_id=".
				$_GET["media_package_id"]);
		}

		private function move_media_package_file_down()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				$_GET["media_package_id"]);
			MediaPackagePopulator::PopulateMediaFiles($mediaPackage);

			$mediaPackage->MoveMediaFile($_GET["media_file_id"], 1);
			MediaPackagePopulator::SaveMediaFileOrder($mediaPackage);

			header(
				"Location: media.php?action=list_media_package_files&media_package_id=".
				$_GET["media_package_id"]);
		}

		private function delete_media_package_file()
		{
			MediaPackagePopulator::DeleteMediaFileFromPackage(
				$_GET["media_package_id"],
				$_GET["media_file_id"]);

			header(
				"Location: media.php?action=list_media_package_files&media_package_id=".
					$_GET["media_package_id"]);
		}

		private function download_media_package()
		{
			$mediaPackage = MediaPackagePopulator::PopulateByID(
				$_GET["media_package_id"]);

			MediaPackagePopulator::BuildZipFile($mediaPackage, false);

			MediaPackageView::DownloadMediaPackage($mediaPackage);
		}

		private function list_relations()
		{
			$relations = MediaRelationPopulator::PopulateAll();

			MediaRelationView::DisplayList($relations);
		}

		private function add_relation()
		{
			$relation = new MediaRelation();

			MediaRelationView::DisplayEditForm($relation, "act_add_relation");
		}

		private function edit_relation()
		{
			$relation = MediaRelationPopulator::PopulateByID(
				$_GET["media_relation_id"]);

			if($relation == null)
			{
				die("Invalid media_relation_id. Aborting.");
			}

			MediaRelationView::DisplayEditForm($relation, "act_add_relation");
		}

		private function act_add_relation()
		{
			$this->update_media_relation("act_add_relation");
		}

		private function act_edit_relation()
		{
			$this->update_media_relation("act_edit_relation");
		}

		private function update_media_relation($action)
		{
			$relation = MediaRelationPopulator::PopulateFromForm();

			if(!$relation->validate($action))
			{
				MediaRelationView::DisplayEditForm($relation, $action);
			}
			else
			{
				if(MediaRelationPopulator::SaveRelation($relation))
				{
					MediaRelationView::DisplayEditForm($relation, $action);
				}
				else
				{
					header("Location: media.php?action=list_relations");
				}
			}
		}

		private function delete_relation()
		{
			$relation = MediaRelationPopulator::PopulateByID(
				$_GET["media_relation_id"]);

			MediaRelationView::DisplayDeleteFrom($relation);
		}

		private function act_delete_relation()
		{
			MediaRelationPopulator::DeleteRelation(
				$_POST["media_relation_id"]);

			header("Location: media.php?action=list_relations");
		}

		private function list_media_package_types()
		{
			$packageTypes = MediaPackageTypePopulator::PopulateAll();

			MediaPackageTypeView::DisplayList($packageTypes);
		}

		private function add_media_package_type()
		{
			$packageType = new MediaPackageType();

			MediaPackageTypeVieW::DisplayEditForm(
				$packageType,
				"act_add_media_package_type");
		}

		private function edit_media_package_type()
		{
			$packageType = MediaPackageTypePopulator::PopulateByID(
				$_GET["media_package_type_id"]);

			if($packageType == null)
			{
				die("Invalid media_package_type_id. Aborting.");
			}

			MediaPackageTypePopulator::PopulateAllowedRelations($packageType);

			MediaPackageTypeVieW::DisplayEditForm(
				$packageType,
				"act_edit_media_package_type");
		}

		private function act_add_media_package_type()
		{
			$this->update_media_package_type("act_add_media_package_type");
		}

		private function act_edit_media_package_type()
		{
			$this->update_media_package_type("act_edit_media_package_type");
		}

		private function update_media_package_type($action)
		{
			$packageType = MediaPackageTypePopulator::PopulateFromForm();

			if(!$packageType->validate($action))
			{
				MediaPackageTypeVieW::DisplayEditForm($packageType, $action);
			}
			else
			{
				if(MediaPackageTypePopulator::SaveMediaPackageType($packageType))
				{
					MediaPackageTypeView::DisplayEditForm($packageType, $action);
				}
				else
				{
					header("Location: media.php?action=list_media_package_types");
				}
			}
		}

		private function delete_media_package_type()
		{
			$packageType = MediaPackageTypePopulator::PopulateByID(
				$_GET["media_package_type_id"]);

			MediaPackageTypeVieW::DisplayDeleteForm($packageType);
		}

		private function act_delete_media_package_type()
		{
			MediaPackageTypePopulator::DeleteMediaPackageType(
				$_POST["media_package_type_id"]);

			header("Location: media.php?action=list_media_package_types");
		}

		private function add_media_package_type_allowed_relation()
		{
			$packageType = MediaPackageTypePopulator::PopulateByID(
				$_GET["media_package_type_id"]);
			$allowedRelation = new MediaPackageTypeAllowedRelation();
			$mediaRelations = MediaRelationPopulator::PopulateAll();

			MediaPackageTypeView::DisplayEditAllowedRelationForm(
				$allowedRelation,
				$packageType,
				$mediaRelations,
				"act_add_media_package_type_allowed_relation");
		}

		private function edit_media_package_type_allowed_relation()
		{
			$allowedRelation = MediaPackageTypePopulator::PopulateAllowedRelationByID(
				$_GET["allowed_relation_id"]);

			if($allowedRelation == null)
			{
				die("Invalid allowed_relation_id. Aborting.");
			}

			$packageType = MediaPackageTypePopulator::PopulateByID(
				$allowedRelation->get_media_package_type_id());
			$mediaRelations = MediaRelationPopulator::PopulateAll();

			MediaPackageTypeView::DisplayEditAllowedRelationForm(
				$allowedRelation,
				$packageType,
				$mediaRelations,
				"act_edit_media_package_type_allowed_relation");
		}

		private function act_add_media_package_type_allowed_relation()
		{
			$this->update_media_package_type_allowed_relation(
				"act_add_media_package_type_allowed_relation");
		}

		private function act_edit_media_package_type_allowed_relation()
		{
			$this->update_media_package_type_allowed_relation(
				"act_edit_media_package_type_allowed_relation");
		}

		private function update_media_package_type_allowed_relation($action)
		{
			$packageType = MediaPackageTypePopulator::PopulateByID(
				$_POST["media_package_type_id"]);
			$allowedRelation = MediaPackageTypePopulator::PopulateAllowedRelationFromForm();

			if(!$allowedRelation->validate($action))
			{
				$mediaRelations = MediaRelationPopulator::PopulateAll();
				MediaPackageTypeView::DisplayEditAllowedRelationForm(
					$allowedRelation,
					$packageType,
					$mediaRelations,
					$action);
			}
			else
			{
				if(MediaPackageTypePopulator::SaveMediaPackageTypeAllowedRelation($allowedRelation))
				{
					$mediaRelations = MediaRelationPopulator::PopulateAll();
					MediaPackageTypeView::DisplayEditAllowedRelationForm(
						$allowedRelation,
						$packageType,
						$mediaRelations,
						$action);
				}
				else
				{
					header("Location: media.php?action=edit_media_package_type&media_package_type_id=".$_POST["media_package_type_id"]);
				}
			}
		}

		private function delete_media_package_type_allowed_relation()
		{
			$allowedRelation = MediaPackageTypePopulator::PopulateAllowedRelationByID(
				$_GET["allowed_relation_id"]);
			$packageType = MediaPackageTypePopulator::PopulateByID(
				$allowedRelation->get_media_package_type_id());

			if($allowedRelation !== null)
			{
				MediaPackageTypePopulator::DeleteMediaPackageTypeAllowedRelation(
					$allowedRelation->get_media_package_type_allowed_relation_id());
			}

			MediaPackageTypePopulator::PopulateAllowedRelations($packageType);
			MediaPackageTypeVieW::DisplayEditForm($packageType, "act_edit_media_package_type");
		}

		private function insert_image_into_yuihtml()
		{
			Zymurgy::memberauthenticate();

			$mediaPackages = MediaPackagePopulator::PopulateByOwner(
				0,
				"zcmimages");

			if(count($mediaPackages) <= 0)
			{
				$mediaPackage = new MediaPackage();
				$mediaPackage->set_display_name("My Zymurgy:CM Image Library");
				$member = MediaMemberPopulator::PopulateByID(
					Zymurgy::$member["id"]);
				$mediaPackage->set_member($member);
				$mediaPackageType = MediaPackageTypePopulator::PopulateByType("zcmimages");
				$mediaPackage->set_packagetype($mediaPackageType);

				MediaPackagePopulator::SaveMediaPackage($mediaPackage);

				// PageImageLibraryView::DisplayNotConfiguredMessage();
			}
			else
			{
				$mediaPackage = $mediaPackages[0];
			}

			MediaPackagePopulator::PopulateMediaFiles(
				$mediaPackage,
				"image");

			PageImageLibraryView::DisplayImageList(
				$mediaPackage,
				$_GET["editor_id"]);
		}
	}
?>