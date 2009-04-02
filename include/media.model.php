<?php
	/*
		Zymurgy:CM Media File Component
		Z:CM Model classes
	*/

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
	
		static function PopulateByOwner(
			$member_id,
			$mediaRelationType = "")
		{
			$relationCriteria = "1 = 1";
			
			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);				
				$relationCriteria = "`media_relation_id` = '".
					mysql_escape_string($mediaRelation->get_media_relation_id())."'";
			}
			
			$criteria = "`zcm_media_file`.`member_id` = '".
				mysql_escape_string($member_id).
				"' AND $relationCriteria";
			
			return MediaFilePopulator::PopulateMultiple($criteria);
		}
		
		static function PopulateStrayFiles(
			$member_id,
			$mediaRelationType = "")
		{
			$relationCriteria = "1 = 1";
			
			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);				
				$relationCriteria = "`media_relation_id` = '".
					mysql_escape_string($mediaRelation->get_media_relation_id())."'";
			}
			
			$criteria = "`zcm_media_file`.`member_id` = '".
				mysql_escape_string($member_id).
				"' AND $relationCriteria ".
				"AND NOT EXISTS(SELECT 1 FROM `zcm_media_file_package` WHERE ".
				"`zcm_media_file_package`.`media_file_id` = `zcm_media_file`.`media_file_id`) ".
				"AND NOT EXISTS(SELECT 1 FROM `zcm_media_file_relation` WHERE ".
				"`zcm_media_file_relation`.`related_media_file_id` = `zcm_media_file`.`media_file_id`)";
			
			return MediaFilePopulator::PopulateMultiple($criteria);
		}

		static function PopulateAllNotInPackage(
			$mediaPackage,
			$mediaRelationType = "",
			$owner = 0)
		{
			$relationCriteria = "1 = 1";
			
			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);				
				$relationCriteria = "`media_relation_id` = '".
					mysql_escape_string($mediaRelation->get_media_relation_id())."'";
			}
			
			$ownerCriteria = "1 = 1";
			
			if($owner > 0)
			{
				$ownerCriteria = "`member_id` = '".
					mysql_escape_string($owner)."'";
			}
			
			$allowedRelationCriteria = "EXISTS(SELECT 1 FROM `zcm_media_package_type_allowed_relation` ".
				"WHERE `zcm_media_package_type_allowed_relation`.`media_relation_id` = ".
				"`zcm_media_file`.`media_relation_id` AND ". 
				"`zcm_media_package_type_allowed_relation`.`media_package_type_id` = '".
				mysql_escape_string($mediaPackage->get_packageType()->get_media_package_type_id()).
				"')";
			
			$notInPackageCriteria = "NOT EXISTS(SELECT 1 FROM `zcm_media_file_package` WHERE ".
				"`zcm_media_file_package`.`media_file_id` = `zcm_media_file`.`media_file_id` AND ".
				"`zcm_media_file_package`.`media_package_id` = '".
				mysql_escape_string($mediaPackage->get_media_package_id())."') ";
			
			$criteria = "$relationCriteria AND $ownerCriteria AND ".
				"$allowedRelationCriteria AND $notInPackageCriteria";				
			
			return MediaFilePopulator::PopulateMultiple($criteria);
		}
	
		static function PopulateAllNotRelated(
			$mediaFile,
			$mediaRelationType = "",
			$owner = 0)
		{
			$relationCriteria = "1 = 1";
			
			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);				
				$relationCriteria = "`media_relation_id` = '".
					mysql_escape_string($mediaRelation->get_media_relation_id())."'";
			}
			
			$ownerCriteria = "1 = 1";
			
			if($owner > 0)
			{
				$ownerCriteria = "`member_id` = '".
					mysql_escape_string($owner)."'";
			}

			$criteria = "`media_file_id` <> '".
				mysql_escape_string($mediaFile->get_media_file_id()).
				"' AND $relationCriteria AND $ownerCriteria ".
				"AND NOT EXISTS(SELECT 1 FROM `zcm_media_file_relation` WHERE ".
				"`zcm_media_file_relation`.`related_media_file_id` = `zcm_media_file`.`media_file_id` AND ".
				"`zcm_media_file_relation`.`media_file_id` = '".
				mysql_escape_string($mediaFile->get_media_file_id()).
				"')";

			return MediaFilePopulator::PopulateMultiple($criteria);
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
				$oldPath = $uploadfolder."/".$mediaFile->get_media_file_id().".*";
				foreach(glob($oldPath) as $oldFile)
				{
					// echo($oldFile."<br>");
					unlink($oldFile);
				}
				
				// die();
				
				$newPath = $uploadfolder."/".$mediaFile->get_media_file_id().".".$extension;
	
				if(move_uploaded_file($file["tmp_name"], $newPath))
				{
					chmod($newPath, 0644);
				}
			}
		}
	
		public function DeleteMediaFile($mediaFile)
		{
			$sql = "DELETE FROM `zcm_media_file_package` WHERE `media_file_id` = '".
				mysql_escape_string($mediaFile->get_media_file_id())."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete media file record from packages: ".mysql_error());
			
			$sql = "DELETE FROM `zcm_media_file` WHERE `media_file_id` = '".
				mysql_escape_string($mediaFile->get_media_file_id())."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete media file record: ".mysql_error());
			
			$uploadfolder = Zymurgy::$config["Media File Local Path"];
			$filepath = $uploadfolder.
				"/".
				$mediaFile->get_media_file_id().
				".".
				$mediaFile->get_extension();

			if(file_exists($filepath))
			{
				unlink($filepath);
			}
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
		private $m_display_name;
	
		private $m_member;
		private $m_restriction;
		private $m_packagetype;
	
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
		
		public function get_packagetype()
		{
			return $this->m_packagetype;
		}
		
		public function set_packagetype($newValue)
		{
			$this->m_packagetype = $newValue;
		}
	
		public function get_media_file($key)
		{
			return $this->m_media_files[$key];
		}
	
		public function get_media_file_count()
		{
			return count($this->m_media_files);
		}
			
		public function set_media_file($key, $newValue)
		{
			$this->m_media_files[$key] = $newValue;
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
			$sql = "SELECT `media_package_id`, `media_restriction_id`, `member_id`,".
				" `display_name`, `media_package_type_id` FROM `zcm_media_package` WHERE $criteria";
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
				
				$packageType = MediaPackageTypePopulator::PopulateByID(
					$row["media_package_type_id"]);
				$mediaPackage->set_packagetype($packageType);
	
				$mediaPackages[] = $mediaPackage;
			}
	
			return $mediaPackages;
		}
	
		static function PopulateByID($media_package_id)
		{
			$sql = "SELECT `media_restriction_id`, `member_id`, `display_name`, ".
				"`media_package_type_id` FROM `zcm_media_package` WHERE ".
				"`media_package_id` = '".
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
					
				$packageType = MediaPackageTypePopulator::PopulateByID(
					$row["media_package_type_id"]);
				$mediaPackage->set_packagetype($packageType);

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
			
			$packageType = MediaPackageTypePopulator::PopulateByID(
				$_POST["media_package_type_id"]);
			$mediaPackage->set_packagetype($packageType);
	
			return $mediaPackage;
		}
	
		static function SaveMediaPackage($mediaPackage)
		{
			$sql = "";
	
			if($mediaPackage->get_media_package_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_package` ( `member_id`, `media_package_type_id`, `display_name` ) VALUES ( '".
					mysql_escape_string($mediaPackage->get_member()->get_member_id())."', '".
					mysql_escape_string($mediaPackage->get_packagetype()->get_media_package_type_id())."', '".
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
					"`media_package_type_id` = '".mysql_escape_string($mediaPackage->get_packagetype()->get_media_package_type_id())."', ".
					"`display_name` = '".mysql_escape_string($mediaPackage->get_display_name())."' ".
					"WHERE `media_package_id` = '".mysql_escape_string($mediaPackage->get_media_package_id())."'";
	
				Zymurgy::$db->query($sql) or die("Could not update media file record: ".mysql_error());
			}
		}
	
		public function DeleteMediaPackage($media_package_id)
		{
			$sql = "DELETE FROM `zcm_media_file_package` WHERE `media_package_id` = '".
				mysql_escape_string($media_package_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete media package record: ".mysql_error());
			
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
		
		public function SwapMediaFiles(
			$mediaPackage, 
			$mediaFile1,
			$mediaFile2, 
			$relationType = '')
		{
			// Before attempting the swap, fix any corruption issues with the 
			// disporder. Note that this has to be done on all of the media files, 
			// so we're doing it before we filter on the relationType.
			MediaPackagePopulator::PopulateMediaFiles($mediaPackage);
			$mediaPackage->FixMediaFileOrder();						
			MediaPackagePopulator::SaveMediaFileOrder($mediaPackage);
			
			// Loop through the media files for the given relationType and find 
			// the files to swap.
			MediaPackagePopulator::PopulateMediaFiles($mediaPackage, $relationType);
			$index1 = -1;
			$index2 = -1;
			
			for($cntr = 0; $cntr < $mediaPackage->get_media_file_count(); $cntr++)
			{
				$mediaFile = $mediaPackage->get_media_file($cntr);
				
				if($mediaFile->get_media_file_id() == $mediaFile1->get_media_file_id())
				{
					$mediaFile1 = $mediaFile;
					$index1 = $cntr;
				}
				
				if($mediaFile->get_media_file_id() == $mediaFile2->get_media_file_id())
				{
					$mediaFile2 = $mediaFile;
					$index2 = $cntr;
				}
				
				if($index1 >= 0 && $index2 >= 0)
				{
					break;
				}
			}
			
			// If we actually found both files, swap them.
			if($index1 >= 0 && $index2 >= 0)
			{
				//echo("Swapping disp_orders ".
				// 	$mediaFile1->get_disporder().
				// 	" and ".
				// 	$mediaFile2->get_disporder());
				
				$tempDispOrder = $mediaFile1->get_disporder();
				$mediaFile1->set_disporder($mediaFile2->get_disporder());
				$mediaFile2->set_disporder($tempDispOrder);
				
				$mediaPackage->set_media_file($index1, $mediaFile1);
				$mediaPackage->set_media_file($index2, $mediaFile2);
			}
			
			MediaPackagePopulator::SaveMediaFileOrder($mediaPackage);
		}
	}
	
	class MediaPackageType
	{
		private $m_media_package_type_id;
		private $m_package_type;
		private $m_package_label;
		
		private $m_allowedrelations = array();
		
		private $m_errors = array();
		
		public function get_media_package_type_id()
		{
			return $this->m_media_package_type_id;
		}
		
		public function set_media_package_type_id($newValue)
		{
			$this->m_media_package_type_id = $newValue;
		}
		
		public function get_package_type()
		{
			return $this->m_package_type;
		}
		
		public function set_package_type($newValue)
		{
			$this->m_package_type = $newValue;
		}
		
		public function get_package_label()
		{
			return $this->m_package_label;
		}
		
		public function set_package_label($newValue)
		{
			$this->m_package_label = $newValue;
		}
		
	
		public function get_allowedrelation($key)
		{
			return $this->m_allowedrelations[$key];
		}
	
		public function get_allowedrelation_count()
		{
			return count($this->m_allowedrelations);
		}
			
		public function set_allowedrelation($key, $newValue)
		{
			$this->m_allowedrelations[$key] = $newValue;
		}

		public function add_allowedrelation($allowedrelation)
		{
			$this->m_allowedrelations[] = $allowedrelation;
	
			return count($this->m_allowedrelations) - 1;
		}
	
		public function delete_allowedrelation($key)
		{
			unset($this->m_allowedrelations[$key]);
		}
	
		public function clear_allowedrelations()
		{
			$this->m_allowedrelations = array();
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
	
			if(strlen($this->m_package_type) <= 0)
			{
				$this->m_errors[] = "Package Type is required.";
				$isValid = false;
			}
	
			if(strlen($this->m_package_label) <= 0)
			{
				$this->m_errors[] = "Label is required.";
				$isValid = false;
			}
	
			return $isValid;
		}
	}
	
	class MediaPackageTypeAllowedRelation
	{
		private $m_media_package_type_allowed_relation_id;
		private $m_media_package_type_id;
		private $m_media_relation_id;
		private $m_maxinstances;

		private $m_relation;

		private $m_errors = array();
		
		public function get_media_package_type_allowed_relation_id()
		{
			return $this->m_media_package_type_allowed_relation_id;
		}
		
		public function set_media_package_type_allowed_relation_id($newValue)
		{
			$this->m_media_package_type_allowed_relation_id = $newValue;
		}
		
		public function get_media_package_type_id()
		{
			return $this->m_media_package_type_id;
		}
		
		public function set_media_package_type_id($newValue)
		{
			$this->m_media_package_type_id = $newValue;
		}
		
		public function get_maxinstances()
		{
			return $this->m_maxinstances;
		}
		
		public function set_maxinstances($newValue)
		{
			$this->m_maxinstances = $newValue;
		}
		
		public function get_relation()
		{
			return $this->m_relation;
		}
		
		public function set_relation($newValue)
		{
			$this->m_relation = $newValue;
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
	
			if($this->m_relation == null)
			{
				$this->m_errors[] = "Relation is required.";
				$isValid = false;
			}
	
			if(strlen($this->m_maxinstances) <= 0)
			{
				$this->m_errors[] = "Max instances is required.";
				$isValid = false;
			}
			else 
			{
				if(!is_numeric($this->m_maxinstances))
				{
					$this->m_errors[] = "Max instances must be a number.";
					$isValid = false;					
				}
			}
	
			return $isValid;
		}
	}
	
	class MediaPackageTypePopulator
	{
		public static function PopulateAll()
		{
			$sql = "SELECT `media_package_type_id`, `package_type`, ".
				"`package_type_label` FROM `zcm_media_package_type`";
	
			$ri = Zymurgy::$db->query($sql) 
				or die("Could not retrieve list of package types: ".mysql_error().", $sql");
			
			$packageTypes = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$packageType = new MediaPackageType();

				$packageType->set_media_package_type_id($row["media_package_type_id"]);
				$packageType->set_package_type($row["package_type"]);
				$packageType->set_package_label($row["package_type_label"]);
				
				$packageTypes[] = $packageType;
			}
			
			return $packageTypes;
		}
	
		public static function PopulateByID(
			$media_package_type_id)
		{
			$sql = "SELECT `package_type`, `package_type_label` FROM ".
				"`zcm_media_package_type` WHERE `media_package_type_id` = '".
				mysql_escape_string($media_package_type_id).
				"'";
	
			$ri = Zymurgy::$db->query($sql) 
				or die("Could not retrieve package type: ".mysql_error().", $sql");
			
			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$packageType = new MediaPackageType();

				$packageType->set_media_package_type_id($media_package_type_id);
				$packageType->set_package_type($row["package_type"]);
				$packageType->set_package_label($row["package_type_label"]);
			
				return $packageType;
			}
		}
		
		public static function PopulateAllowedRelations(
			&$packageType)
		{			
			$sql = "SELECT `media_package_type_allowed_relation_id`, `media_relation_id`, `max_instances` FROM `zcm_media_package_type_allowed_relation` WHERE `media_package_type_id` = '".mysql_escape_string($packageType->get_media_package_type_id())."'";
	
			$ri = Zymurgy::$db->query($sql) 
				or die("Could not retrieve allowed relations: ".mysql_error().", $sql");
			
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$allowedRelation = new MediaPackageTypeAllowedRelation();
				
				$allowedRelation->set_media_package_type_allowed_relation_id(
					$row["media_package_type_allowed_relation_id"]);
				$allowedRelation->set_media_package_type_id($packageType->get_media_package_type_id());
				$allowedRelation->set_maxinstances($row["max_instances"]);
				
				$relation = MediaRelationPopulator::PopulateByID(
					$row["media_relation_id"]);
				$allowedRelation->set_relation($relation);
				
				$packageType->add_allowedrelation($allowedRelation);
			}			
		}
		
		public static function PopulateAllowedRelationByID(
			$allowedRelationID)
		{			
			$sql = "SELECT `media_package_type_id`, `media_relation_id`, `max_instances` FROM `zcm_media_package_type_allowed_relation` WHERE `media_package_type_allowed_relation_id` = '".mysql_escape_string($allowedRelationID)."'";
	
			$ri = Zymurgy::$db->query($sql) 
				or die("Could not retrieve allowed relations: ".mysql_error().", $sql");
			
			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$allowedRelation = new MediaPackageTypeAllowedRelation();
				
				$allowedRelation->set_media_package_type_allowed_relation_id(
					$allowedRelationID);
				$allowedRelation->set_media_package_type_id($row["media_package_type_id"]);
				$allowedRelation->set_maxinstances($row["max_instances"]);
				
				$relation = MediaRelationPopulator::PopulateByID(
					$row["media_relation_id"]);
				$allowedRelation->set_relation($relation);
				
				return $allowedRelation;
			}			
		}
	
		static function PopulateFromForm()
		{
			$packageType = new MediaPackageType();
	
			$packageType->set_media_package_type_id($_POST["media_package_type_id"]);
			$packageType->set_package_type($_POST["package_type"]);
			$packageType->set_package_label($_POST["package_label"]);
	
			return $packageType;
		}
		
		public static function PopulateAllowedRelationFromForm()
		{
			$allowedRestriction = new MediaPackageTypeAllowedRelation();
			
			$allowedRestriction->set_media_package_type_allowed_relation_id(
				$_POST["media_package_type_allowed_relation_id"]);
			$allowedRestriction->set_media_package_type_id($_POST["media_package_type_id"]);
			$allowedRestriction->set_maxinstances($_POST["max_instances"]);

			$relation = MediaRelationPopulator::PopulateByID(
				$_POST["media_relation_id"]);
			$allowedRestriction->set_relation($relation);
			
			return $allowedRestriction;
		}

		static function SaveMediaPackageType($packageType)
		{
			$sql = "";
	
			if($packageType->get_media_package_type_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_package_type` ( `package_type`, ".
					"`package_type_label` ) VALUES ( '".
					mysql_escape_string($packageType->get_package_type())."', '".
					mysql_escape_string($packageType->get_package_label())."')";
	
				Zymurgy::$db->query($sql) or die("Could not insert package type record: ".mysql_error());
	
				$sql = "SELECT MAX(`media_package_type_id`) FROM `zcm_media_package_type`";
	
				$packageType->set_media_package_type_id(
					Zymurgy::$db->get($sql));
			}
			else
			{
				$sql = "UPDATE `zcm_media_package_type` SET ".
					"`package_type` = '".mysql_escape_string($packageType->get_package_type())."', ".
					"`package_type_label` = '".mysql_escape_string($packageType->get_package_label())."' ".
					"WHERE `media_package_type_id` = '".mysql_escape_string($packageType->get_media_package_type_id())."'";
	
				Zymurgy::$db->query($sql) or die("Could not update package type record: ".mysql_error());
			}
		}
	
		public static function DeleteMediaPackageType($media_package_type_id)
		{
			$sql = "DELETE FROM `zcm_media_package_type` WHERE `media_package_type_id` = '".
				mysql_escape_string($media_package_type_id)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete package type: ".mysql_error());
		}
		
		public static function SaveMediaPackageTypeAllowedRelation($allowedRelation)
		{
			$sql = "";
	
			if($allowedRelation->get_media_package_type_allowed_relation_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_package_type_allowed_relation` ( ".
					"`media_package_type_id`, `media_relation_id`, `max_instances` ) VALUES ( '".
					mysql_escape_string($allowedRelation->get_media_package_type_id())."', '".
					mysql_escape_string($allowedRelation->get_relation()->get_media_relation_id())."', '".
					mysql_escape_string($allowedRelation->get_maxinstances())."')";
	
				Zymurgy::$db->query($sql) or die("Could not insert allowed relation record: ".mysql_error());
	
				$sql = "SELECT MAX(`media_package_type_allowed_relation_id`) FROM ".
					"`zcm_media_package_type_allowed_relation`";
	
				$allowedRelation->set_media_package_type_allowed_relation_id(
					Zymurgy::$db->get($sql));
			}
			else
			{
				$sql = "UPDATE `zcm_media_package_type_allowed_relation` SET ".
					"`media_package_type_id` = '".mysql_escape_string($allowedRelation->get_media_package_type_id())."', ".
					"`media_relation_id` = '".mysql_escape_string($allowedRelation->get_relation()->get_media_relation_id())."', ".
					"`max_instances` = '".mysql_escape_string($allowedRelation->get_maxinstances())."' ".
					"WHERE `media_package_type_allowed_relation_id` = '".mysql_escape_string($allowedRelation->get_media_package_type_allowed_relation_id())."'";
	
				Zymurgy::$db->query($sql) or die("Could not update allowed relation record: ".mysql_error());
			}
		}
		
		public static function DeleteMediaPackageTypeAllowedRelation($allowedRelationID)
		{
			$sql = "DELETE FROM `zcm_media_package_type_allowed_relation` ".
				"WHERE `media_package_type_allowed_relation_id` = '".
				mysql_escape_string($allowedRelationID)."'";
	
			Zymurgy::$db->query($sql) or die("Could not delete allowed relation: ".mysql_error());
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
		static function PopulateAll()
		{
			$sql = "SELECT `id`, `email` FROM `zcm_member`";
	
			$ri = Zymurgy::$db->query($sql) 
				or die("Could not retrieve list of members: ".mysql_error().", $sql");
			
			$members = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$member = new MediaMember();
	
				$member->set_member_id($row["id"]);
				$member->set_email($row["email"]);
				
				$members[] = $member;
			}
			
			return $members;
		}
		
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
?>