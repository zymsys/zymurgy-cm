<?php
	class MediaFileInstaller
	{
		static function InstalledVersion()
		{
			$sql = "show tables like 'zcm_media_file'";
			$tableExists = Zymurgy::$db->get($sql);
	
			if($tableExists == 'zcm_media_file')
			{
				$sql = "show columns from `zcm_media_file` like 'media_relation_id'";
				$fieldExists = Zymurgy::$db->get($sql);
	
				if($fieldExists == 'media_relation_id')
				{
					return 2;
				}
				else
				{
					return 1;
				}
			}
			else
			{
				return 0;
			}
		}
	
		static function Version()
		{
			return 2;
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
	
					default:
						die("Unsupported version");
				}
			}
		}
	
		static function Uninstall()
		{
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
	
	class MediaFile
	{
		private $m_media_file_id;
		private $m_mimetype;
		private $m_extension;
		private $m_display_name;
	
		private $m_disporder;
	
		private $m_restriction;
		private $m_relation;
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
			$this->m_display_name = str_replace("\\'", "'", $newValue);
		}
	
		public function get_disporder()
		{
			return $this->m_disporder;
		}
	
		public function set_disporder($newValue)
		{
			$this->m_disporder = $newValue;
		}
	
		public function get_restriction()
		{
			return $this->m_restriction;
		}
	
		public function set_restriction($newValue)
		{
			$this->m_restriction = $newValue;
		}
	
		public function get_relation()
		{
			return $this->m_relation;
		}
	
		public function set_relation($newValue)
		{
			$this->m_relation = $newValue;
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
			$continueProcessing = true;
	
			switch ($this->m_file['error'])
			{
				case UPLOAD_ERR_OK:
					break;
	
				case UPLOAD_ERR_NO_FILE:
					$continueProcessing = false;
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
			
			if($continueProcessing)
			{
				if(!($this->m_relation->isValidMimetype($this->m_file['type'])))
				{
					$this->m_errors[] = ("The selected file is not in the correct format for the selected content type. The following mimetypes are supported: ".$this->m_relation->get_allowed_mimetypes().".");
					$isValid = false;
				}				
			}

			return $isValid;
		}
	}
	
	class MediaFilePopulator
	{
		static function PopulateAll()
		{
			return MediaFilePopulator::PopulateMultiple("1 = 1");
		}
	
		static function PopulateByOwner($member_id)
		{
			return MediaFilePopulator::PopulateMultiple(
				"`zcm_media_file`.`member_id` = '".
				mysql_escape_string($member_id).
				"'");
		}
	
		static function PopulateAllNotInPackage(
			$mediaPackage,
			$mediaRelationType = "")
		{
			$relationCriteria = "1 = 1";
			
			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);				
				$relationCriteria = "`media_relation_id` = '".
					mysql_escape_string($mediaRelation->get_media_relation_id())."'";
			}
			
			$criteria = "NOT EXISTS(SELECT 1 FROM `zcm_media_file_package` WHERE ".
				"`zcm_media_file_package`.`media_file_id` = `zcm_media_file`.`media_file_id` AND ".
				"`zcm_media_file_package`.`media_package_id` = '".
				mysql_escape_string($mediaPackage->get_media_package_id())."') AND $relationCriteria";
			
			return MediaFilePopulator::PopulateMultiple($criteria);
		}
	
		static function PopulateAllNotRelated($mediaFile)
		{
			return MediaFilePopulator::PopulateMultiple(
				"`media_file_id` <> '".
				mysql_escape_string($mediaFile->get_media_file_id()).
				"' AND NOT EXISTS(SELECT 1 FROM `zcm_media_file_relation` WHERE ".
				"`zcm_media_file_relation`.`related_media_file_id` = `zcm_media_file`.`media_file_id` AND ".
				"`zcm_media_file_relation`.`media_file_id` = '".
				mysql_escape_string($mediaFile->get_media_file_id()).
				"')");
		}
	
		private static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `media_file_id`, `member_id`, `mimetype`, `extension`, ".
				"`display_name`, `media_restriction_id`, `media_relation_id` ".
				"FROM `zcm_media_file` WHERE $criteria";
	
			// die($sql);
	
			$ri = Zymurgy::$db->query($sql) or die("Could not select list of media files: ".mysql_error());
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
				
				if(is_numeric($row["media_relation_id"]))
				{
					$relation = MediaRelationPopulator::PopulateByID(
						$row["media_relation_id"]);
					$mediaFile->set_relation($relation);					
				}
				else 
				{
					$relation = new MediaRelation();
					$mediaFile->set_relation($relation);
				}	
	
				$mediaFiles[] = $mediaFile;
			}
			
			// print_r($mediaFiles);
			// die();
						
			return $mediaFiles;
		}
	
		static function PopulateByID(
		$media_file_id)
		{
			$sql = "SELECT `member_id`, `mimetype`, `extension`, `display_name`, ".
				"`media_restriction_id`, `media_relation_id` FROM `zcm_media_file` ".
				"WHERE `media_file_id` = '".
				mysql_escape_string($media_file_id)."'";
	
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve media file record: ".mysql_error().", $sql");
	
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
	
				$relation = MediaRelationPopulator::PopulateByID(
				$row["media_relation_id"]);
				$mediaFile->set_relation($relation);
	
				return $mediaFile;
			}
		}
	
		static function PopulateRelatedMedia(
			$base_media_file,
			$mediaRelationType = '')
		{
			$criteria = "1 = 1";
			
			if($mediaRelationType !== '')
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);				
				$criteria = "`media_relation_id` = '".
					mysql_escape_string($mediaRelation->get_media_relation_id())."'";
			}
			
			$sql = "SELECT `related_media_file_id`, `media_relation_id` FROM ".
				"`zcm_media_file_relation` WHERE `media_file_id` = '".
				mysql_escape_string($base_media_file->get_media_file_id())."' ".
				"AND $criteria";
	
			// die($sql);
	
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve related media: ".mysql_error().", $sql");
	
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaFile = MediaFilePopulator::PopulateByID(
					$row["related_media_file_id"]);
	
				$relation = MediaRelationPopulator::PopulateByID(
					$row["media_relation_id"]);
				$mediaFile->set_relation($relation);
	
				$base_media_file->add_relatedmedia($mediaFile);
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
			
			$mediaRelation = MediaRelationPopulator::PopulateByID(
				$_POST["media_relation_id"]);
			$mediaFile->set_relation($mediaRelation);
			
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
	
		static function SaveMediaFile(&$mediaFile)
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
					"`display_name`, `media_relation_id` ) VALUES ( '".
					mysql_escape_string($mediaFile->get_member()->get_member_id())."', '".
					mysql_escape_string($mediaFile->get_mimetype())."', '".
					mysql_escape_string($mediaFile->get_extension())."', '".
					mysql_escape_string($mediaFile->get_display_name())."', '".
					mysql_escape_string($mediaFile->get_relation()->get_media_relation_id())."' )";
	
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
					"`display_name` = '".mysql_escape_string($mediaFile->get_display_name())."', ".
					"`media_relation_id` = '".mysql_escape_string($mediaFile->get_relation()->get_media_relation_id())."' ".				
					"WHERE `media_file_id` = '".mysql_escape_string($mediaFile->get_media_file_id())."'";
	
				Zymurgy::$db->query($sql) or die("Could not update media file record: ".mysql_error());
			}
	
			if($uploadingFile)
			{
				$newPath = $uploadfolder."/".$mediaFile->get_media_file_id().".".$extension;
	
				if(move_uploaded_file($file["tmp_name"], $newPath))
				{
					chmod($newPath, 0644);
				}
			}
		}
	
		public function DeleteMediaFile($media_file_id)
		{
			$sql = "DELETE FROM `zcm_media_file_package` WHERE `media_file_id` = '".
				mysql_escape_string($media_file_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete media file record from packages: ".mysql_error());
			
			$sql = "DELETE FROM `zcm_media_file` WHERE `media_file_id` = '".
				mysql_escape_string($media_file_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete media file record: ".mysql_error());
		}
	
		public function AddRelatedMedia($media_file_id, $related_media_file_id, $media_relation_id)
		{
			$sql = "INSERT INTO `zcm_media_file_relation` ( `media_file_id`,".
				" `related_media_file_id`, `media_relation_id` ) VALUES ( '".
				mysql_escape_string($media_file_id)."', '".
				mysql_escape_string($related_media_file_id)."', '".
				mysql_escape_string($media_relation_id)."' )";
	
			// die($sql);
	
			Zymurgy::$db->query($sql) or die("Could not add related media: ".mysql_error().", $sql");
		}
	
		public function DeleteRelatedMedia($media_file_id, $related_media_file_id)
		{
			$sql = "DELETE FROM `zcm_media_file_relation` WHERE `media_file_id` = '".
				mysql_escape_string($media_file_id)."' AND `related_media_file_id` = '".
				mysql_escape_string($related_media_file_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete related media: ".mysql_error().", $sql");
		}
	}
	
	class MediaPackage
	{
		private $m_media_package_id;
		private $m_member;
		private $m_display_name;
	
		private $m_restriction;
	
		private $m_media_files = array();
	
		private $m_errors = array();
	
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
			return $this->m_display_name;
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
	
		public function clear_media_files()
		{
			$this->m_media_files = array();
		}
	
		public function get_errors()
		{
			return $this->m_errors;
		}
	
		public function set_errors($newValue)
		{
			$this->m_errors = $newValue;
		}
	
		public function validate($action)
		{
			$isValid = true;
	
			if(strlen($this->m_display_name) <= 0)
			{
				$this->m_errors[] = "Display Name is required.";
				$isValid = false;
			}
	
			return $isValid;
		}
	
		public function MoveMediaFile($mediaFileID, $offset)
		{
			// Just in case the media file disporder has been corrupted,
			// clean it up.
			$this->FixMediaFileOrder();
	
			// Calculate the start and end indexes.
			// If we're moving the file up ($offset = -1), we want to start on the
			// second file and end on the last file.
			// If we're moving the file down ($offset = 1), we want to start on the
			// first file and end on the second-last file.
			$start = $offset > 0 ? 0 : 1;
			$end = count($this->m_media_files) + ($offset > 0 ? -1 : 0);
	
			// NOTE: This loop skips a file on purpose. If it's the
			// ID sent, it can't be moved up any further.
			for($cntr = $start; $cntr < $end; $cntr++)
			{
				if($this->m_media_files[$cntr]->get_media_file_id() == $mediaFileID)
				{
					$this->m_media_files[$cntr]->set_disporder($cntr + 1 + $offset);
					$this->m_media_files[$cntr + $offset]->set_disporder($cntr + 1);
				}
			}
		}
	
		public function FixMediaFileOrder()
		{
			for($cntr = 0; $cntr < count($this->m_media_files); $cntr++)
			{
				$this->m_media_files[$cntr]->set_disporder($cntr + 1);
			}
		}
	}
	
	class MediaPackagePopulator
	{
		static function PopulateAll()
		{
			return MediaPackagePopulator::PopulateMultiple(
				"1 = 1");
		}
	
		static function PopulateByOwner($member_id)
		{
			return MediaPackagePopulator::PopulateMultiple(
				"`zcm_media_package`.`member_id` = '".
				mysql_escape_string($member_id).
				"'");
		}
	
		static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `media_package_id`, `media_restriction_id`, `member_id`, `display_name` ".
				"FROM `zcm_media_package` WHERE $criteria";
			$ri = Zymurgy::$db->query($sql) or die("Could not retrieve list of packages: ".mysql_error());
	
			$mediaPackages = array();
	
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaPackage = new MediaPackage();
	
				$mediaPackage->set_media_package_id($row["media_package_id"]);
				$mediaPackage->set_display_name($row["display_name"]);
	
				$member = MediaMemberPopulator::PopulateByID(
					$row["member_id"]);
				$mediaPackage->set_member($member);
	
				$restriction = MediaRestrictionPopulator::PopulateByID(
					$row["media_restriction_id"]);
				$mediaPackage->set_restriction($restriction);
	
				$mediaPackages[] = $mediaPackage;
			}
	
			return $mediaPackages;
		}
	
		static function PopulateByID($media_package_id)
		{
			$sql = "SELECT `media_restriction_id`, `member_id`, `display_name` FROM `zcm_media_package` ".
				"WHERE `media_package_id` = '".
				mysql_escape_string($media_package_id)."'";
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
					$row["media_restriction_id"]);
				$mediaPackage->set_restriction($restriction);
	
				return $mediaPackage;
			}
		}
	
		static function PopulateMediaFiles(
			&$mediaPackage,
			$mediaRelationType = '')
		{
			$criteria = "1 = 1";
			
			if($mediaRelationType !== '')
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);	
				$criteria = "`media_relation_id` = '".
					mysql_escape_string($mediaRelation->get_media_relation_id())."'";
			}

			$sql = "SELECT `media_file_id`, `disporder`, `media_relation_id` ".
				"FROM `zcm_media_file_package` WHERE `media_package_id` = '".
				mysql_escape_string($mediaPackage->get_media_package_id()).
				"' AND $criteria ORDER BY `disporder`";				
	
			// die($sql);
	
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve list of files: ".mysql_error().", $sql");
	
			$mediaPackage->clear_media_files();
	
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaFile = MediaFilePopulator::PopulateByID(
					$row["media_file_id"]);
	
				if($mediaFile !== null)
				{
					$mediaFile->set_disporder($row["disporder"]);
					
					$mediaRelation = MediaRelationPopulator::PopulateByID(
						$row["media_relation_id"]);			
					
					$mediaFile->set_relation($mediaRelation);
		
					$mediaPackage->add_media_file($mediaFile);					
				}
			}
		}
	
		static function PopulateFromForm()
		{
			$mediaPackage = new MediaPackage();
	
			$mediaPackage->set_media_package_id($_POST["media_package_id"]);
			$mediaPackage->set_display_name($_POST["display_name"]);
	
			$mediaMember = MediaMemberPopulator::PopulateByID(
				$_POST["member_id"]);
			$mediaPackage->set_member($mediaMember);
	
			return $mediaPackage;
		}
	
		static function SaveMediaPackage($mediaPackage)
		{
			$sql = "";
	
			if($mediaPackage->get_media_package_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_package` ( `member_id`, `display_name` ) VALUES ( '".
					mysql_escape_string($mediaPackage->get_member()->get_member_id())."', '".
					mysql_escape_string($mediaPackage->get_display_name())."' )";
	
				Zymurgy::$db->query($sql) or die("Could not insert media file record: ".mysql_error());
	
				$sql = "SELECT MAX(`media_package_id`) FROM `zcm_media_package`";
	
				$mediaPackage->set_media_package_id(
					Zymurgy::$db->get($sql));
			}
			else
			{
				$sql = "UPDATE `zcm_media_package` SET ".
					"`member_id` = '".mysql_escape_string($mediaPackage->get_member()->get_member_id())."', ".
					"`display_name` = '".mysql_escape_string($mediaPackage->get_display_name())."' ".
					"WHERE `media_package_id` = '".mysql_escape_string($mediaPackage->get_media_package_id())."'";
	
				Zymurgy::$db->query($sql) or die("Could not update media file record: ".mysql_error());
			}
		}
	
		public function DeleteMediaPackage($media_package_id)
		{
			$sql = "DELETE FROM `zcm_media_package` WHERE `media_package_id` = '".
				mysql_escape_string($media_package_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete media package record: ".mysql_error());
		}
	
		public function AddMediaFileToPackage(
		$media_package_id,
		$media_file_id,
		$media_relation_id,
		$disporder)
		{
			$sql = "INSERT INTO `zcm_media_file_package` ( `media_package_id`, ".
				"`media_file_id`, `media_relation_id`, `disporder` ) VALUES ('".
				mysql_escape_string($media_package_id)."', '".
				mysql_escape_string($media_file_id)."', '".
				mysql_escape_string($media_relation_id)."', '".
				mysql_escape_string($disporder)."')";
	
			Zymurgy::$db->query($sql)
				or die("Could not add media file to media package: ".mysql_error().", $sql");
		}
	
		public function SaveMediaFileOrder($mediaPackage)
		{
			for($cntr = 0; $cntr < $mediaPackage->get_media_file_count(); $cntr++)
			{
				$mediaFile = $mediaPackage->get_media_file($cntr);
	
				$sql = "UPDATE `zcm_media_file_package` SET `disporder` = '".
					mysql_escape_string($mediaFile->get_disporder())."' WHERE `media_package_id` = '".
					mysql_escape_string($mediaPackage->get_media_package_id())."' AND `media_file_id` = '".
					mysql_escape_string($mediaFile->get_media_file_id())."'";
		
				Zymurgy::$db->query($sql)
					or die("Could not re-order media file in package: ".mysql_error());
			}
		}
	
		public function DeleteMediaFileFromPackage($media_package_id, $media_file_id)
		{
			$sql = "DELETE FROM `zcm_media_file_package` WHERE `media_package_id` = '".
				mysql_escape_string($media_package_id)."' AND `media_file_id` = '".
				mysql_escape_string($media_file_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete media file from media package: ".mysql_error());
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
	
	class MediaRelation
	{
		private $m_media_relation_id;
		private $m_relation_type;
		private $m_relation_label;
		private $m_allowed_mimetypes = "";
	
		private $m_errors = array();
	
		public function get_media_relation_id()
		{
			return $this->m_media_relation_id;
		}
	
		public function set_media_relation_id($newValue)
		{
			$this->m_media_relation_id = $newValue;
		}
	
		public function get_relation_type()
		{
			return $this->m_relation_type;
		}
	
		public function set_relation_type($newValue)
		{
			$this->m_relation_type = $newValue;
		}
	
		public function get_relation_label()
		{
			return $this->m_relation_label;
		}
	
		public function set_relation_label($newValue)
		{
			$this->m_relation_label = $newValue;
		}
	
		public function get_allowed_mimetypes()
		{
			return $this->m_allowed_mimetypes;
		}
	
		public function set_allowed_mimetypes($newValue)
		{
			$this->m_allowed_mimetypes = $newValue;
		}
	
		public function get_errors()
		{
			return $this->m_errors;
		}
	
		public function set_errors($newValue)
		{
			$this->m_errors = $newValue;
		}
	
		public function isValidMimetype($mimetype)
		{
			if(strlen($this->m_allowed_mimetypes) <= 0) return true;
	
			$mimetypes = explode(",", $this->m_allowed_mimetypes);
			return in_array($mimetype, $mimetypes);
		}
	
		public function validate($action)
		{
			$isValid = true;
	
			if(strlen($this->m_relation_type) <= 0)
			{
				$this->m_errors[] = "Relation Type is required.";
				$isValid = false;
			}
	
			if(strlen($this->m_relation_label) <= 0)
			{
				$this->m_errors[] = "Label is required.";
				$isValid = false;
			}
	
			return $isValid;
		}
	}
	
	class MediaRelationPopulator
	{
		static function PopulateAll()
		{
			$sql = "SELECT `media_relation_id`, `relation_type`, `relation_type_label`, `allowed_mimetypes` ".
				"FROM `zcm_media_relation` ORDER BY `relation_type_label`";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve relation types: ".mysql_error().", $sql");
	
			$mediaRelations = array();
	
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaRelation = new MediaRelation();
	
				$mediaRelation->set_media_relation_id($row["media_relation_id"]);
				$mediaRelation->set_relation_type($row["relation_type"]);
				$mediaRelation->set_relation_label($row["relation_type_label"]);
				$mediaRelation->set_allowed_mimetypes($row["allowed_mimetypes"]);
	
				$mediaRelations[] = $mediaRelation;
			}
	
			return $mediaRelations;
		}
	
		static function PopulateByID($media_relation_id)
		{
			$sql = "SELECT `relation_type`, `relation_type_label`, `allowed_mimetypes` ".
				"FROM `zcm_media_relation` WHERE `media_relation_id` = '".
				mysql_escape_string($media_relation_id)."'";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve relation: ".mysql_error().", $sql");
	
			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaRelation = new MediaRelation();
	
				$mediaRelation->set_media_relation_id($media_relation_id);
				$mediaRelation->set_relation_type($row["relation_type"]);
				$mediaRelation->set_relation_label($row["relation_type_label"]);
				$mediaRelation->set_allowed_mimetypes($row["allowed_mimetypes"]);
	
				return $mediaRelation;
			}
		}
		
		static function PopulateByType($relation_type)
		{
			$sql = "SELECT `media_relation_id`,  `relation_type`, `relation_type_label`, ".
				"`allowed_mimetypes` FROM `zcm_media_relation` WHERE `relation_type` = '".
				mysql_escape_string($relation_type)."'";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve relation: ".mysql_error().", $sql");
	
			if(Zymurgy::$db->num_rows($ri) <= 0)
			{
				die("Could not find any relations with the given type");
			}
			else 
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaRelation = new MediaRelation();
	
				$mediaRelation->set_media_relation_id($row["media_relation_id"]);
				$mediaRelation->set_relation_type($row["relation_type"]);
				$mediaRelation->set_relation_label($row["relation_type_label"]);
				$mediaRelation->set_allowed_mimetypes($row["allowed_mimetypes"]);
	
				return $mediaRelation;
			}
		}
	
		static function PopulateFromForm()
		{
			$mediaRelation = new MediaRelation();
	
			$mediaRelation->set_media_relation_id($_POST["media_relation_id"]);
			$mediaRelation->set_relation_type($_POST["relation_type"]);
			$mediaRelation->set_relation_label($_POST["relation_label"]);
			$mediaRelation->set_allowed_mimetypes($_POST["allowed_mimetypes"]);
	
			return $mediaRelation;
		}
	
		static function SaveRelation($mediaRelation)
		{
			$sql = "";
	
			if($mediaRelation->get_media_relation_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_relation` ( `relation_type`, ".
					"`relation_type_label`, `allowed_mimetypes` ) VALUES ( '".
					mysql_escape_string($mediaRelation->get_relation_type())."', '".
					mysql_escape_string($mediaRelation->get_relation_label())."', '".
					mysql_escape_string($mediaRelation->get_allowed_mimetypes())."')";
	
				Zymurgy::$db->query($sql) or die("Could not insert relation record: ".mysql_error());
	
				$sql = "SELECT MAX(`media_relation_id`) FROM `zcm_media_relation`";
	
				$mediaRelation->set_media_relation_id(
					Zymurgy::$db->get($sql));
			}
			else
			{
				$sql = "UPDATE `zcm_media_relation` SET ".
					"`relation_type` = '".mysql_escape_string($mediaRelation->get_relation_type())."', ".
					"`relation_type_label` = '".mysql_escape_string($mediaRelation->get_relation_label())."', ".
					"`allowed_mimetypes` = '".mysql_escape_string($mediaRelation->get_allowed_mimetypes())."' ".
					"WHERE `media_relation_id` = '".mysql_escape_string($mediaRelation->get_media_relation_id())."'";
	
				Zymurgy::$db->query($sql) or die("Could not update relation record: ".mysql_error());
			}
		}
	
		public function DeleteRelation($media_relation_id)
		{
			$sql = "DELETE FROM `zcm_media_relation` WHERE `media_relation_id` = '".
			mysql_escape_string($media_relation_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete relation: ".mysql_error());
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
	
		static function DisplayEditForm($mediaFile, $mediaRelations, $action)
		{
			include("header.php");
			include('datagrid.php');
	
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
			echo("<input type=\"hidden\" name=\"member_id\" value=\"".$mediaFile->get_member()->get_member_id()."\">");
			echo("<table>");
	
			echo("<tr>");
			echo("<td>Display Name:</td>");
			echo("<td>");
			$widget->Render("input.30.100", "display_name", $mediaFile->get_display_name());
			echo("</td>");
			echo("</tr>");
	
			echo("<tr>");
			echo("<td>Content Type:</td>");
			echo("<td>");
			echo("<select name=\"media_relation_id\">");
	
			foreach($mediaRelations as $mediaRelation)
			{
				echo("<option ".
					($mediaRelation->get_media_relation_id() == $mediaFile->get_relation()->get_media_relation_id() ? "SELECTED" : "").
					" value=\"".
					$mediaRelation->get_media_relation_id().
					"\">".
					$mediaRelation->get_relation_label().
					"</option>");
			}
			//$widget->Render("lookup.zcm_media_relation.media_relation_id.relation_type_label", "media_relation_id", $mediaFile->get_relation()->get_media_relation_id());
	
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
	
			echo("<tr>");
			echo("<td>Owner:</td>");
			echo("<td>".$mediaFile->get_member()->get_email()."</td>");
			echo("</tr>");
	
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
			echo("<td>Owner</td>");
			echo("<td colspan=\"2\">&nbsp;</td>");
			echo("</tr>");
	
			$cntr = 1;
	
			foreach($mediaPackages as $mediaPackage)
			{
				echo("<tr class=\"".($cntr % 2 ? "DataGridRow" : "DataGridRowAlternate")."\">");
				echo("<td><a href=\"media.php?action=edit_media_package&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."\">".$mediaPackage->get_display_name()."</td>");
				echo("<td>".$mediaPackage->get_member()->get_email()."</td>");
				echo("<td><a href=\"media.php?action=list_media_package_files&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."\">Files</a></td>");
				echo("<td><a href=\"media.php?action=delete_media_package&amp;media_package_id=".
					$mediaPackage->get_media_package_id()."\">Delete</a></td>");
				echo("</tr>");
	
				$cntr++;
			}
	
			echo("<tr class=\"DataGridHeader\">");
			echo("<td colspan=\"4\"><a style=\"color: white;\" href=\"media.php?action=add_media_package\">Add Media Package</td>");
	
			echo("</table>");
	
			include("footer.php");
		}
	
		static function DisplayEditForm($mediaPackage, $action)
		{
			include("header.php");
			$widget = new InputWidget();
	
			// echo("<pre>");
			// print_r($mediaFile);
			// echo("</pre>");
	
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
			echo("<input type=\"hidden\" name=\"member_id\" value=\"".$mediaPackage->get_member()->get_member_id()."\">");
			echo("<table>");
	
			echo("<tr>");
			echo("<td>Display Name:</td>");
			echo("<td>");
				$widget->Render("input.30.100", "display_name", $mediaPackage->get_display_name());
			echo("</td>");
			echo("</tr>");
	
			echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");
	
			echo("<tr>");
			echo("<td>Owner:</td>");
			echo("<td>".$mediaPackage->get_member()->get_email()."</td>");
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
				echo("<td>".$mediaFile->get_relation()->get_relation_label()."</td>");
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
			echo("<td>MIME Type</td>");
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
				echo("<td>".$mediaFile->get_mimetype()."</td>");
				echo("<td>".$mediaFile->get_member()->get_email()."</td>");
				echo("<td><a href=\"media.php?action=download_media_file&amp;media_file_id=".
					$mediaFile->get_media_file_id()."\">Download</a></td>");
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
			echo("<input style=\"width: 80px;\" type=\"button\" value=\"Cancel\" onclick=\"window.location.href='media.php?action=list_media_package_files&media_package_id=".$mediaPackage->get_media_package_id()."';\">&nbsp;</p>");
	
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
	
			echo("<tr>");
			echo("<td>Allowed mimetypes:</td>");
			echo("<td>");
			$widget->Render("input.30.50", "allowed_mimetypes", $mediaRelation->get_allowed_mimetypes());
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
	
		static function DisplayDeleteFrom($mediaRelation)
		{
			include("header.php");
	
			echo("<form name=\"frm\" action=\"media.php\" method=\"POST\">");
	
			echo("<input type=\"hidden\" name=\"action\" value=\"act_delete_relation\">");
			echo("<input type=\"hidden\" name=\"media_relation_id\" value=\"".
				$mediaRelation->get_media_relation_id()."\">");
	
			echo("<p>Are you sure you want to delete the following media package:</p>");
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
	
					MediaFileView::DisplayEditForm($mediaFile, $mediaRelations, "act_add_media_file");
					return true;
	
				case "edit_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					MediaFilePopulator::PopulateRelatedMedia($mediaFile);
					$mediaRelations = MediaRelationPopulator::PopulateAll();
	
					MediaFileView::DisplayEditForm($mediaFile, $mediaRelations, "act_edit_media_file");
					
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
					MediaFilePopulator::DeleteMediaFile(
						$_POST["media_file_id"]);
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
	
				case "download_media_file":
					$mediaFile = MediaFilePopulator::PopulateByID(
						$_GET["media_file_id"]);
					$fileContent = MediaFilePopulator::GetFilestream(
						$mediaFile);
					MediaFileView::DownloadMediaFile($mediaFile, $fileContent);
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
					$mediaPackage->set_member(
						MediaMemberPopulator::PopulateByID(1));
					MediaPackageView::DisplayEditForm($mediaPackage, "act_add_media_package");
					return true;
	
				case "edit_media_package":
					$mediaPackage = MediaPackagePopulator::PopulateByID(
						$_GET["media_package_id"]);
					MediaPackageView::DisplayEditForm($mediaPackage, "act_edit_media_package");
					return true;
	
				case "act_add_media_package":
				case "act_edit_media_package":
					$mediaPackage = MediaPackagePopulator::PopulateFromForm();
	
					if(!$mediaPackage->validate($action))
					{
						MediaPackageView::DisplayEditForm($mediaPackage, $action);
					}
					else
					{
						if(MediaPackagePopulator::SaveMediaPackage($mediaPackage))
						{
							MediaPackageView::DisplayEditForm($mediaPackage, $action);
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
					MediaPackagePopulator::PopulateMediaFiles($mediaPackage);
					$disporder = $mediaPackage->get_media_file_count();
	
					MediaPackagePopulator::AddMediaFileToPackage(
						$_POST["media_package_id"],
						$_POST["media_file_id"],
						$_POST["media_relation_id"],
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
	}
?>