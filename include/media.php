<?php
	/*
		Zymurgy:CM Media File Component
		Z:CM View and Controller classes

		This component has been built using a self-contained MVC structure. To insert
		media file code from within Zymurgy:CM, the controller should be instantiated
		and called as follows:

		-----
		$mediaController = new MediaFileController();
		$mediaController->Execute($action);
		-----

		The valid values for $action are as follows:
		    - Installation Actions
		 		- install (when explicitly enabled - see comment in Controller class)
		 		- uninstall (when explicitly enabled - see comment in Controller class)
		 	- Media File Actions
				- list_media_files
				- add_media_file
				- act_add_media_file
				- edit_media_file
				- act_edit_media_file
				- delete_media_file
				- act_delete_media_file
				- add_media_file_relatedmedia
				- act_add_media_file_relatedmedia
				- delete_media_file_relation
				- download_media_file
				- stream_media_file
			- Media Package Actions
				- list_media_packages
				- add_media_package
				- act_add_media_package
				- edit_media_package
				- act_edit_media_package
				- delete_media_package
				- act_delete_media_package
				- list_media_package_files
				- add_media_package_file
				- act_add_media_package_file
				- move_media_package_file_up
				- move_media_package_file_down
				- delete_media_package_file
			- Media Relations
				- list_media_relations
				- add_media_relation
				- act_add_media_relation
				- edit_media_relation
				- act_edit_media_relation
				- delete_media_relation
				- act_delete_media_relation
	*/

	// Include the model
	require_once(Zymurgy::$root."/zymurgy/include/media.model.php");

	class MediaFileInstaller
	{
		static function InstalledVersion()
		{
			$sql = "show tables like 'zcm_media_file'";
			$tableExists = Zymurgy::$db->get($sql);

			if(!($tableExists == 'zcm_media_file'))
			{
				return 0;
			}
			else
			{
				// Check for a column defined in each version of the
				// table definition, in descending order. If the column is
				// found, the return statement ensures that older versions
				// won't be checked.

				// If none of the columns are found, return as version 1.

				$sql = "show columns from `zcm_media_relation` like 'thumbnails'";
				$fieldExists = Zymurgy::$db->get($sql);
				if(isset($fieldExists[0]) && $fieldExists[0] == 'thumbnails') return 5;

				$sql = "show columns from `zcm_media_file` like 'price'";
				$fieldExists = Zymurgy::$db->get($sql);
				if(isset($fieldExists[0]) && $fieldExists[0] == 'price') return 4;

				$sql = "show columns from `zcm_media_package` like 'media_package_type_id'";
				$fieldExists = Zymurgy::$db->get($sql);
				if(isset($fieldExists[0]) && $fieldExists[0] == 'media_package_type_id') return 3;

				$sql = "show columns from `zcm_media_file` like 'media_relation_id'";
				$fieldExists = Zymurgy::$db->get($sql);
				if(isset($fieldExists[0]) && $fieldExists[0] == 'media_relation_id') return 2;

				return 1;
			}
		}

		static function Version()
		{
			return 5;
		}

		static function Install()
		{
			MediaFileInstaller::Upgrade(0, MediaFileInstaller::Version());
		}

		static function Upgrade($currentVersion, $targetVersion)
		{
			for($version = $currentVersion + 1; $version <= $targetVersion; $version++)
			{
				switch($version)
				{
					case 1:
						$sql = "CREATE TABLE `zcm_media_restriction` (".
							"`media_restriction_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
							"`download_limit` INTEGER UNSIGNED NOT NULL,".
							"`day_limit` INTEGER UNSIGNED NOT NULL,".
							"PRIMARY KEY (`media_restriction_id`)".
							") ENGINE = InnoDB;";
						Zymurgy::$db->query($sql)
							or die("Could not create zcm_media_restriction table: ".mysql_error());

						$sql = "CREATE TABLE `zcm_media_relation` (".
							"`media_relation_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
							"`relation_type` VARCHAR(50) NOT NULL,".
							"`relation_type_label` VARCHAR(50) NOT NULL,".
							"PRIMARY KEY (`media_relation_id`)".
							") ENGINE = InnoDB;";
						Zymurgy::$db->query($sql)
							or die("Could not create zcm_media_relation table: ".mysql_error());

						$sql = "CREATE TABLE `zcm_media_file` (".
							"`media_file_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
							"`member_id` INTEGER UNSIGNED NOT NULL,".
							"`mimetype` VARCHAR(45) NOT NULL,".
							"`extension` VARCHAR(10) NOT NULL,".
							"`display_name` VARCHAR(100) NOT NULL,".
							"`media_restriction_id` INTEGER UNSIGNED,".
							"PRIMARY KEY (`media_file_id`)".
							") ENGINE = InnoDB;";
						Zymurgy::$db->query($sql)
							or die("Could not create zcm_media_file table: ".mysql_error());

						$sql = "CREATE TABLE `zcm_media_file_relation` (".
							"`media_file_relation_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
							"`media_file_id` INTEGER UNSIGNED NOT NULL,".
							"`related_media_file_id` INTEGER UNSIGNED NOT NULL,".
							"`media_relation_id` INTEGER UNSIGNED NOT NULL,".
							"PRIMARY KEY (`media_file_relation_id`)".
							") ENGINE = InnoDB";
						Zymurgy::$db->query($sql)
							or die("Could not create zcm_media_file_relation table: ".mysql_error());

						$sql = "CREATE TABLE `zcm_media_package` (".
							"`media_package_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
							"`member_id` INTEGER UNSIGNED NOT NULL,".
							"`display_name` VARCHAR(100) NOT NULL,".
							"`media_restriction_id` INTEGER UNSIGNED,".
							"PRIMARY KEY (`media_package_id`)".
							") ENGINE = InnoDB;";
						Zymurgy::$db->query($sql)
							or die("Could not create zcm_media_package table: ".mysql_error());

						$sql = "CREATE TABLE `zcm_media_file_package` (".
							"`media_file_package_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
							"`media_file_id` INTEGER UNSIGNED NOT NULL,".
							"`media_package_id` INTEGER UNSIGNED NOT NULL,".
							"`disporder` INTEGER UNSIGNED NOT NULL,".
							"`media_relation_id` INTEGER NOT NULL,".
							"PRIMARY KEY (`media_file_package_id`)".
							") ENGINE = InnoDB;";
						Zymurgy::$db->query($sql)
							or die("Could not create zcm_media_file_package table: ".mysql_error());

						break;

					case 2:
						$sql = "ALTER TABLE `zcm_media_relation` ".
							"ADD COLUMN `allowed_mimetypes` VARCHAR(200) AFTER `relation_type_label`;";
						Zymurgy::$db->query($sql)
							or die("Could not upgrade zcm_media_relation table: ".mysql_error());

						$sql = "ALTER TABLE `zcm_media_file` ".
							"ADD COLUMN `media_relation_id` INTEGER UNSIGNED AFTER `media_restriction_id`;";
						Zymurgy::$db->query($sql)
							or die("Could not upgrade zcm_media_file table: ".mysql_error());

						break;

					case 3:
						$sql = "CREATE TABLE `zcm_media_package_type` (".
							"`media_package_type_id` INTEGER UNSIGNED ".
								"NOT NULL AUTO_INCREMENT,".
							"`package_type` VARCHAR(50) NOT NULL,".
							"`package_type_label` VARCHAR(50) NOT NULL,".
							"PRIMARY KEY (`media_package_type_id`)".
							") ENGINE = InnoDB;";
						Zymurgy::$db->query($sql)
						 	or die(
						 		"Could not create zcm_media_package_type table: ".
						 		mysql_error());

						$sql = "CREATE TABLE `zcm_media_package_type_allowed_relation` (".
							"`media_package_type_allowed_relation_id` INTEGER UNSIGNED ".
								"NOT NULL AUTO_INCREMENT,".
							"`media_package_type_id` INTEGER UNSIGNED NOT NULL,".
							"`media_relation_id` INTEGER UNSIGNED NOT NULL,".
							"`max_instances` INTEGER UNSIGNED NOT NULL,".
							"PRIMARY KEY (`media_package_type_allowed_relation_id`)".
							") ENGINE = InnoDB;";
						Zymurgy::$db->query($sql)
							or die(
								"Could not create zcm_media_package_type_allowed_relation table: ".
								mysql_error());

						$sql = "ALTER TABLE `zcm_media_package` ".
							"ADD COLUMN `media_package_type_id` INTEGER UNSIGNED NOT NULL ".
								"AFTER `media_restriction_id`;";
						Zymurgy::$db->query($sql)
							or die("Could not upgrade zcm_media_relation table: ".mysql_error());

						break;

					case 4:
						$sql = "ALTER TABLE `zcm_media_file` ".
							"ADD COLUMN `price` DECIMAL(8,2) UNSIGNED AFTER `display_name`;";
						Zymurgy::$db->query($sql)
							or die("Could not upgrade zcm_media_file table: ".mysql_error());

						$sql = "ALTER TABLE `zcm_media_package` ".
							"ADD COLUMN `price` DECIMAL(8,2) UNSIGNED AFTER `display_name`;";
						Zymurgy::$db->query($sql)
							or die("Could not upgrade zcm_media_package table: ".mysql_error());
						break;

					case 5:
						$sql = "ALTER TABLE `zcm_media_relation` ".
							"ADD COLUMN `thumbnails` VARCHAR(50) AFTER `relation_type_label`;";
						Zymurgy::$db->query($sql)
							or die("Could not upgrade zcm_media_relation table: ".mysql_error());
						break;

					default:
						die("Unsupported version $version");
				}
			}
		}

		static function Uninstall()
		{
			$sql = "DROP TABLE `zcm_media_package_type_allowed_relation`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE `zcm_media_package_type`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE `zcm_media_file_package`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE `zcm_media_package`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE `zcm_media_file_relation`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE `zcm_media_file`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE `zcm_media_relation`";
			Zymurgy::$db->query($sql);

			$sql = "DROP TABLE `zcm_media_restriction`";
			Zymurgy::$db->query($sql);
		}
	}

	class MediaFileView
	{
		static function DisplayList($mediaFiles)
		{
			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Display Name</td>");
			echo("<td>Content Type</td>");
			echo("<td>MIME Type</td>");
			echo("<td>Owner</td>");
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
					$mediaFile->get_media_file_id()."\">Download</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Delete</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"6\"><a style=\"color: white;\" href=\"media.php?action=add_media_file\">Add Media File</td>");

			echo("</table>");

			include("footer.php");
		}

		static function DisplayEditForm($mediaFile, $mediaRelations, $members, $action)
		{
			include("header.php");
			include('datagrid.php');

			echo("<script type=\"text/javascript\">\n");
			echo("function mf_aspectcrop_popup(media_file_id, thumbnail) {\n");
			echo("url = 'media.php?action=edit_media_file_thumbnail&media_file_id={0}&thumbnail={1}';\n");
			echo("url = url.replace(\"{0}\", media_file_id);\n");
			echo("url = url.replace(\"{1}\", thumbnail);\n");
			echo("window.open(url, \"thumber\", \"scrollbars=no,width=780,height=500\");\n");
			echo("}\n");
			echo("</script>\n");

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
			echo("<td>Display Name:</td>");
			echo("<td>");
			$widget->Render("input.30.100", "display_name", $mediaFile->get_display_name());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			echo("<td>Price:</td>");
			echo("<td>");
			$widget->Render("input.10.9", "price", $mediaFile->get_price());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			echo("<td>Owner:</td>");
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
			echo("<td>Content Type:</td>");
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
			echo("<td>File:</td>");
			echo("<td>");
			$widget->Render("attachment", "file", "");
			echo("</td>");
			echo("</tr>");

			if($action == "act_edit_media_file")
			{
				echo("<tr>");
				echo("<td>&nbsp;</td>");
				echo("<td>Only provide a file if you want to replace the one currently in the system.</td>");
				echo("</tr>");
			}

			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

			echo("<tr>");
			echo("<td>mime-type:</td>");
			echo("<td>".$mediaFile->get_mimetype()."</td>");
			echo("</tr>");

			if($mediaFile->get_relation() !== null
				&& strlen($mediaFile->get_relation()->get_thumbnails()) > 0)
			{
				echo("<tr>");
				echo("<td valign=\"bottom\">Thumbnails:</td>");
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
				echo("<td valign=\"top\">Related Media:</td>");
				echo("<td>");
				MediaFileView::DisplayRelatedMedia($mediaFile);
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

		static function DisplayRelatedMedia($mediaFile)
		{
			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Display Name</td>");
			echo("<td>MIME Type</td>");
			echo("<td>Owner</td>");
			echo("<td>Relation</td>");
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
					$relatedFile->get_media_file_id()."\">Download</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_file_relation&amp;media_file_id=".
					$mediaFile->get_media_file_id().
					"&amp;related_media_file_id=".
					$relatedFile->get_media_file_id().
					"\">Delete</a></td>");
				echo("</tr>");
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"6\"><a style=\"color: white;\" href=\"media.php?action=add_media_file_relatedmedia&amp;media_file_id=".$mediaFile->get_media_file_id()."\">Add Related Media File</td>");

			echo("</table>");
		}

		static function DisplayDeleteFrom($mediaFile)
		{
			include("header.php");

			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");

			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_media_file\">");
			echo("<input type=\"hidden\" name=\"media_file_id\" value=\"".
				$mediaFile->get_media_file_id()."\">");

			echo("<p>Are you sure you want to delete the following media file:</p>");
			echo("<p>Display Name: ".$mediaFile->get_display_name()."</p>");

			echo("<p>");
			echo("<input style=\"width: 80px; margin-right: 10px;\" type=\"submit\" value=\"Yes\">");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"No\" onclick=\"window.location.href='media.php?action=list_media_files';\">");
			echo("</p>");

			echo("</form>");

			include("footer.php");
		}

		static function DisplayListOfFilesToAdd($mediaFile, $mediaFiles, $mediaRelations)
		{
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
			echo("<td>Display Name</td>");
			echo("<td>MIME Type</td>");
			echo("<td>Owner</td>");
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
					$relatedFile->get_media_file_id()."\">Download</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"5\">&nbsp;</td>");

			echo("</table>");

			echo("<p>Relation: <select name=\"media_relation_id\">");

			foreach($mediaRelations as $mediaRelation)
			{
				echo("<option value=\"".
					$mediaRelation->get_media_relation_id().
					"\">".
					$mediaRelation->get_relation_label().
					"</option>");
			}

			echo("</select></p>");
			echo("<p><input style=\"width: 80px;\" type=\"submit\" value=\"Save\">&nbsp;");
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"Cancel\" onclick=\"window.location.href='media.php?action=edit_media_file&media_file_id=".$mediaFile->get_media_file_id()."';\">&nbsp;</p>");

			include("footer.php");
		}

		static function DisplayThumber($mediaFile, $thumbnail)
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

		static function DisplayThumbCreated()
		{
			echo("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" ");
			echo("\"http://www.w3.org/TR/html4/loose.dtd\">\n");
        	echo("<html>\n");
 			echo("<head>\n");
 			echo("<script type=\"text/javascript\">window.opener.location.reload(); window.close();</script>");
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
		static function DisplayList($mediaPackages)
		{
			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Display Name</td>");
			echo("<td>Package Type</td>");
			echo("<td>Owner</td>");
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
					$mediaPackage->get_media_package_id()."\">Files</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_package&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."\">Delete</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"5\"><a style=\"color: white;\" href=\"media.php?action=add_media_package\">Add Media Package</td>");

			echo("</table>");

			include("footer.php");
		}

		static function DisplayEditForm(
			$mediaPackage,
			$members,
			$packageTypes,
			$action)
		{
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
			echo("<td>Display Name:</td>");
			echo("<td>");
				$widget->Render("input.30.100", "display_name", $mediaPackage->get_display_name());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			echo("<td>Price:</td>");
			echo("<td>");
				$widget->Render("input.10.9", "price", $mediaPackage->get_price());
			echo("</td>");
			echo("</tr>");

			echo("<tr>");
			echo("<td>Owner:</td>");
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
			echo("<td>Package Type:</td>");
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
			echo("<td><input type=\"submit\" value=\"Save\"></td>");
			echo("</tr>");

			echo("</table>");
			echo("</form>");

			include("footer.php");
		}

		static function DisplayDeleteFrom($mediaPackage)
		{
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
			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Media File Name</td>");
			echo("<td>Owner</td>");
			echo("<td>Relation</td>");
			echo("<td colspan=\"3\">&nbsp;</td>");
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
				echo("<td><a href=\"media.php?action=delete_media_package_file&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Delete</a></td>");
				echo("</tr>");
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"6\"><a style=\"color: white;\" href=\"media.php?action=add_media_package_file&amp;media_package_id=".$mediaPackage->get_media_package_id()."\">Add Media File to Package</td>");

			echo("</table>");

			include("footer.php");
		}

		static function DisplayListOfFilesToAdd($mediaPackage, $mediaFiles, $mediaRelations)
		{
			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

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
			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Package Type</td>");
			echo("<td>Label</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaPackageTypes as $mediaPackageType)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><a href=\"media.php?action=edit_media_package_type&amp;media_package_type_id=".
					$mediaPackageType->get_media_package_type_id()."\">".$mediaPackageType->get_package_type()."</a></td>");
				echo("<td>".$mediaPackageType->get_package_label()."</td>");
				echo("<td><a href=\"media.php?action=delete_media_package_type&amp;media_package_type_id=".
					$mediaPackageType->get_media_package_type_id()."\">Delete</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"3\"><a style=\"color: white;\" href=\"media.php?action=add_media_package_type\">Add Media Package Type</td>");

			echo("</table>");

			include("footer.php");
		}

		public static function DisplayEditForm($mediaPackageType, $action)
		{
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
			include("header.php");
			include('datagrid.php');

			DumpDataGridCSS();

			echo("<table class=\"DataGrid\" rules=\"cols\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"#000000\" border=\"1\">");
			echo("<tr class=\"DataGridHeader\">");
			echo("<td>Type</td>");
			echo("<td>Label</td>");
			echo("<td>&nbsp;</td>");
			echo("</tr>");

			$cntr = 1;

			foreach($mediaRelations as $mediaRelation)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><a href=\"media.php?action=edit_relation&amp;media_relation_id=".
					urlencode($mediaRelation->get_media_relation_id())."\">".
					$mediaRelation->get_relation_type()."</td>");
				echo("<td>".$mediaRelation->get_relation_label()."</td>");
				echo("<td><a href=\"media.php?action=delete_relation&amp;media_relation_id=".
					urlencode($mediaRelation->get_media_relation_id())."\">Delete</a></td>");
				echo("</tr>");

				$cntr++;
			}

			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"3\"><a style=\"color: white;\" href=\"media.php?action=add_relation\">Add Relation Type</td>");

			echo("</table>");

			include("footer.php");
		}

		static function DisplayEditForm($mediaRelation, $action)
		{
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

	class MediaController
	{
		function Execute($action)
		{
			// ZK: The Installer actions are disabled by default.
			// If you are working on the component and need to reset the
			// table definitions, uncomment the two lines below, and use
			// the following URLs:
			//
			// (ZymurgyRoot)/media.php?action=install
			// (ZymurgyRoot)/media.php?action=uninstall

			// if($this->Execute_InstallerActions($action)) {}
			// else
			if($this->Execute_MediaFileActions($action)) {}
			else if($this->Execute_MediaPackageActions($action)) {}
			else if($this->Execute_RelationActions($action)) {}
			else if($this->Execute_MediaPackageTypeActions($action)) {}
			else
			{
				die("Unsupported action ".$action);
			}
		}

		private function Execute_InstallerActions($action)
		{
			switch($action)
			{
				case "install":
					MediaFileInstaller::Install();
					return true;

				case "uninstall":
					MediaFileInstaller::Uninstall();
					return true;

				default:
					return false;
			}
		}

		private function Execute_MediaFileActions($action)
		{
			switch($action)
			{
				case "list_media_files":
					$mediaFiles = MediaFilePopulator::PopulateAll();
					MediaFileView::DisplayList($mediaFiles);
					return true;

				case "add_media_file":
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

					return true;

				case "edit_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					MediaFilePopulator::PopulateRelatedMedia($mediaFile);
					$mediaRelations = MediaRelationPopulator::PopulateAll();
					$members = MediaMemberPopulator::PopulateAll();

					MediaFileView::DisplayEditForm(
						$mediaFile,
						$mediaRelations,
						$members,
						"act_edit_media_file");

					return true;

				case "act_add_media_file":
				case "act_edit_media_file":
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
					return true;

				case "delete_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					MediaFileView::DisplayDeleteFrom($mediaFile);
					return true;

				case "act_delete_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_POST["media_file_id"]);
					MediaFilePopulator::DeleteMediaFile(
						$mediaFile);
					header("Location: media.php?action=list_media_files");
					return true;

				case "add_media_file_relatedmedia":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					$mediaFiles = MediaFilePopulator::PopulateAllNotRelated(
						$mediaFile);
					$mediaRelations = MediaRelationPopulator::PopulateAll();
					MediaFileView::DisplayListOfFilesToAdd($mediaFile, $mediaFiles, $mediaRelations);
					return true;

				case "act_add_media_file_relatedmedia":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_POST["media_file_id"]);

					MediaFilePopulator::AddRelatedMedia(
						$_POST["media_file_id"],
						$_POST["related_media_file_id"],
						$_POST["media_relation_id"]);

					header(
						"Location: media.php?action=edit_media_file&media_file_id=".
						$_POST["media_file_id"]);

					return true;

				case "delete_media_file_relation":
					MediaFilePopulator::DeleteRelatedMedia(
						$_GET["media_file_id"],
						$_GET["related_media_file_id"]);
					header(
						"Location: media.php?action=edit_media_file&media_file_id=".
						$_GET["media_file_id"]);
					return true;

				case "edit_media_file_thumbnail":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					MediaFilePopulator::InitializeThumbnail(
						$mediaFile,
						$_GET["thumbnail"]);
					MediaFileView::DisplayThumber(
						$mediaFile,
						$_GET["thumbnail"]);
					return true;

				case "act_edit_media_file_thumbnail":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					MediaFilePopulator::MakeThumbnail(
						$mediaFile,
						$_GET["thumbnail"]);
					MediaFileView::DisplayThumbCreated();

					return true;

				case "download_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					$fileContent = MediaFilePopulator::GetFilestream(
						$mediaFile);
					MediaFileView::DownloadMediaFile($mediaFile, $fileContent);
					return true;

				case "stream_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					$fileContent = MediaFilePopulator::GetFilestream(
						$mediaFile,
						$_GET["suffix"]);
					MediaFileView::StreamMediaFile($mediaFile, $fileContent);
					return true;

				default:
					return false;
			}
		}

		private function Execute_MediaPackageActions($action)
		{
			switch($action)
			{
				case "list_media_packages":
					$mediaPackages = MediaPackagePopulator::PopulateAll();
					MediaPackageView::DisplayList($mediaPackages);
					return true;

				case "add_media_package":
					$mediaPackage = new MediaPackage();
					$members = MediaMemberPopulator::PopulateAll();
					$mediaPackage->set_member($members[0]);
					$packageTypes = MediaPackageTypePopulator::PopulateAll();

					MediaPackageView::DisplayEditForm(
						$mediaPackage,
						$members,
						$packageTypes,
						"act_add_media_package");

					return true;

				case "edit_media_package":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					$members = MediaMemberPopulator::PopulateAll();
					$packageTypes = MediaPackageTypePopulator::PopulateAll();

					MediaPackageView::DisplayEditForm(
						$mediaPackage,
						$members,
						$packageTypes,
						"act_edit_media_package");

					return true;

				case "act_add_media_package":
				case "act_edit_media_package":
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
					return true;

				case "delete_media_package":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					MediaPackageView::DisplayDeleteFrom($mediaPackage);
					return true;

				case "act_delete_media_package":
					MediaPackagePopulator::DeleteMediaPackage(
						$_POST["media_package_id"]);
					header("Location: media.php?action=list_media_packages");
					return true;

				case "list_media_package_files":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					MediaPackagePopulator::PopulateMediaFiles($mediaPackage);

					// echo("<pre>");
					// print_r($mediaPackage);
					// echo("</pre>");
					// echo($mediaPackage->get_media_file_count());
					// die();

					MediaPackageView::DisplayRelatedMedia($mediaPackage);
					return true;

				case "add_media_package_file":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					$mediaFiles = MediaFilePopulator::PopulateAllNotInPackage(
						$mediaPackage);
					$mediaRelations = MediaRelationPopulator::PopulateAll();
					MediaPackageView::DisplayListOfFilesToAdd($mediaPackage, $mediaFiles, $mediaRelations);
					return true;

				case "act_add_media_package_file":
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

					return true;

				case "move_media_package_file_up":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					MediaPackagePopulator::PopulateMediaFiles($mediaPackage);
						$mediaPackage->MoveMediaFile($_GET["media_file_id"], -1);
					MediaPackagePopulator::SaveMediaFileOrder($mediaPackage);
					header(
						"Location: media.php?action=list_media_package_files&media_package_id=".
						$_GET["media_package_id"]);
					return true;

				case "move_media_package_file_down":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					MediaPackagePopulator::PopulateMediaFiles($mediaPackage);
					$mediaPackage->MoveMediaFile($_GET["media_file_id"], 1);
					MediaPackagePopulator::SaveMediaFileOrder($mediaPackage);
					header(
						"Location: media.php?action=list_media_package_files&media_package_id=".
						$_GET["media_package_id"]);
					return true;

				case "delete_media_package_file":
					MediaPackagePopulator::DeleteMediaFileFromPackage(
						$_GET["media_package_id"],
						$_GET["media_file_id"]);
					header(
						"Location: media.php?action=list_media_package_files&media_package_id=".
						$_GET["media_package_id"]);
					return true;

				case "download_media_package":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					MediaPackagePopulator::BuildZipFile($mediaPackage, false);
					MediaPackageView::DownloadMediaPackage($mediaPackage);

					break;

				default:
					return false;
			}
		}

		private function Execute_RelationActions($action)
		{
			switch($action)
			{
				case "list_relations":
					$relations = MediaRelationPopulator::PopulateAll();
					MediaRelationView::DisplayList($relations);
					return true;

				case "add_relation":
					$relation = new MediaRelation();
					MediaRelationView::DisplayEditForm($relation, "act_add_relation");
					return true;

				case "edit_relation":
					$relation = MediaRelationPopulator::PopulateByID(
						$_GET["media_relation_id"]);
					MediaRelationView::DisplayEditForm($relation, "act_add_relation");
					return true;

				case "act_add_relation":
				case "act_edit_relation":
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
					return true;

				case "delete_relation":
					$relation = MediaRelationPopulator::PopulateByID(
						$_GET["media_relation_id"]);
					MediaRelationView::DisplayDeleteFrom($relation);
					return true;

				case "act_delete_relation":
					MediaRelationPopulator::DeleteRelation(
						$_POST["media_relation_id"]);
					header("Location: media.php?action=list_relations");
					return true;

				default:
					return false;
			}
		}

		private function Execute_MediaPackageTypeActions($action)
		{
			switch($action)
			{
				case "list_media_package_types":
					$packageTypes = MediaPackageTypePopulator::PopulateAll();
					MediaPackageTypeView::DisplayList($packageTypes);
					return true;

				case "add_media_package_type":
					$packageType = new MediaPackageType();
					MediaPackageTypeVieW::DisplayEditForm($packageType, "act_add_media_package_type");
					return true;

				case "edit_media_package_type":
					$packageType = MediaPackageTypePopulator::PopulateByID(
						$_GET["media_package_type_id"]);
					MediaPackageTypePopulator::PopulateAllowedRelations($packageType);
					MediaPackageTypeVieW::DisplayEditForm($packageType, "act_edit_media_package_type");
					return true;

				case "act_add_media_package_type":
				case "act_edit_media_package_type":
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
					return true;

				case "delete_media_package_type":
					$packageType = MediaPackageTypePopulator::PopulateByID(
						$_GET["media_package_type_id"]);
					MediaPackageTypeVieW::DisplayDeleteForm($packageType);
					return true;

				case "act_delete_media_package_type":
					MediaPackageTypePopulator::DeleteMediaPackageType(
						$_POST["media_package_type_id"]);
					header("Location: media.php?action=list_media_package_types");
					return true;

				case "add_media_package_type_allowed_relation":
					$packageType = MediaPackageTypePopulator::PopulateByID(
						$_GET["media_package_type_id"]);
					$allowedRelation = new MediaPackageTypeAllowedRelation();
					$mediaRelations = MediaRelationPopulator::PopulateAll();
					MediaPackageTypeView::DisplayEditAllowedRelationForm(
						$allowedRelation,
						$packageType,
						$mediaRelations,
						"act_add_media_package_type_allowed_relation");
					return true;

				case "edit_media_package_type_allowed_relation":
					$allowedRelation = MediaPackageTypePopulator::PopulateAllowedRelationByID(
						$_GET["allowed_relation_id"]);
					$packageType = MediaPackageTypePopulator::PopulateByID(
						$allowedRelation->get_media_package_type_id());
					$mediaRelations = MediaRelationPopulator::PopulateAll();
					MediaPackageTypeView::DisplayEditAllowedRelationForm(
						$allowedRelation,
						$packageType,
						$mediaRelations,
						"act_edit_media_package_type_allowed_relation");
					return true;

				case "act_add_media_package_type_allowed_relation":
				case "act_edit_media_package_type_allowed_relation":
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

					return true;

				case "delete_media_package_type_allowed_relation":
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

					return true;

				default:
					return false;
			}
		}
	}
?>