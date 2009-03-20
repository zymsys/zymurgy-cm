<?php
	class MediaFileInstaller
	{
		static function Install()
		{
			$sql = "CREATE TABLE `zcm_media_restriction` (".
				"`media_restriction_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
				"`download_limit` INTEGER UNSIGNED NOT NULL,".
				"`day_limit` INTEGER UNSIGNED NOT NULL,".
				"PRIMARY KEY (`media_restriction_id`)".
				") ENGINE = InnoDB;";
			Zymurgy::$db->query($sql) 
				or die("Could not create zcm_media_restriction table: ".mysql_error());
				
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
				"`relation_type` VARCHAR(50) NOT NULL,".
				"PRIMARY KEY (`media_file_package_id`)".
				") ENGINE = InnoDB;";
			Zymurgy::$db->query($sql) 
				or die("Could not create zcm_media_file_package table: ".mysql_error());
		}
		
		static function Uninstall()
		{
			$sql = "DROP TABLE `zcm_media_file_package`";
			Zymurgy::$db->query($sql); 
			//	or die("Could not drop zcm_media_file_package table: ".mysql_error());
			
			$sql = "DROP TABLE `zcm_media_package`";
			Zymurgy::$db->query($sql); 
			//	or die("Could not drop zcm_media_package table: ".mysql_error());

			$sql = "DROP TABLE `zcm_media_file`";
			Zymurgy::$db->query($sql); 
			//	or die("Could not drop zcm_media_file table: ".mysql_error());
			
			$sql = "DROP TABLE `zcm_media_restriction`";
			Zymurgy::$db->query($sql); 
			//	or die("Could not drop zcm_media_restriction table: ".mysql_error());
		}
	}

	class MediaFile
	{
		private $m_media_file_id;
		private $m_mimetype;
		private $m_extension;
		private $m_display_name;
		
		private $m_disporder;
		private $m_relation_type;
		
		private $m_restriction;
		private $m_member;
		
		private $m_relatedmedia = array();
		
		private $m_file;
		private $m_filestream;
		
		private $m_errors = array();
		
		function MediaFile()
		{
			$this->m_member = new MediaMember();
		}
		
		public function get_media_file_id()
		{
			return $this->m_media_file_id;
		}
		
		public function set_media_file_id($newValue)
		{
			$this->m_media_file_id = $newValue;
		}
		
		public function get_member()
		{
			return $this->m_member;
		}
		
		public function set_member($newValue)
		{
			$this->m_member = $newValue;
		}
		
		public function get_mimetype()
		{
			return $this->m_mimetype;
		}
		
		public function set_mimetype($newValue)
		{
			$this->m_mimetype = $newValue;
		}
		
		public function get_extension()
		{
			return $this->m_extension;
		}
		
		public function set_extension($newValue)
		{
			$this->m_extension = $newValue;
		}
		
		public function get_display_name()
		{
			return $this->m_display_name;
		}
		
		public function set_display_name($newValue)
		{
			$this->m_display_name = $newValue;
		}
		
		public function get_disporder()
		{
			return $this->m_disporder;
		}
		
		public function set_disporder($newValue)
		{
			$this->m_disporder = $newValue;
		}
		
		public function get_relation_type()
		{
			return $this->m_relation_type;
		}
		
		public function set_relation_type($newValue)
		{
			$this->m_relation_type = $newValue;
		}
		
		public function get_restriction()
		{
			return $this->m_restriction;
		}
		
		public function set_restriction($newValue)
		{
			$this->m_restriction = $newValue;
		}
		
		public function get_relatedmedia($key)
		{
			return $this->m_relatedmedia[$key];
		}
		
		public function get_relatedmedia_count()
		{
			return count($this->m_relatedmedia);
		}
		
		public function add_relatedmedia($newValue)
		{
			$this->m_relatedmedia[] = $newValue;
			
			return count($this->m_relatedmedia) - 1;
		}
		
		public function delete_relatedmedia($key)
		{
			unset($this->m_relatedmedia[key]);
		}
		
		public function get_file()
		{
			return $this->m_file;
		}
			
		public function set_file($fileObject)
		{
			$this->m_file = $fileObject;
		}
		
		public function get_filestream()
		{
			return $this->m_filestream;
		}
		
		public function set_filestream($newValue)
		{
			$this->m_filestream = $newValue;
		}
		
		public function get_errors()
		{
			return $this->m_errors;
		}
		
		public function validate($action)
		{
			$isValid = true;
			
			if(strlen($this->m_display_name) <= 0)
			{
				$this->m_errors[] = "Display Name is required.";				
				$isValid = false;
			}
			
			if($action == "act_add_media_file")
			{
				if(!isset($this->m_file))
				{
					$this->m_errors[] = "A file must be provided.";
					$isValid = false;
				}
			}
			
			if(isset($this->m_file))
			{
				$isValid = $this->validateFile() ? $isValid : false;
			}
		
			return $isValid;
		}
		
		public function validateFile()
		{
			$isValid = true;

			switch ($this->m_file['error'])
			{
			   	case UPLOAD_ERR_OK:
			   	case UPLOAD_ERR_NO_FILE:
			       	break;
			       	
			   	case UPLOAD_ERR_INI_SIZE:
			       	$this->m_errors[] = ("The uploaded file exceeds the upload_max_filesize directive (".ini_get("upload_max_filesize").") in php.ini.");
					$isValid = false;					
			       	break;
			       	
			   	case UPLOAD_ERR_FORM_SIZE:
			       	$this->m_errors[] = ("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
					$isValid = false;					
			       	break;
			       	
			   	case UPLOAD_ERR_PARTIAL:
			       	$this->m_errors[] = ("The uploaded file was only partially uploaded.");
					$isValid = false;					
			       	break;
			       	
			   	case UPLOAD_ERR_NO_TMP_DIR:
			       	$this->m_errors[] = ("Missing a temporary folder.");
					$isValid = false;					
			       	break;
			       	
			   	case UPLOAD_ERR_CANT_WRITE:
			       	$this->m_errors[] = ("Failed to write file to disk");
					$isValid = false;					
			       	break;
			       	
			   	default:
			       	$this->m_errors[] = ("Unknown File Error");
					$isValid = false;					
			}

			return $isValid;		
		}
	}
	
	class MediaFilePopulator
	{
		static function PopulateAll()
		{
			$sql = "SELECT `media_file_id`, `member_id`, `mimetype`, `extension`, ".
				"`display_name`, `media_restriction_id` FROM `zcm_media_file`";
				
			$ri = Zymurgy::$db->query($sql) or die();
			$mediaFiles = array();
			
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaFile = new MediaFile();
				
				$mediaFile->set_media_file_id($row["media_file_id"]);
				$mediaFile->set_mimetype($row["mimetype"]);
				$mediaFile->set_extension($row["extension"]);
				$mediaFile->set_display_name($row["display_name"]);

				$member = MediaMemberPopulator::PopulateByID(
					$row["member_id"]);
				$mediaFile->set_member($member);
					
				$restriction = MediaRestrictionPopulator::PopulateByID(
					$row["media_restriction_id"]);				
				$mediaFile->set_restriction($restriction);
				
				$mediaFiles[] = $mediaFile;
			}
			
			return $mediaFiles;
		}
		
		static function PopulateByID(
			$media_file_id)
		{
			$sql = "SELECT `member_id`, `mimetype`, `extension`, `display_name`, ".
				"`media_restriction_id` FROM `zcm_media_file` WHERE `media_file_id` = '".
				mysql_escape_string($media_file_id)."'";
				
			$ri = Zymurgy::$db->query($sql) or die();
			
			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaFile = new MediaFile();
				
				$mediaFile->set_media_file_id($media_file_id);
				$mediaFile->set_mimetype($row["mimetype"]);
				$mediaFile->set_extension($row["extension"]);
				$mediaFile->set_display_name($row["display_name"]);

				$member = MediaMemberPopulator::PopulateByID(
					$row["member_id"]);
				$mediaFile->set_member($member);
				
				$restriction = MediaRestrictionPopulator::PopulateByID(
					$row["media_restriction_id"]);				
				$mediaFile->set_restriction($restriction);
				
				return $mediaFile;
			}
		}
		
		static function PopulateRelatedMedia(
			$base_media_file)
		{
			$sql = "SELECT `related_media_file_id`, `relation_type` FROM ".
			"`media_file_relation` WHERE `media_file_id` = '".
			mysql_escape_string($base_media_file->get_media_file_id())."'";
				
			$ri = Zymurgy::$db->query($sql) or die();
			
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{ 
				$media_file = MediaFilePopulator::PopulateByID(
					$row["related_media_file_id"]);
				$media_file->set_relation_type($row["relation_type"]);
				
				$base_media_file->add_relatedmedia($media_file);
			}
		}
		
		static function PopulateFromForm()
		{
			$mediaFile = new MediaFile();
			
			$mediaFile->set_media_file_id($_POST["media_file_id"]);
			$mediaFile->set_mimetype($_POST["mimetype"]);
			$mediaFile->set_extension($_POST["extension"]);
			$mediaFile->set_member(
				MediaMemberPopulator::PopulateByID($_POST["member_id"]));
			$mediaFile->set_display_name($_POST["display_name"]);
			
			// print_r($_FILES);
			
			if($_FILES)
			{
				// echo("File found.");
				
				$mediaFile->set_file($_FILES['file']);
				
			}
			
			return $mediaFile;
		}
		
		static function GetFilestream($mediaFile)
		{
			$uploadfolder = Zymurgy::$config["Media File Local Path"];			
			$filepath = $uploadfolder.
				"/".
				$mediaFile->get_media_file_id().
				".".
				$mediaFile->get_extension();		
			
			$file = fopen($filepath, "r");
			$content = fread($file, filesize($filepath));			
			fclose($file);
			
			return $content;
		}
		
		static function SaveMediaFile($mediaFile)
		{
			$uploadfolder = Zymurgy::$config["Media File Local Path"];			
			$file = $mediaFile->get_file();
			$uploadingFile = $file["error"] == UPLOAD_ERR_NO_FILE ? false : true;
			$pathinfo = null;
			$extension = "";
			
			if($uploadingFile)
			{
				$pathinfo = pathinfo($file["name"]);
				$extension = $pathinfo["extension"];
				
				$mediaFile->set_mimetype($file["type"]);
				$mediaFile->set_extension($extension);
			}
			
			$sql = "";
			
			if($mediaFile->get_media_file_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_file` ( `member_id`, `mimetype`, `extension`, ".
					"`display_name` ) VALUES ( '".
					mysql_escape_string($mediaFile->get_member()->get_member_id())."', '".
					mysql_escape_string($mediaFile->get_mimetype())."', '".
					mysql_escape_string($mediaFile->get_extension())."', '".
					mysql_escape_string($mediaFile->get_display_name())."' )";
					
				Zymurgy::$db->query($sql) or die("Could not insert media file record: ".mysql_error());
				
				$sql = "SELECT MAX(`media_file_id`) FROM `zcm_media_file`";
				
				$mediaFile->set_media_file_id(
					Zymurgy::$db->get($sql));
			}
			else 
			{
				$sql = "UPDATE `zcm_media_file` SET ".
					"`member_id` = '".mysql_escape_string($mediaFile->get_member()->get_member_id())."', ".
					"`mimetype` = '".mysql_escape_string($mediaFile->get_mimetype())."', ".
					"`extension` = '".mysql_escape_string($mediaFile->get_extension())."', ".
					"`display_name` = '".mysql_escape_string($mediaFile->get_display_name())."' ".
					"WHERE `media_file_id` = '".mysql_escape_string($mediaFile->get_media_file_id())."'";
					
				Zymurgy::$db->query($sql) or die("Could not update media file record: ".mysql_error());
			}			
						
			if($uploadingFile)
			{
				$newPath = $uploadfolder."/".$mediaFile->get_media_file_id().".".$extension;
				
				if(move_uploaded_file($file["tmp_name"], $newPath))
				{
					chmod($localfn, 0644);
				}				
			}
		}
		
		public function DeleteMediaFile($media_file_id)
		{
			$sql = "DELETE FROM `zcm_media_file` WHERE `media_file_id` = '".
				mysql_escape_string($media_file_id)."'";
				
			Zymurgy::$db->query($sql) or die("Could not delete media file record: ".mysql_error());
		}
	}
	
	class MediaPackage
	{
		private $m_media_package_id;
		private $m_member;
		private $m_display_name;
		
		private $m_restriction;
		
		private $m_media_files = array();
		
		public function get_media_package_id()
		{
			return $this->m_media_package_id;
		}
		
		public function set_media_package_id($newValue)
		{
			$this->m_media_package_id = $newValue;
		}
		
		public function get_member()
		{
			return $this->m_member;
		}
		
		public function set_member($newValue)
		{
			$this->m_member = $newValue;
		}
		
		public function get_display_name()
		{
			return $this->m_display_name();
		}
		
		public function set_display_name($newValue)
		{
			$this->m_display_name = $newValue;
		}
		
		public function get_restriction()
		{
			return $this->m_restriction;
		}
		
		public function set_restriction($newValue)
		{
			$this->m_restriction = $newValue;
		}
		
		public function get_media_file($key)
		{
			return $this->m_media_files[$key];
		}
		
		public function get_media_file_count()
		{
			return count($this->m_media_files);
		}
		
		public function add_media_file($media_file)
		{
			$this->m_media_files[] = $media_file;
			
			return count($this->m_media_files) - 1;
		}
		
		public function delete_media_file($key)
		{
			unset($this->m_media_files[$key]);
		}
	}
	
	class MediaPackagePopulator
	{
		static function PopulateByID($media_package_id)
		{
			$sql = "SELECT `media_restriction_id`, `display_name` FROM `zcm_media_package` ".
				"WHERE `media_package_id` = '".
				mysql_escape_string($restriction_id)."'";
				
			$ri = Zymurgy::$db->query($sql) or die();
			
			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaPackage = new MediaPackage();
				
				$mediaPackage->set_media_package_id($media_package_id);
				$mediaPackage->set_display_name($row["display_name"]);
				
				$member = MediaMemberPopulator::PopulateByID(
					$row["member_id"]);
				$mediaPackage->set_member($member);
				
				$restriction = MediaRestrictionPopulator::PopulateByID(
					$row["restriction_id"]);				
				$mediaPackage->set_restriction($restriction);
				
				return $mediaPackage;
			}
		}
		
		static function PopulateMediaFiles($mediaPackage)
		{
			$sql = "SELECT `media_file_id`, `disporder`, `relation_type` FROM ".
				"`zcm_media_file_package` WHERE `media_package_id` = '".
				mysql_escape_string($mediaPackage->get_media_package_id).
				"' ORDER BY `disporder`";			
				
			$ri = Zymurgy::$db->query($sql) or die();
			
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaFile = MediaFilePopulator::PopulateByID(
					$row["media_file_id"]);
					
				$mediaFile->set_disporder($row["disporder"]);
				$mediaFile->set_relation_type($row["relation_type"]);
					
				$mediaPackage->add_media_file($mediaFile);
			}
		}
	}
	
	class MediaRestriction
	{
		private $m_restriction_id;
		private $m_download_limit;
		private $m_day_limit;
		
		public function get_restriction_id()
		{
			return $this->m_restriction_id;
		}
		
		public function set_restriction_id($newValue)
		{
			$this->m_restriction_id = $newValue;
		}
		
		public function get_download_limit()
		{
			return $this->m_download_limit;
		}
		
		public function set_download_limit($newValue)
		{
			$this->m_download_limit = $newValue;
		}
		
		public function get_day_limit()
		{
			return $this->m_day_limit();
		}
		
		public function set_day_limit($newValue)
		{
			$this->m_day_limit = $newValue;
		}
	}
	
	class MediaRestrictionPopulator
	{
		static function PopulateByID(
			$restriction_id)
		{
			$sql = "SELECT `download_limit`, `day_limit` FROM `zcm_media_restriction` ".
				"WHERE `media_restriction_id` = '".
				mysql_escape_string($restriction_id)."'";
				
			$ri = Zymurgy::$db->query($sql) or die();
			
			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaRestriction = new MediaRestriction();
				
				$mediaRestriction->set_restriction_id($restriction_id);
				$mediaRestriction->set_download_limit($row["download_limit"]);
				$mediaRestriction->set_day_limit($row["day_limit"]);
				
				return $mediaRestriction;
			}
		}
	}

	class MediaMember
	{
		private $m_member_id;		
		private $m_email;
		
		public function get_member_id()
		{
			return $this->m_member_id;
		}
		
		public function set_member_id($newValue)
		{
			$this->m_member_id = $newValue;
		}
		
		public function get_email()
		{
			return $this->m_email;
		}
		
		public function set_email($newValue)
		{
			$this->m_email = $newValue;
		}
	}
	
	class MediaMemberPopulator
	{
		static function PopulateByID($member_id)
		{
			$sql = "SELECT `email` ".
				"FROM `zcm_member` WHERE `id` = '".
				mysql_escape_string($member_id)."'";
				
			$ri = Zymurgy::$db->query($sql) or die();
			
			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$member = new MediaMember();
				
				$member->set_member_id($member_id);
				$member->set_email($row["email"]);
				
				return $member;
			}
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
				echo("<td>".$mediaFile->get_mimetype()."</td>");
				echo("<td>".$mediaFile->get_member()->get_email()."</td>");
				echo("<td><a href=\"media.php?action=download_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Download</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Delete</a></td>");
				echo("</tr>");
				
				$cntr++;
			}
			
			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"5\"><a style=\"color: white;\" href=\"media.php?action=add_media_file\">Add Media File</td>");
			
			echo("</table>");
			
			include("footer.php");
		}
		
		static function DisplayEditForm($mediaFile, $action)
		{
			include("header.php");
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
			echo("<input type=\"hidden\" name=\"member_id\" value=\"".$mediaFile->get_member()->get_member_id()."\">");
			echo("<table>");
			
			echo("<tr>");
			echo("<td>Display Name:</td>");
			echo("<td>");
			$widget->Render("input.30.100", "display_name", $mediaFile->get_display_name());
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
			
			echo("<tr>");
			echo("<td>Owner:</td>");
			echo("<td>".$mediaFile->get_member()->get_email()."</td>");
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
	}
	
	class MediaController
	{
		function Execute($action)
		{
			switch($action)
			{
				case "install":
					MediaFileInstaller::Install();
					break;
					
				case "uninstall":
					MediaFileInstaller::Uninstall();
					break;
				
				case "list_media_files":
					$mediaFiles = MediaFilePopulator::PopulateAll();					
					MediaFileView::DisplayList($mediaFiles);					
					break;
					
				case "add_media_file":
					$mediaFile = new MediaFile();
					$mediaFile->set_mimetype("n/a");
					$mediaFile->set_member(
						MediaMemberPopulator::PopulateByID(1));
					MediaFileView::DisplayEditForm($mediaFile, "act_add_media_file");
					break;
					
				case "edit_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					MediaFileView::DisplayEditForm($mediaFile, "act_edit_media_file");
					break;
					
				case "act_add_media_file":
				case "act_edit_media_file":
					$mediaFile = MediaFilePopulator::PopulateFromForm();
						
					if(!$mediaFile->validate($action))
					{
						MediaFileView::DisplayEditForm($mediaFile, $action);
					}
					else 
					{
						if(MediaFilePopulator::SaveMediaFile($mediaFile))
						{
							MediaFileView::DisplayEditForm($mediaFile, $action);
						}
						else 
						{
							header("Location: media.php?action=list_media_files");	
						}
					}
					break;
					
				case "delete_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					MediaFileView::DisplayDeleteFrom($mediaFile);
					break;
					
				case "act_delete_media_file":
					MediaFilePopulator::DeleteMediaFile(
						$_POST["media_file_id"]);
					header("Location: media.php?action=list_media_files");	
					
				case "download_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					$fileContent = MediaFilePopulator::GetFilestream(
						$mediaFile);
					MediaFileView::DownloadMediaFile($mediaFile, $fileContent);
					break;
					
				default:
					die("Unsupported action ".$action);
			}
		}
	}
?>