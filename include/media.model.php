<?php
	ini_set("display_errors", 1);
	require_once(Zymurgy::$root."/zymurgy/installer/upgradelib.php");

	/*
		Zymurgy:CM Media File Component
		Z:CM Model classes
	*/

	/**
	 * Contains the information and validation routines for a MediaFile.
	 *
	 */
	class MediaFile
	{
		private $m_media_file_id;
		private $m_mimetype;
		private $m_extension;
		private $m_display_name;
		private $m_price;

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

		/**
		 * Get the media file's ID, as stored in the database.
		 *
		 * @return int The media_file_id
		 */
		public function get_media_file_id()
		{
			return $this->m_media_file_id;
		}

		/**
		 * Set the media file's ID, as stored in the database.
		 *
		 * @param int $newValue
		 */
		public function set_media_file_id($newValue)
		{
			$this->m_media_file_id = $newValue;
		}

		/**
		 * Get the member information on the owner of the media file.
		 *
		 * @return MediaMember The MediaMember object describing the
		 * owner of the media file.
		 */
		public function get_member()
		{
			return $this->m_member;
		}

		/**
		 * Set the member information on the owner of the media file.
		 *
		 * @param MediaMember $newValue
		 */
		public function set_member($newValue)
		{
			$this->m_member = $newValue;
		}

		/**
		 * Get the media file's mime-type, as detected when the file
		 * was first uploaded.
		 *
		 * @return string The mime-type
		 */
		public function get_mimetype()
		{
			return $this->m_mimetype;
		}

		/**
		 * Set the media file's mime-type.
		 *
		 * @param string $newValue
		 */
		public function set_mimetype($newValue)
		{
			$this->m_mimetype = $newValue;
		}

		/**
		 * Get the media file's extension, as detected when the file
		 * was first uploaded.
		 *
		 * @return string The file extension.
		 */
		public function get_extension()
		{
			return $this->m_extension;
		}

		/**
		 * Set the media file's extension.
		 *
		 * @param string $newValue
		 */
		public function set_extension($newValue)
		{
			$this->m_extension = $newValue;
		}

		/**
		 * Get the name to display in the application front-end.
		 *
		 * @return string The name of the media file
		 */
		public function get_display_name()
		{
			return $this->m_display_name;
		}

		/**
		 * Set the name to display in the application front-end.
		 *
		 * @param unknown_type $newValue
		 */
		public function set_display_name($newValue)
		{
			$this->m_display_name = str_replace("\\'", "'", $newValue);
		}

		/**
		 * Get the price of the media file, when it is attached to a
		 * for-pay system on the web site.
		 *
		 * @return currency The price of the file
		 */
		public function get_price()
		{
			return $this->m_price;
		}

		/**
		 * Set the price of the media file, when it is attached to a
		 * for-pay system on the web site.
		 *
		 * @param currency $newValue
		 */
		public function set_price($newValue)
		{
			$this->m_price = $newValue;
		}

		/**
		 * Get the display index of the media file. This is populated
		 * when the MediaFile is retrieved as part of a MediaPackage.
		 *
		 * @return int The file's display index
		 */
		public function get_disporder()
		{
			return $this->m_disporder;
		}

		/**
		 * Set the display index of the media file.
		 *
		 * @param int $newValue
		 */
		public function set_disporder($newValue)
		{
			$this->m_disporder = $newValue;
		}

		/**
		 * Get the object describing the download restrictions on the
		 * file.
		 *
		 * @return MediaRestriction The media restriction object
		 */
		public function get_restriction()
		{
			return $this->m_restriction;
		}

		/**
		 * Set the download restrictions on the file.
		 *
		 * @param MediaRestriction $newValue
		 */
		public function set_restriction($newValue)
		{
			$this->m_restriction = $newValue;
		}

		/**
		 * Get the relation/file type information on the file.
		 *
		 * @return MediaRelation The relation data.
		 */
		public function get_relation()
		{
			return $this->m_relation;
		}

		/**
		 * Set the relation/file type information on the file.
		 *
		 * @param MediaRelation $newValue
		 */
		public function set_relation($newValue)
		{
			$this->m_relation = $newValue;
		}

		/**
		 * Get the information on a media file related to this one.
		 *
		 * @param int $key
		 * @return MediaFile The media file at the given key.
		 */
		public function get_relatedmedia($key)
		{
			return $this->m_relatedmedia[$key];
		}

		/**
		 * Get the number of media files related to this one.
		 *
		 * @return int The number of media files related to this one.
		 */
		public function get_relatedmedia_count()
		{
			return count($this->m_relatedmedia);
		}

		/**
		 * Make an existing media file related to this one.
		 *
		 * @param MediaFile $newValue
		 * @return int The key of the newly added file.
		 */
		public function add_relatedmedia($newValue)
		{
			$this->m_relatedmedia[] = $newValue;

			return count($this->m_relatedmedia) - 1;
		}

		/**
		 * Remove the media file at the given key from this media file's
		 * list of related files
		 *
		 * @param int $key
		 */
		public function delete_relatedmedia($key)
		{
			unset($this->m_relatedmedia[$key]);
		}

		/**
		 * Get the media file's file information. This is only populated
		 * when the media file has been set in a web form.
		 *
		 * @return File The file object, as set in $_FILES
		 */
		public function get_file()
		{
			return $this->m_file;
		}

		/**
		 * Set the media file's file information. This is only to be
		 * called when the media file has been set in a web form.
		 *
		 * @param File $fileObject The file object, as set in $_FILES
		 */
		public function set_file($fileObject)
		{
			$this->m_file = $fileObject;
		}

		/**
		 * Get the media file's actual file stream. This is only populated
		 * when the media file is going to be downloaded/streamed to the
		 * end user.
		 *
		 * @return byte[] The media file's content stream
		 */
		public function get_filestream()
		{
			return $this->m_filestream;
		}

		/**
		 * Set the media file's actual file stream. This is only to be
		 * called when the media file is going to be downloaded/streamed
		 * to the end user.
		 *
		 * @param byte[] $newValue The media file's content stream
		 */
		public function set_filestream($newValue)
		{
			$this->m_filestream = $newValue;
		}

		/**
		 * Get the list of errors in the media file object. This is
		 * populated as part of the validate() method, and is typically
		 * called as part of the save process during data input.
		 *
		 * @return string[] The list of errors in the object.
		 */
		public function get_errors()
		{
			return $this->m_errors;
		}

		/**
		 * Validate the media file object. This is typically called as
		 * part of the save process during data input. If there are
		 * errors, the errors array will be populated, and may be retrieved
		 * by calling get_errors().
		 *
		 * @param string $action The controller action. Used to enable add-only
		 * and edit-only specific validations. The supported actions are
		 * "act_add_media_file" and "act_edit_media_file".
		 * @return boolean True if the data is valid. Otherwise false.
		 */
		public function validate($action)
		{
			$isValid = true;

			if(strlen($this->m_display_name) <= 0)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString("MediaFile.Validate.DisplayNameRequired");
				$isValid = false;
			}

			if($action == "act_add_media_file")
			{
				if(!isset($this->m_file))
				{
					$this->m_errors[] = Zymurgy::GetLocaleString("MediaFile.Validate.FileRequired");
					$isValid = false;
				}
			}

			if(isset($this->m_file))
			{
				$isValid = $this->validateFile() ? $isValid : false;
			}

			return $isValid;
		}

		private function validateFile()
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
					$this->m_errors[] = str_replace(
						"{0}",
						ini_get("upload_max_filesize"),
						Zymurgy::GetLocaleString("FileUpload.Validate.TooLargeINI"));
					$isValid = false;
					break;

				case UPLOAD_ERR_FORM_SIZE:
					$this->m_errors[] = Zymurgy::GetLocaleString("FileUpload.Validate.TooLargeForm");
					$isValid = false;
					break;

				case UPLOAD_ERR_PARTIAL:
					$this->m_errors[] = Zymurgy::GetLocaleString("FileUpload.Validate.PartialUpload");
					$isValid = false;
					break;

				case UPLOAD_ERR_NO_TMP_DIR:
					$this->m_errors[] = Zymurgy::GetLocaleString("FileUpload.Validate.NoTempFolder");
					$isValid = false;
					break;

				case UPLOAD_ERR_CANT_WRITE:
					$this->m_errors[] = Zymurgy::GetLocaleString("FileUpload.Validate.WriteFailure");
					$isValid = false;
					break;

				default:
					$this->m_errors[] = Zymurgy::GetLocaleString("FileUpload.Validate.UnknownError");
					$isValid = false;
			}

			if($continueProcessing)
			{
				if(!($this->m_relation->isValidMimetype($this->m_file['type'])))
				{
					$this->m_errors[] = str_replace(
						"{0}",
						$this->m_relation->get_allowed_mimetypes(),
						Zymurgy::GetLocaleString("FileUpload.Validate.TooLargeINI"));
					$isValid = false;
				}
			}

			return $isValid;
		}
	}

	/**
	 * Contains a set of static methods used to populate either a single
	 * MediaFile object, or an array of MediaFile objects. Most of the methods
	 * populate the MediaFile object from the database, but the object may
	 * also be populated from a web form.
	 *
	 */
	class MediaFilePopulator
	{
		/**
		 * Return all of the media files in the database
		 *
		 * @return MediaFile[]
		 */
		public static function PopulateAll()
		{
			return MediaFilePopulator::PopulateMultiple("1 = 1");
		}

		/**
		 * Return all of the media files owned by a particular member.
		 *
		 * @param int $member_id Optional. The ID of the owner of the media files.
		 * @param string $mediaRelationType Optional. The relation type of the
		 * files to return
		 * @return MediaFile[]
		 */
		public static function PopulateByOwner(
			$member_id = 0,
			$mediaRelationType = "")
		{
			$ownerCriteria = "1 = 1";
			$relationCriteria = "1 = 1";

			if($member_id > 0)
			{
				$ownerCriteria = "`member_id` = '".
					Zymurgy::$db->escape_string($member_id).
					"'";
			}

			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);
				$relationCriteria = "`media_relation_id` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_media_relation_id())."'";
			}

			$criteria = "$ownerCriteria AND $relationCriteria";

			return MediaFilePopulator::PopulateMultiple($criteria);
		}

		/**
		 * Return all of the media files that have not been placed in
		 * a package.
		 *
		 * @param int $member_id Optional. The ID of the owner of the media files.
		 * @param string $mediaRelationType Optional. The relation type of the
		 * files to return
		 * @return MediaFile[]
		 */
		public static function PopulateStrayFiles(
			$member_id,
			$mediaRelationType = "")
		{
			$relationCriteria = "1 = 1";

			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);
				$relationCriteria = "`media_relation_id` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_media_relation_id())."'";
			}

			$criteria = "`zcm_media_file`.`member_id` = '".
				Zymurgy::$db->escape_string($member_id).
				"' AND $relationCriteria ".
				"AND NOT EXISTS(SELECT 1 FROM `zcm_media_file_package` WHERE ".
				"`zcm_media_file_package`.`media_file_id` = `zcm_media_file`.`media_file_id`) ".
				"AND NOT EXISTS(SELECT 1 FROM `zcm_media_file_relation` WHERE ".
				"`zcm_media_file_relation`.`related_media_file_id` = `zcm_media_file`.`media_file_id`)";

			return MediaFilePopulator::PopulateMultiple($criteria);
		}

		/**
		 * Return all of the media files that have not been placed in
		 * a given package.
		 *
		 * @param MediaPackage $mediaPackage
		 * @param string $mediaRelationType Optional. The relation type of the
		 * files to return
		 * @param int $owner Optional. The ID of the owner of the media files.
		 * @return MediaFile[]
		 */
		public static function PopulateAllNotInPackage(
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
					Zymurgy::$db->escape_string($owner)."'";
			}

			$allowedRelationCriteria = "EXISTS(SELECT 1 FROM `zcm_media_package_type_allowed_relation` ".
				"WHERE `zcm_media_package_type_allowed_relation`.`media_relation_id` = ".
				"`zcm_media_file`.`media_relation_id` AND ".
				"`zcm_media_package_type_allowed_relation`.`media_package_type_id` = '".
				Zymurgy::$db->escape_string($mediaPackage->get_packageType()->get_media_package_type_id()).
				"')";

			$notInPackageCriteria = "NOT EXISTS(SELECT 1 FROM `zcm_media_file_package` WHERE ".
				"`zcm_media_file_package`.`media_file_id` = `zcm_media_file`.`media_file_id` AND ".
				"`zcm_media_file_package`.`media_package_id` = '".
				Zymurgy::$db->escape_string($mediaPackage->get_media_package_id())."') ";

			$criteria = "$relationCriteria AND $ownerCriteria AND ".
				"$allowedRelationCriteria AND $notInPackageCriteria";

			return MediaFilePopulator::PopulateMultiple($criteria);
		}

		/**
		 * Return all of the media files not related to a given media file.
		 *
		 * @param MediaFile $mediaFile
		 * @param string $mediaRelationType Optional. The relation type of the
		 * files to return
		 * @param int $owner Optional. The ID of the owner of the media files.
		 * @return MediaFile[]
		 */
		public static function PopulateAllNotRelated(
			$mediaFile,
			$mediaRelationType = "",
			$owner = 0)
		{
			$relationCriteria = "1 = 1";

			if($mediaRelationType !== "")
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);
				$relationCriteria = "`media_relation_id` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_media_relation_id())."'";
			}

			$ownerCriteria = "1 = 1";

			if($owner > 0)
			{
				$ownerCriteria = "`member_id` = '".
					mysql_escape_string($owner)."'";
			}

			$criteria = "`media_file_id` <> '".
				Zymurgy::$db->escape_string($mediaFile->get_media_file_id()).
				"' AND $relationCriteria AND $ownerCriteria ".
				"AND NOT EXISTS(SELECT 1 FROM `zcm_media_file_relation` WHERE ".
				"`zcm_media_file_relation`.`related_media_file_id` = `zcm_media_file`.`media_file_id` AND ".
				"`zcm_media_file_relation`.`media_file_id` = '".
				Zymurgy::$db->escape_string($mediaFile->get_media_file_id()).
				"')";

			return MediaFilePopulator::PopulateMultiple($criteria);
		}

		private static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `media_file_id`, `member_id`, `mimetype`, `extension`, ".
				"`display_name`, `price`, `media_restriction_id`, `media_relation_id` ".
				"FROM `zcm_media_file` WHERE $criteria";

			// die($sql);

			$ri = Zymurgy::$db->query($sql)
				or die("Could not select list of media files: ".mysql_error().", $sql");
			$mediaFiles = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaFile = new MediaFile();

				$mediaFile->set_media_file_id($row["media_file_id"]);
				$mediaFile->set_mimetype($row["mimetype"]);
				$mediaFile->set_extension($row["extension"]);
				$mediaFile->set_display_name($row["display_name"]);
				$mediaFile->set_price($row["price"]);

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

			Zymurgy::$db->free_result($ri);

			// print_r($mediaFiles);
			// die();

			return $mediaFiles;
		}

		/**
		 * Populate a MemberFile object by the given ID in the database.
		 *
		 * @param int $media_file_id The ID of the MediaFile
		 * @return MediaFile
		 */
		public static function PopulateByID(
			$media_file_id)
		{
			$sql = "SELECT `member_id`, `mimetype`, `extension`, `display_name`, `price`, ".
				"`media_restriction_id`, `media_relation_id` FROM `zcm_media_file` ".
				"WHERE `media_file_id` = '".
				Zymurgy::$db->escape_string($media_file_id)."'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve media file record: ".mysql_error().", $sql");

			$mediaFile = null;

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaFile = new MediaFile();

				$mediaFile->set_media_file_id($media_file_id);
				$mediaFile->set_mimetype($row["mimetype"]);
				$mediaFile->set_extension($row["extension"]);
				$mediaFile->set_display_name($row["display_name"]);
				$mediaFile->set_price($row["price"]);

				$member = MediaMemberPopulator::PopulateByID(
					$row["member_id"]);
				$mediaFile->set_member($member);

				$restriction = MediaRestrictionPopulator::PopulateByID(
					$row["media_restriction_id"]);
				$mediaFile->set_restriction($restriction);

				$relation = MediaRelationPopulator::PopulateByID(
					$row["media_relation_id"]);
				$mediaFile->set_relation($relation);
			}

			Zymurgy::$db->free_result($ri);

			return $mediaFile;
		}

		/**
		 * Populate the list of related media files for a given media file.
		 * Only call this if you need to access the related media.
		 *
		 * @param MediaFile $base_media_file
		 * @param string $mediaRelationType Optional. The relation type of the
		 * files to return
		 */
		public static function PopulateRelatedMedia(
			$base_media_file,
			$mediaRelationType = '')
		{
			if($base_media_file == null) return;

			$criteria = "1 = 1";

			if($mediaRelationType !== '')
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);
				$criteria = "`media_relation_id` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_media_relation_id())."'";
			}

			$sql = "SELECT `related_media_file_id`, `media_relation_id` FROM ".
				"`zcm_media_file_relation` WHERE `media_file_id` = '".
				Zymurgy::$db->escape_string($base_media_file->get_media_file_id())."' ".
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

			Zymurgy::$db->free_result($ri);
		}

		/**
		 * Populate a MediaFile given the contents of a web form.
		 *
		 * @return MediaFile
		 */
		public static function PopulateFromForm()
		{
			$mediaFile = new MediaFile();

			$mediaFile->set_media_file_id($_POST["media_file_id"]);
			$mediaFile->set_mimetype($_POST["mimetype"]);
			$mediaFile->set_extension($_POST["extension"]);
			$mediaFile->set_member(
				MediaMemberPopulator::PopulateByID($_POST["member_id"]));
			$mediaFile->set_display_name($_POST["display_name"]);
			$mediaFile->set_price($_POST["price"]);

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

		/**
		 * Populate the file stream for the given media file. Only call this
		 * if you need to access the stream.
		 *
		 * @param MediaFile $mediaFile
		 * @param string $suffix Optional. The suffix to apply to the media
		 * file's name in the back-end. This is used mainly to access an
		 * image's thumbnail/cropped image.
		 * @return byte[] The media file's content stream.
		 */
		public static function GetFilestream(
			$mediaFile,
			$suffix = "")
		{
			$uploadfolder = Zymurgy::$config["Media File Local Path"];
			$filepath = $uploadfolder.
				"/".
				$mediaFile->get_media_file_id().
				$suffix.
				".".
				$mediaFile->get_extension();

			if(!file_exists($filepath) && $suffix !== "")
			{
				MediaFilePopulator::InitializeThumbnail(
					$mediaFile,
					str_replace("thumb", "", $suffix),
					true);
			}

			$file = fopen($filepath, "r");
			$content = "";
			while(!feof($file))
			{
				$content .= fread($file, 8192);
			}
			fclose($file);

			return $content;
		}

		/**
		 * Save the media file information to the database.
		 *
		 * @param MediaFile $mediaFile
		 */
		public static function SaveMediaFile(
			&$mediaFile)
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
					"`display_name`, `price`, `media_relation_id` ) VALUES ( '".
					Zymurgy::$db->escape_string($mediaFile->get_member()->get_member_id())."', '".
					Zymurgy::$db->escape_string($mediaFile->get_mimetype())."', '".
					Zymurgy::$db->escape_string($mediaFile->get_extension())."', '".
					Zymurgy::$db->escape_string($mediaFile->get_display_name())."', '".
					Zymurgy::$db->escape_string($mediaFile->get_price())."', '".
					Zymurgy::$db->escape_string($mediaFile->get_relation()->get_media_relation_id())."' )";

				Zymurgy::$db->query($sql)
					or die("Could not insert media file record: ".mysql_error().", $sql");

				$mediaFile->set_media_file_id(
					Zymurgy::$db->insert_id());
			}
			else
			{
				$sql = "UPDATE `zcm_media_file` SET ".
					"`member_id` = '".
					Zymurgy::$db->escape_string($mediaFile->get_member()->get_member_id()).
					"', `mimetype` = '".
					Zymurgy::$db->escape_string($mediaFile->get_mimetype()).
					"', `extension` = '".
					Zymurgy::$db->escape_string($mediaFile->get_extension()).
					"', `display_name` = '".
					Zymurgy::$db->escape_string($mediaFile->get_display_name()).
					"', `price` = '".
					Zymurgy::$db->escape_string($mediaFile->get_price()).
					"', `media_relation_id` = '".
					Zymurgy::$db->escape_string($mediaFile->get_relation()->get_media_relation_id()).
					"' WHERE `media_file_id` = '".
					Zymurgy::$db->escape_string($mediaFile->get_media_file_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update media file record: ".mysql_error().", $sql");
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

					if($mediaFile->get_relation() !== null
						&& strlen($mediaFile->get_relation()->get_thumbnails()) > 0)
					{
						MediaFilePopulator::InitializeThumbnails(
							$mediaFile);
					}
				}
			}
		}

		/**
		 * Remove the given media file from the database.
		 *
		 * @param MediaFile $mediaFile
		 */
		public static function DeleteMediaFile(
			$mediaFile)
		{
			$sql = "DELETE FROM `zcm_media_file_package` WHERE `media_file_id` = '".
				Zymurgy::$db->escape_string($mediaFile->get_media_file_id())."'";

			Zymurgy::$db->query($sql)
				or die("Could not delete media file record from packages: ".mysql_error().", $sql");

			$sql = "DELETE FROM `zcm_media_file` WHERE `media_file_id` = '".
				Zymurgy::$db->escape_string($mediaFile->get_media_file_id())."'";

			Zymurgy::$db->query($sql)
				or die("Could not delete media file record: ".mysql_error().", $sql");

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

		/**
		 * Add a media file to the list of related media for the given
		 * media file.
		 *
		 * @param int $media_file_id
		 * @param int $related_media_file_id
		 * @param int $media_relation_id
		 */
		public static function AddRelatedMedia(
			$media_file_id,
			$related_media_file_id,
			$media_relation_id)
		{
			$sql = "INSERT INTO `zcm_media_file_relation` ( `media_file_id`,".
				" `related_media_file_id`, `media_relation_id` ) VALUES ( '".
				Zymurgy::$db->escape_string($media_file_id)."', '".
				Zymurgy::$db->escape_string($related_media_file_id)."', '".
				Zymurgy::$db->escape_string($media_relation_id)."' )";

			// die($sql);

			Zymurgy::$db->query($sql)
				or die("Could not add related media: ".mysql_error().", $sql");
		}

		/**
		 * Remove a media file from the list of related media for the
		 * given media file.
		 *
		 * @param int $media_file_id
		 * @param int $related_media_file_id
		 */
		public static function DeleteRelatedMedia($media_file_id, $related_media_file_id)
		{
			$sql = "DELETE FROM `zcm_media_file_relation` WHERE `media_file_id` = '".
				Zymurgy::$db->escape_string($media_file_id).
				"' AND `related_media_file_id` = '".
				Zymurgy::$db->escape_string($related_media_file_id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete related media: ".mysql_error().", $sql");
		}

		/**
		 * Initialize the thumbnails for a given media file. The thumbnail sizes
		 * to initialize are specified in the media file's relation/file type
		 * data. This is typically called when the media file is first added, and
		 * when it is replaced.
		 *
		 * @param MediaFile $mediaFile
		 */
		public static function InitializeThumbnails($mediaFile)
		{
			$thumbnails = explode(",", $mediaFile->get_relation()->get_thumbnails());

			foreach($thumbnails as $thumbnail)
			{
				MediaFilePopulator::InitializeThumbnail($mediaFile, $thumbnail, true);
			}
		}

		/**
		 * Initialize a thumbnail at the given size for the given media file.
		 *
		 * @param MediaFile $mediaFile
		 * @param string $thumbnail The size of the thumbnail at ###x### format
		 * (i.e. 60x40, 800x600)
		 * @param boolean $forceUpdate Update the thumbnail, even if one exists
		 * at the given size.
		 */
		public static function InitializeThumbnail(
			$mediaFile,
			$thumbnail,
			$forceUpdate = false)
		{
			$uploadfolder = Zymurgy::$config["Media File Local Path"];
			$filepath = $uploadfolder.
				"/".
				$mediaFile->get_media_file_id();

			if($forceUpdate || !file_exists($filepath."raw.jpg"))
			{
				// echo(Zymurgy::$root.'/zymurgy/include/Thumb.php');
				// echo("<br>");
				require_once(Zymurgy::$root.'/zymurgy/include/Thumb.php');

				$dimensions = explode('x',$thumbnail);
				Thumb::MakeFixedThumb(
					$dimensions[0],
					$dimensions[1],
					$filepath.".".$mediaFile->get_extension(),
					$filepath."thumb".$thumbnail.".jpg");

				Thumb::MakeQuickThumb(
					640,
					480,
					$filepath.".".$mediaFile->get_extension(),
					$filepath."aspectcropNormal.jpg");

				system("{$ZymurgyConfig['ConvertPath']}convert -modulate 75 ".
					"{$filepath}aspectcropNormal.jpg {$filepath}aspectcropDark.jpg");

				copy(
					$filepath.".".$mediaFile->get_extension(),
					$filepath."raw.jpg");
			}
		}

		/**
		 * Create the files required for the thumber component at the given
		 * size for the given media file.
		 *
		 * @param MediaFile $mediaFile
		 * @param string $thumbnail The size of the thumbnail at ###x### format
		 * (i.e. 60x40, 800x600)
		 */
		public function MakeThumbnail($mediaFile, $thumbnail)
		{
			require_once('include/Thumb.php');

			$uploadfolder = Zymurgy::$config["Media File Local Path"];
			$filepath = $uploadfolder.
				"/".
				$mediaFile->get_media_file_id();

			list ($width,$height) = explode('x',$thumbnail,2);
			$work = array();
			list($work['w'], $work['h'], $type, $attr) = getimagesize($filepath."aspectcropNormal.jpg");
			$raw = array();
			list($raw['w'], $raw['h'], $type, $attr) = getimagesize($filepath."raw.jpg");

			$selected = array(
				'x'=>$_POST['cropX'],
				'y'=>$_POST['cropY'],
				'w'=>$_POST['cropWidth'],
				'h'=>$_POST['cropHeight']);

			//echo "[{$selected['x']},{$selected['y']},{$selected['w']},{$selected['h']}]<br>";
			//echo "raw [{$raw['w']},{$raw['h']}]<br>";

			//Math time...  Take 640x480 work image coordinates and figure out coordinates on full sized image.
			$xfactor = $raw['w'] / $work['w'];
			$yfactor = $raw['h'] / $work['h'];

			//echo "factors: $xfactor $yfactor<br>";

			$x = $selected['x'] * $xfactor;
			$y = $selected['y'] * $yfactor;
			$w = $selected['w'] * $xfactor;
			$h = $selected['h'] * $yfactor;

			$thumbpath = $filepath."thumb".$thumbnail.".jpg";
			Thumb::MakeThumb(
				$x,
				$y,
				$w,
				$h,
				$width,
				$height,
				$filepath."raw.jpg",
				$thumbpath);
		}

		static function GetTableDefinitions()
		{
			return array(
				MediaFilePopulator::GetTableDefinitions_zcm_media_file());
		}

		static function GetTableDefinitions_zcm_media_file()
		{
			return array(
				"name" => "zcm_media_file",
				"columns" => array(
					DefineTableField("media_file_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("member_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("mimetype", "VARCHAR(45)", "NOT NULL"),
					DefineTableField("extension", "VARCHAR(10)", "NOT NULL"),
					DefineTableField("display_name", "VARCHAR(100)", "NOT NULL"),
					DefineTableField("price", "INTEGER", "UNSIGNED"),
					DefineTableField("media_restriction_id", "DECIMAL(8,2)", "UNSIGNED"),
					DefineTableField("media_relation_id", "INTEGER", "UNSIGNED")
				),
				"indexes" => array(),
				"primarykey" => "media_file_id",
				"engine" => "InnoDB"
			);
		}
	}

	/**
	 * Contains the information and validation routines for a MediaPackage.
	 *
	 */
	class MediaPackage
	{
		private $m_media_package_id;
		private $m_display_name;
		private $m_price;

		private $m_member;
		private $m_restriction;
		private $m_packagetype;

		private $m_media_files = array();

		private $m_errors = array();

		/**
		 * Get the media package's ID, as stored in the database.
		 *
		 * @return int The media_package_id
		 */
		public function get_media_package_id()
		{
			return $this->m_media_package_id;
		}

		/**
		 * Set the media package's ID, as stored in the database.
		 *
		 * @param int $newValue
		 */
		public function set_media_package_id($newValue)
		{
			$this->m_media_package_id = $newValue;
		}

		/**
		 * Get the member information on the owner of the media package.
		 *
		 * @return MediaMember The MediaMember object describing the
		 * owner of the media package.
		 */
		public function get_member()
		{
			return $this->m_member;
		}

		/**
		 * Set the member information on the owner of the media file.
		 *
		 * @param MediaMember $newValue
		 */
		public function set_member($newValue)
		{
			$this->m_member = $newValue;
		}

		/**
		 * Get the name of the package to display on the front-end.
		 *
		 * @return string The name of the package.
		 */
		public function get_display_name()
		{
			return $this->m_display_name;
		}

		/**
		 * Set the nawe of the package to display on the front-end.
		 *
		 * @param string $newValue
		 */
		public function set_display_name($newValue)
		{
			$this->m_display_name = $newValue;
		}

		/**
		 * Get the price of the media package, when it is attached
		 * to a for-pay system on the web site.
		 *
		 * @return currency The price of the package
		 */
		public function get_price()
		{
			return $this->m_price;
		}

		/**
		 * Set the price of the media package, when it is attached
		 * to a for-pay system on the web site.
		 *
		 * @return currency The price of the package
		 */
		public function set_price($newValue)
		{
			$this->m_price = $newValue;
		}

		/**
		 * Get the object describing the download restrictions on the
		 * package.
		 *
		 * @return MediaRestriction The media restriction object
		 */
		public function get_restriction()
		{
			return $this->m_restriction;
		}

		/**
		 * Set the object describing the download restrictions on the
		 * package.
		 *
		 * @param MediaRestriction $newValue
		 */
		public function set_restriction($newValue)
		{
			$this->m_restriction = $newValue;
		}

		/**
		 * Get the object describing the package type.
		 *
		 * @return MediaPackageType The media package type object.
		 */
		public function get_packagetype()
		{
			return $this->m_packagetype;
		}

		/**
		 * Set the object describing the package type.
		 *
		 * @param MediaPackageType $newValue
		 */
		public function set_packagetype($newValue)
		{
			$this->m_packagetype = $newValue;
		}

		/**
		 * Get the information on a media file that is part of
		 * this package.
		 *
		 * @param int $key
		 * @return MediaFile The media file at the given key.
		 */
		public function get_media_file($key)
		{
			return $this->m_media_files[$key];
		}

		/**
		 * Get the number of media files in the package. Note that if
		 * the array was populated using a relation filter, this will
		 * only count files that match the filter.
		 *
		 * @return int The file count
		 */
		public function get_media_file_count()
		{
			return count($this->m_media_files);
		}

		/**
		 * Set the media file information for the media file at
		 * the given key.
		 *
		 * @param int $key
		 * @param MediaFile $newValue
		 */
		public function set_media_file($key, $newValue)
		{
			$this->m_media_files[$key] = $newValue;
		}

		/**
		 * Add the given media file to the package.
		 *
		 * @param MediaFile $media_file
		 * @return int The key of the newly added media file.
		 */
		public function add_media_file($media_file)
		{
			$this->m_media_files[] = $media_file;

			return count($this->m_media_files) - 1;
		}

		/**
		 * Remove the given media file from the package.
		 *
		 * @param int $key The key of the file to remove from the package
		 */
		public function delete_media_file($key)
		{
			unset($this->m_media_files[$key]);
		}

		/**
		 * Clear the list of media files in the package.
		 */
		public function clear_media_files()
		{
			$this->m_media_files = array();
		}

		/**
		 * Get the list of errors in the media package object. This is
		 * populated as part of the validate() method, and is typically
		 * called as part of the save process during data input.
		 *
		 * @return string[] The list of errors in the object.
		 */
		public function get_errors()
		{
			return $this->m_errors;
		}

		/**
		 * Validate the media package object. This is typically called as
		 * part of the save process during data input. If there are
		 * errors, the errors array will be populated, and may be retrieved
		 * by calling get_errors().
		 *
		 * @param string $action The controller action. Used to enable add-only
		 * and edit-only specific validations. The supported actions are
		 * "act_add_media_package" and "act_edit_media_package".
		 * @return boolean True if the data is valid. Otherwise false.
		 */
		public function validate(
			$action)
		{
			$isValid = true;

			if(strlen($this->m_display_name) <= 0)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString("MediaPackage.Validate.DisplayNameRequired");
				$isValid = false;
			}

			return $isValid;
		}

		/**
		 * Move the given media file either up or down within the
		 * package.
		 *
		 * @param int $mediaFileID The ID of the media file to move.
		 * @param int $offset The offset. To move the file up, set this to -1.
		 * To move the file down, set this to 1.
		 */
		public function MoveMediaFile(
			$mediaFileID,
			$offset)
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

		/**
		 * Ensure the display order indexes for the media files
		 * within the package are contiguous.
		 */
		public function FixMediaFileOrder()
		{
			for($cntr = 0; $cntr < count($this->m_media_files); $cntr++)
			{
				$this->m_media_files[$cntr]->set_disporder($cntr + 1);
			}
		}
	}

	/**
	 * Contains a set of static methods used to populate either a single
	 * MediaPackage object, or an array of MediaPackage objects. Most of the
	 * methods populate the MediaPackage object from the database, but the
	 * object may also be populated from a web form.
	 *
	 */
	class MediaPackagePopulator
	{
		/**
		 * Return all of the media packages in the database.
		 *
		 * @return MediaPackage[]
		 */
		public static function PopulateAll()
		{
			return MediaPackagePopulator::PopulateMultiple(
				"1 = 1");
		}

		/**
		 * Return all of the media packages owned by the given member.
		 *
		 * @param int $member_id Optional. The ID of the owner of the
		 * packages to return.
		 * @param string $packageType Optional. The name of the package
		 * type of packages to return.
		 * @return MediaPackage[]
		 */
		public static function PopulateByOwner(
			$member_id = 0,
			$packageType = "")
		{
			$ownerCriteria = "1 = 1";
			$typeCriteria = "1 = 1";

			if($member_id > 0)
			{
				$ownerCriteria = "`zcm_media_package`.`member_id` = '".
					Zymurgy::$db->escape_string($member_id).
					"'";
			}

			if($packageType !== "")
			{
				$typeCriteria = "EXISTS(SELECT 1 FROM `zcm_media_package_type` WHERE `zcm_media_package_type`.`media_package_type_id` = `zcm_media_package`.`media_package_type_id` AND `zcm_media_package_type`.`package_type` = '".$packageType."')";
			}

			return MediaPackagePopulator::PopulateMultiple(
				"$ownerCriteria AND $typeCriteria");
		}

		private static function PopulateMultiple($criteria)
		{
			$sql = "SELECT `media_package_id`, `media_restriction_id`, `member_id`,".
				" `display_name`, `price`, `media_package_type_id` ".
				"FROM `zcm_media_package` WHERE $criteria";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve list of packages: ".mysql_error().", $sql");

			$mediaPackages = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$mediaPackage = new MediaPackage();

				$mediaPackage->set_media_package_id($row["media_package_id"]);
				$mediaPackage->set_display_name($row["display_name"]);
				$mediaPackage->set_price($row["price"]);

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

			Zymurgy::$db->free_result($ri);

			return $mediaPackages;
		}

		/**
		 * Return a media package, given it's media_package_id in the database.
		 *
		 * @param int $media_package_id
		 * @return MediaPackage
		 */
		public static function PopulateByID($media_package_id)
		{
			$sql = "SELECT `media_restriction_id`, `member_id`, `display_name`, ".
				"`price`, `media_package_type_id` FROM `zcm_media_package` WHERE ".
				"`media_package_id` = '".
				Zymurgy::$db->escape_string($media_package_id)."'";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve media package: ".mysql_error().", $sql");

			$mediaPackage = null;

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaPackage = new MediaPackage();

				$mediaPackage->set_media_package_id($media_package_id);
				$mediaPackage->set_display_name($row["display_name"]);
				$mediaPackage->set_price($row["price"]);

				$member = MediaMemberPopulator::PopulateByID(
					$row["member_id"]);
				$mediaPackage->set_member($member);

				$restriction = MediaRestrictionPopulator::PopulateByID(
					$row["media_restriction_id"]);
				$mediaPackage->set_restriction($restriction);

				$packageType = MediaPackageTypePopulator::PopulateByID(
					$row["media_package_type_id"]);
				$mediaPackage->set_packagetype($packageType);
			}

			Zymurgy::$db->free_result($ri);

			return $mediaPackage;
		}

		/**
		 * Populate the list of media files in a given package.
		 *
		 * @param MediaPackage $mediaPackage The media package to populate
		 * @param string $mediaRelationType Optional. The relation type to
		 * filter the media files by.
		 */
		public static function PopulateMediaFiles(
			&$mediaPackage,
			$mediaRelationType = '')
		{
			$criteria = "1 = 1";

			if($mediaRelationType !== '')
			{
				$mediaRelation = MediaRelationPopulator::PopulateByType($mediaRelationType);
				$criteria = "`media_relation_id` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_media_relation_id())."'";
			}

			$sql = "SELECT `media_file_id`, `disporder`, `media_relation_id` ".
				"FROM `zcm_media_file_package` WHERE `media_package_id` = '".
				Zymurgy::$db->escape_string($mediaPackage->get_media_package_id()).
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

			Zymurgy::$db->free_result($ri);
		}

		/**
		 * Populate a MediaPackage object based on the contents of a form.
		 *
		 * @return MediaPackage
		 */
		public static function PopulateFromForm()
		{
			$mediaPackage = new MediaPackage();

			$mediaPackage->set_media_package_id($_POST["media_package_id"]);
			$mediaPackage->set_display_name($_POST["display_name"]);
			$mediaPackage->set_price($_POST["price"]);

			$mediaMember = MediaMemberPopulator::PopulateByID(
				$_POST["member_id"]);
			$mediaPackage->set_member($mediaMember);

			$packageType = MediaPackageTypePopulator::PopulateByID(
				$_POST["media_package_type_id"]);
			$mediaPackage->set_packagetype($packageType);

			return $mediaPackage;
		}

		/**
		 * Save the information on a given MediaPackage to the database.
		 *
		 * @param MediaPackage $mediaPackage
		 */
		public static function SaveMediaPackage($mediaPackage)
		{
			$sql = "";

			if($mediaPackage->get_media_package_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_package` ( `member_id`, ".
					"`media_package_type_id`, `display_name`, `price` ) VALUES ( '".
					Zymurgy::$db->escape_string($mediaPackage->get_member()->get_member_id()).
					"', '".
					Zymurgy::$db->escape_string($mediaPackage->get_packagetype()->get_media_package_type_id()).
					"', '".
					Zymurgy::$db->escape_string($mediaPackage->get_display_name()).
					"', '".
					Zymurgy::$db->escape_string($mediaPackage->get_price()).
					"' )";

				Zymurgy::$db->query($sql)
					or die("Could not insert media file record: ".mysql_error().", $sql");

				$mediaPackage->set_media_package_id(
					Zymurgy::$db->insert_id());

				$sql = "SELECT MAX(`media_package_id`) FROM `zcm_media_package`";

				$mediaPackage->set_media_package_id(
					Zymurgy::$db->get($sql));
			}
			else
			{
				$sql = "UPDATE `zcm_media_package` SET ".
					"`member_id` = '".
					Zymurgy::$db->escape_string($mediaPackage->get_member()->get_member_id()).
					"', `media_package_type_id` = '".
					Zymurgy::$db->escape_string($mediaPackage->get_packagetype()->get_media_package_type_id()).
					"', `display_name` = '".
					Zymurgy::$db->escape_string($mediaPackage->get_display_name()).
					"', `price` = '".
					Zymurgy::$db->escape_string($mediaPackage->get_price()).
					"' WHERE `media_package_id` = '".
					Zymurgy::$db->escape_string($mediaPackage->get_media_package_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update media file record: ".mysql_error().", $sql");
			}
		}

		/**
		 * Remove a given media package from the database.
		 *
		 * @param int $media_package_id
		 */
		public static function DeleteMediaPackage($media_package_id)
		{
			$sql = "DELETE FROM `zcm_media_file_package` WHERE `media_package_id` = '".
				Zymurgy::$db->escape_string($media_package_id)."'";

			Zymurgy::$db->query($sql)
				or die("Could not delete media package record: ".mysql_error().", $sql");

			$sql = "DELETE FROM `zcm_media_package` WHERE `media_package_id` = '".
				Zymurgy::$db->escape_string($media_package_id)."'";

			Zymurgy::$db->query($sql)
				or die("Could not delete media package record: ".mysql_error().", $sql");
		}

		/**
		 * Add a media file to a given media package.
		 *
		 * @param int $media_package_id
		 * @param int $media_file_id
		 * @param int $media_relation_id
		 * @param int $disporder
		 */
		public static function AddMediaFileToPackage(
			$media_package_id,
			$media_file_id,
			$media_relation_id,
			$disporder)
		{
			$sql = "INSERT INTO `zcm_media_file_package` ( `media_package_id`, ".
				"`media_file_id`, `media_relation_id`, `disporder` ) VALUES ('".
				Zymurgy::$db->escape_string($media_package_id)."', '".
				Zymurgy::$db->escape_string($media_file_id)."', '".
				Zymurgy::$db->escape_string($media_relation_id)."', '".
				Zymurgy::$db->escape_string($disporder)."')";

			Zymurgy::$db->query($sql)
				or die("Could not add media file to media package: ".mysql_error().", $sql");
		}

		/**
		 * Save the updated display order for all of the media files
		 * in a given media package.
		 *
		 * @param MediaPackage $mediaPackage
		 */
		public static function SaveMediaFileOrder($mediaPackage)
		{
			for($cntr = 0; $cntr < $mediaPackage->get_media_file_count(); $cntr++)
			{
				$mediaFile = $mediaPackage->get_media_file($cntr);

				$sql = "UPDATE `zcm_media_file_package` SET `disporder` = '".
					Zymurgy::$db->escape_string($mediaFile->get_disporder()).
					"' WHERE `media_package_id` = '".
					Zymurgy::$db->escape_string($mediaPackage->get_media_package_id()).
					"' AND `media_file_id` = '".
					Zymurgy::$db->escape_string($mediaFile->get_media_file_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not re-order media file in package: ".mysql_error());
			}
		}

		/**
		 * Remove the given media file from the given media package.
		 *
		 * @param int $media_package_id
		 * @param int $media_file_id
		 */
		public static function DeleteMediaFileFromPackage(
			$media_package_id,
			$media_file_id)
		{
			$sql = "DELETE FROM `zcm_media_file_package` WHERE `media_package_id` = '".
				Zymurgy::$db->escape_string($media_package_id).
				"' AND `media_file_id` = '".
				Zymurgy::$db->escape_string($media_file_id).
				"'";

			Zymurgy::$db->query($sql)
				or die("Could not delete media file from media package: ".mysql_error().", $sql");
		}

		/**
		 * Swap the given media files within a package. This method
		 * is used to re-order items within a package.
		 *
		 * @param MediaPackage $mediaPackage
		 * @param MediaFile $mediaFile1
		 * @param MediaFile $mediaFile2
		 * @param string $relationType
		 */
		public static function SwapMediaFiles(
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

		/**
		 * Build an return a ZIP file containing all of the media
		 * files in a given package. This method assumes that the
		 * list of media files in the package has already been populated.
		 *
		 * @param MediaPackage $mediaPackage
		 * @param boolean $forceRebuild If true, the ZIP file is regenerated
		 * even if the file already exists. Otherwise, the cached version of
		 * the file will be returned if it is available.
		 */
		public static function BuildZipFile($mediaPackage, $forceRebuild)
		{
			$zip = new ZipArchive();
			$uploadfolder = Zymurgy::$config["Media File Local Path"];
			$filepath = $uploadfolder.
				"/".
				$mediaPackage->get_media_package_id().
				".zip";

			$build = true;

			if(file_exists($filepath))
			{
				if($forceRebuild)
				{
					unlink($filepath);
				}
				else
				{
					$build = false;
				}
			}

			if($build)
			{
				if($zip->open($filepath, ZIPARCHIVE::CREATE) !== TRUE)
				{
					die("Cannot create $filepath");
				}
				$zip->addFromString(
					"0. Downloaded from ".$_SERVER['HTTP_HOST'].".txt",
					"File generated on ".date("Y/M/d"));
				$zip->close();

				for($cntr = 0; $cntr < $mediaPackage->get_media_file_count(); $cntr++)
				{
					$mediaFile = $mediaPackage->get_media_file($cntr);

					if($zip->open($filepath) !== TRUE)
					{
						die("Cannot open $filepath to add media file");
					}

					// echo($mediaFile->get_display_name()."<br>");

					$zip->addFile(
						$uploadfolder."/".$mediaFile->get_media_file_id().".".$mediaFile->get_extension(),
						($cntr + 1).". ".$mediaFile->get_display_name().".".$mediaFile->get_extension());

					$zip->close();
				}
			}
		}

		static function GetTableDefinitions()
		{
			return array(
				MediaPackagePopulator::GetTableDefinitions_zcm_media_package(),
				MediaPackagePopulator::GetTableDefinitions_zcm_media_file_package());
		}

		static function GetTableDefinitions_zcm_media_package()
		{
			return array(
				"name" => "zcm_media_package",
				"columns" => array(
					DefineTableField("media_package_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("member_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("display_name", "VARCHAR(100)", "NOT NULL"),
					DefineTableField("price", "INTEGER", "UNSIGNED"),
					DefineTableField("media_restriction_id", "DECIMAL(8,2)", "UNSIGNED"),
					DefineTableField("media_package_type_id", "INTEGER", "UNSIGNED NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "media_package_id",
				"engine" => "InnoDB"
			);
		}

		static function GetTableDefinitions_zcm_media_file_package()
		{

			return array(
				"name" => "zcm_media_file_package",
				"columns" => array(
					DefineTableField("media_file_package_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("media_file_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("media_package_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("disporder", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("media_relation_id", "INTEGER", "UNSIGNED NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "media_file_package_id",
				"engine" => "InnoDB"
			);
		}
	}

	/**
	 * Contains the information and validation routines for a
	 * MediaPackageType.
	 *
	 */
	class MediaPackageType
	{
		private $m_media_package_type_id;
		private $m_package_type;
		private $m_package_label;
		private $m_builtin;

		private $m_allowedrelations = array();

		private $m_errors = array();

		/**
		 * Get the media package type's ID, as stored in the database.
		 *
		 * @return int The media_package_type_id
		 */
		public function get_media_package_type_id()
		{
			return $this->m_media_package_type_id;
		}

		/**
		 * Set the media package type's ID, as stored in the database.
		 *
		 * @param int $newValue
		 */
		public function set_media_package_type_id($newValue)
		{
			$this->m_media_package_type_id = $newValue;
		}

		/**
		 * Gets the media package type's one-word text descriptor. This
		 * descriptor is used to differentiate between types in code, without
		 * having to use either the user-friendly descriptor (which could change
		 * based on the client's vocabulary) or the database ID (which could
		 * change between deployments).
		 *
		 * @return string The package type descriptor
		 */
		public function get_package_type()
		{
			return $this->m_package_type;
		}

		/**
		 * Sets the media package type's one-word text descriptor. This
		 * descriptor is used to differentiate between types in code, without
		 * having to use either the user-friendly descriptor (which could change
		 * based on the client's vocabulary) or the database ID (which could
		 * change between deployments).
		 *
		 * @param string $newValue
		 */
		public function set_package_type($newValue)
		{
			$this->m_package_type = $newValue;
		}

		/**
		 * Gets the media package type's user friendly label.
		 *
		 * @return string The package label
		 */
		public function get_package_label()
		{
			return $this->m_package_label;
		}

		/**
		 * Sets the media package type's user friendly label.
		 *
		 * @param string $newValue
		 */
		public function set_package_label($newValue)
		{
			$this->m_package_label = $newValue;
		}

		/**
		 * Get the built-in status of the package type. Users are not able
		 * to delete built-in package types using the GUI.
		 *
		 * @return boolean True, if the package type is built-in.
		 */
		public function get_builtin()
		{
			return $this->m_builtin;
		}

		/**
		 * Set the built-in status of the package type.
		 *
		 * @param boolean $newValue
		 */
		public function set_builtin($newValue)
		{
			$this->m_builtin = $newValue;
		}

		/**
		 * Gets the information on the allowed relation type based
		 * on its key within the array.
		 *
		 * @param int $key
		 * @return MediaPackageTypeAllowedRelation
		 */
		public function get_allowedrelation($key)
		{
			return $this->m_allowedrelations[$key];
		}

		/**
		 * Returns the number of allowed relations for this media
		 * package type.
		 *
		 * @return int
		 */
		public function get_allowedrelation_count()
		{
			return count($this->m_allowedrelations);
		}

		/**
		 * Sets the information on an allowed relation for this
		 * media package type at the given key.
		 *
		 * @param int $key
		 * @param MediaPackageTypeAllowedRelation $newValue
		 */
		public function set_allowedrelation($key, $newValue)
		{
			$this->m_allowedrelations[$key] = $newValue;
		}

		/**
		 * Add a relation type to the list of allowed relations for
		 * this media package
		 *
		 * @param MediaPackageTypeAllowedRelation $allowedrelation
		 * @return int The key for the newly added allowed media
		 * relation
		 */
		public function add_allowedrelation($allowedrelation)
		{
			$this->m_allowedrelations[] = $allowedrelation;

			return count($this->m_allowedrelations) - 1;
		}

		/**
		 * Remove the allowed relation for this media package at the
		 * given key.
		 *
		 * @param int $key
		 */
		public function delete_allowedrelation($key)
		{
			unset($this->m_allowedrelations[$key]);
		}

		/**
		 * Clear the list of allowed relations for this media package.
		 *
		 */
		public function clear_allowedrelations()
		{
			$this->m_allowedrelations = array();
		}

		/**
		 * Get the list of errors in the media package type object. This is
		 * populated as part of the validate() method, and is typically
		 * called as part of the save process during data input.
		 *
		 * @return string[] The list of errors in the object.
		 */
		public function get_errors()
		{
			return $this->m_errors;
		}

		/**
		 * Validate the media package type object. This is typically called as
		 * part of the save process during data input. If there are
		 * errors, the errors array will be populated, and may be retrieved
		 * by calling get_errors().
		 *
		 * @param string $action The controller action. Used to enable add-only
		 * and edit-only specific validations. The supported actions are
		 * "act_add_media_package_type" and "act_edit_media_package_type".
		 * @return boolean True if the data is valid. Otherwise false.
		 */
		public function validate($action)
		{
			$isValid = true;

			if(strlen($this->m_package_type) <= 0)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString("MediaPackageType.Validate.TypeRequired");
				$isValid = false;
			}

			if(strlen($this->m_package_label) <= 0)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString("MediaPackageType.Validate.LabelRequired");
				$isValid = false;
			}

			return $isValid;
		}
	}

	/**
	 * Contains the information and validation routines for a
	 * MediaPackageTypeAllowedRelation.
	 *
	 */
	class MediaPackageTypeAllowedRelation
	{
		private $m_media_package_type_allowed_relation_id;
		private $m_media_package_type_id;
		private $m_media_relation_id;
		private $m_maxinstances;

		private $m_relation;

		private $m_errors = array();

		/**
		 * Get the relation's ID, as stored in the database.
		 *
		 * @return int The media_package_type_allowed_relation_id
		 */
		public function get_media_package_type_allowed_relation_id()
		{
			return $this->m_media_package_type_allowed_relation_id;
		}

		/**
		 * Set the media relation's ID, as stored in the database.
		 *
		 * @param int $newValue
		 */
		public function set_media_package_type_allowed_relation_id($newValue)
		{
			$this->m_media_package_type_allowed_relation_id = $newValue;
		}

		/**
		 * Get the ID of the media package type this relation is
		 * associated with, as stored in the database.
		 *
		 * @return int the media_package_type_id
		 */
		public function get_media_package_type_id()
		{
			return $this->m_media_package_type_id;
		}

		/**
		 * Get the ID of the media package type this relation is
		 * associated with, as stored in the database.
		 *
		 * @param int $newValue
		 */
		public function set_media_package_type_id($newValue)
		{
			$this->m_media_package_type_id = $newValue;
		}

		/**
		 * Get the maximum number of instances of this relation/
		 * file type a given package may contain.
		 *
		 * @return int
		 */
		public function get_maxinstances()
		{
			return $this->m_maxinstances;
		}

		/**
		 * Set the maximum number of instances of this relation/
		 * file type a given package may contain.
		 *
		 * @param int $newValue
		 */
		public function set_maxinstances($newValue)
		{
			$this->m_maxinstances = $newValue;
		}

		/**
		 * Get the information on the relation being associated
		 * with the media package type.
		 *
		 * @return MediaRelation
		 */
		public function get_relation()
		{
			return $this->m_relation;
		}

		/**
		 * Set the relation being associated with the media package type.
		 *
		 * @param MediaRelation $newValue
		 */
		public function set_relation($newValue)
		{
			$this->m_relation = $newValue;
		}

		/**
		 * Get the list of errors in the relation object. This is
		 * populated as part of the validate() method, and is typically
		 * called as part of the save process during data input.
		 *
		 * @return string[] The list of errors in the object.
		 */
		public function get_errors()
		{
			return $this->m_errors;
		}

		/**
		 * Validate the relation object. This is typically called as
		 * part of the save process during data input. If there are
		 * errors, the errors array will be populated, and may be retrieved
		 * by calling get_errors().
		 *
		 * @param string $action The controller action. Used to enable add-only
		 * and edit-only specific validations.
		 * @return boolean True if the data is valid. Otherwise false.
		 */
		public function validate($action)
		{
			$isValid = true;

			if($this->m_relation == null)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString(
					"MediaPackageTypeAllowedRelation.Validate.RelationRequired");
				$isValid = false;
			}

			if(strlen($this->m_maxinstances) <= 0)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString(
					"MediaPackageTypeAllowedRelation.Validate.MaxInstancesRequired");
				$isValid = false;
			}
			else
			{
				if(!is_numeric($this->m_maxinstances))
				{
					$this->m_errors[] = Zymurgy::GetLocaleString(
						"MediaPackageTypeAllowedRelation.Validate.MaxInstancesFormat");
					$isValid = false;
				}
			}

			return $isValid;
		}
	}

	/**
	 * Contains a set of static methods used to populate either a single
	 * MediaPackage object, or an array of MediaPackage objects. Most of the
	 * methods populate the MediaPackage object from the database, but the
	 * object may also be populated from a web form.
	 *
	 */
	class MediaPackageTypePopulator
	{
		/**
		 * Return all of the package types defined in the database.
		 *
		 * @return MediaPackageType[]
		 */
		public static function PopulateAll()
		{
			$sql = "SELECT `media_package_type_id`, `package_type`, ".
				"`package_type_label`, `builtin` FROM `zcm_media_package_type`";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve list of package types: ".mysql_error().", $sql");

			$packageTypes = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$packageType = new MediaPackageType();

				$packageType->set_media_package_type_id($row["media_package_type_id"]);
				$packageType->set_package_type($row["package_type"]);
				$packageType->set_package_label($row["package_type_label"]);
				$packageType->set_builtin($row["builtin"]);

				$packageTypes[] = $packageType;
			}

			Zymurgy::$db->free_result($ri);

			return $packageTypes;
		}

		/**
		 * Return the information on a single media package type,
		 * given it's ID in the database.
		 *
		 * @param int $media_package_type_id
		 * @return MediaPackageType
		 */
		public static function PopulateByID(
			$media_package_type_id)
		{
			$sql = "SELECT `package_type`, `package_type_label`, `builtin` FROM ".
				"`zcm_media_package_type` WHERE `media_package_type_id` = '".
				Zymurgy::$db->escape_string($media_package_type_id).
				"'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve package type: ".mysql_error().", $sql");

			$packageType = null;

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$packageType = new MediaPackageType();

				$packageType->set_media_package_type_id($media_package_type_id);
				$packageType->set_package_type($row["package_type"]);
				$packageType->set_package_label($row["package_type_label"]);
				$packageType->set_builtin($row["builtin"]);
			}

			Zymurgy::$db->free_result($ri);

			return $packageType;
		}

		/**
		 * Return the information on a single media package type, given it's
		 * one-word text descriptor.
		 *
		 * @param string $package_type
		 * @return MediaPackageType
		 */
		public static function PopulateByType(
			$package_type)
		{
			$sql = "SELECT `media_package_type_id`, `package_type_label`, `builtin` FROM ".
				"`zcm_media_package_type` WHERE `package_type` = '".
				Zymurgy::$db->escape_string($package_type).
				"'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve package type: ".mysql_error().", $sql");

			$packageType = null;

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$packageType = new MediaPackageType();

				$packageType->set_media_package_type_id($row["media_package_type_id"]);
				$packageType->set_package_type($package_type);
				$packageType->set_package_label($row["package_type_label"]);
				$packageType->set_builtin($row["builtin"]);
			}

			Zymurgy::$db->free_result($ri);

			return $packageType;
		}

		/**
		 * Populate the list of allowed relations for a given package type.
		 *
		 * @param MediaPackageType $packageType
		 */
		public static function PopulateAllowedRelations(
			&$packageType)
		{
			$sql = "SELECT `media_package_type_allowed_relation_id`, ".
				"`media_relation_id`, `max_instances` FROM ".
				"`zcm_media_package_type_allowed_relation` WHERE ".
				"`media_package_type_id` = '".
				Zymurgy::$db->escape_string($packageType->get_media_package_type_id()).
				"'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve allowed relations: ".mysql_error().", $sql");

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$allowedRelation = new MediaPackageTypeAllowedRelation();

				$allowedRelation->set_media_package_type_allowed_relation_id(
					$row["media_package_type_allowed_relation_id"]);
				$allowedRelation->set_media_package_type_id(
					$packageType->get_media_package_type_id());
				$allowedRelation->set_maxinstances($row["max_instances"]);

				$relation = MediaRelationPopulator::PopulateByID(
					$row["media_relation_id"]);
				$allowedRelation->set_relation($relation);

				$packageType->add_allowedrelation($allowedRelation);
			}

			Zymurgy::$db->free_result($ri);
		}

		/**
		 * Return the information on a single allowed relation, given the
		 * relation's ID in the database.
		 *
		 * @param int $allowedRelationID
		 * @return MediaPackageTypeAllowedRelation
		 */
		public static function PopulateAllowedRelationByID(
			$allowedRelationID)
		{
			$sql = "SELECT `media_package_type_id`, `media_relation_id`, ".
				"`max_instances` FROM `zcm_media_package_type_allowed_relation` ".
				"WHERE `media_package_type_allowed_relation_id` = '".
				Zymurgy::$db->escape_string($allowedRelationID)."'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve allowed relations: ".mysql_error().", $sql");

			$allowedRelation = null;

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
			}

			Zymurgy::$db->free_result($ri);

			return $allowedRelation;
		}

		/**
		 * Populate a MediaPackageType object, given the contents of a web form.
		 *
		 * @return MediaPackageType
		 */
		public static function PopulateFromForm()
		{
			$packageType = new MediaPackageType();

			$packageType->set_media_package_type_id($_POST["media_package_type_id"]);
			$packageType->set_package_type($_POST["package_type"]);
			$packageType->set_package_label($_POST["package_label"]);

			return $packageType;
		}

		/**
		 * Populate a MediaPackageTypeAllowedRelation object, given the contents
		 * of a web form.
		 *
		 * @return MediaPackageTypeAllowedRelation
		 */
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

		/**
		 * Save the information on a media package type to the database.
		 *
		 * Note that this method does not alter the builtin flag on the record,
		 * as that cannot be manipulated through the GUI.
		 *
		 * @param MediaPackageType $packageType
		 */
		public static function SaveMediaPackageType($packageType)
		{
			$sql = "";

			if($packageType->get_media_package_type_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_package_type` ( `package_type`, ".
					"`package_type_label` ) VALUES ( '".
					Zymurgy::$db->escape_string($packageType->get_package_type())."', '".
					Zymurgy::$db->escape_string($packageType->get_package_label())."')";

				Zymurgy::$db->query($sql)
					or die("Could not insert package type record: ".mysql_error().", $sql");

				$packageType->set_media_package_type_id(
					Zymurgy::$db->insert_id());
			}
			else
			{
				$sql = "UPDATE `zcm_media_package_type` SET `package_type` = '".
					Zymurgy::$db->escape_string($packageType->get_package_type()).
					"', `package_type_label` = '".
					Zymurgy::$db->escape_string($packageType->get_package_label()).
					"' WHERE `media_package_type_id` = '".
					Zymurgy::$db->escape_string($packageType->get_media_package_type_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update package type record: ".mysql_error().", $sql");
			}
		}

		/**
		 * Remove a media package type from the database.
		 *
		 * @param int $media_package_type_id The ID of the media package type to
		 * remove, as stored in the database.
		 */
		public static function DeleteMediaPackageType($media_package_type_id)
		{
			$sql = "DELETE FROM `zcm_media_package_type` WHERE `media_package_type_id` = '".
				Zymurgy::$db->escape_string($media_package_type_id)."'";

			Zymurgy::$db->query($sql)
				or die("Could not delete package type: ".mysql_error().", $sql");
		}

		/**
		 * Save the information on an allowed relation type for a media package
		 * type to the database.
		 *
		 * @param MediaPackageTypeAllowedRelation $allowedRelation
		 */
		public static function SaveMediaPackageTypeAllowedRelation($allowedRelation)
		{
			$sql = "";

			if($allowedRelation->get_media_package_type_allowed_relation_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_package_type_allowed_relation` ( ".
					"`media_package_type_id`, `media_relation_id`, `max_instances` ) VALUES ( '".
					Zymurgy::$db->escape_string($allowedRelation->get_media_package_type_id()).
					"', '".
					Zymurgy::$db->escape_string($allowedRelation->get_relation()->get_media_relation_id()).
					"', '".
					Zymurgy::$db->escape_string($allowedRelation->get_maxinstances()).
					"')";

				Zymurgy::$db->query($sql)
					or die("Could not insert allowed relation record: ".mysql_error().", $sql");

				$allowedRelation->set_media_package_type_allowed_relation_id(
					Zymurgy::$db->insert_id());
			}
			else
			{
				$sql = "UPDATE `zcm_media_package_type_allowed_relation` SET ".
					"`media_package_type_id` = '".
					Zymurgy::$db->escape_string($allowedRelation->get_media_package_type_id()).
					"', `media_relation_id` = '".
					Zymurgy::$db->escape_string($allowedRelation->get_relation()->get_media_relation_id()).
					"', `max_instances` = '".
					Zymurgy::$db->escape_string($allowedRelation->get_maxinstances()).
					"' WHERE `media_package_type_allowed_relation_id` = '".
					Zymurgy::$db->escape_string($allowedRelation->get_media_package_type_allowed_relation_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update allowed relation record: ".mysql_error().", $sql");
			}
		}

		/**
		 * Remove an allowed relation for a media package type from the database.
		 *
		 * @param int $allowedRelationID The ID of the relation to
		 * remove, as stored in the database.
		 */
		public static function DeleteMediaPackageTypeAllowedRelation($allowedRelationID)
		{
			$sql = "DELETE FROM `zcm_media_package_type_allowed_relation` ".
				"WHERE `media_package_type_allowed_relation_id` = '".
				Zymurgy::$db->escape_string($allowedRelationID)."'";

			Zymurgy::$db->query($sql)
				or die("Could not delete allowed relation: ".mysql_error().", $sql");
		}

		static function GetTableDefinitions()
		{
			return array(
				MediaPackageTypePopulator::GetTableDefinitions_zcm_media_package_type(),
				MediaPackageTypePopulator::GetTableDefinitions_zcm_media_package_type_allowed_relation());
		}

		static function GetTableDefinitions_zcm_media_package_type()
		{
			return array(
				"name" => "zcm_media_package_type",
				"columns" => array(
					DefineTableField("media_package_type_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("package_type", "VARCHAR(50)", "NOT NULL"),
					DefineTableField("package_type_label", "VARCHAR(50)", "NOT NULL"),
					DefineTableField("builtin", "INTEGER", "NOT NULL DEFAULT '0'")
				),
				"indexes" => array(),
				"primarykey" => "media_package_type_id",
				"engine" => "InnoDB"
			);
		}

		static function GetTableDefinitions_zcm_media_package_type_allowed_relation()
		{
			return array(
				"name" => "zcm_media_package_type_allowed_relation",
				"columns" => array(
					DefineTableField("media_package_type_allowed_relation_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("media_package_type_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("media_relation_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("max_instances", "INTEGER", "UNSIGNED NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "media_package_type_allowed_relation_id",
				"engine" => "InnoDB"
			);
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
				Zymurgy::$db->escape_string($restriction_id)."'";

			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve media restriction: ".mysql_error().", $sql");

			$mediaRestriction = null;

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaRestriction = new MediaRestriction();

				$mediaRestriction->set_restriction_id($restriction_id);
				$mediaRestriction->set_download_limit($row["download_limit"]);
				$mediaRestriction->set_day_limit($row["day_limit"]);
			}

			Zymurgy::$db->free_result($ri);

			return $mediaRestriction;
		}

		static function GetTableDefinitions()
		{
			return array(
				MediaRestrictionPopulator::GetTableDefinitions_zcm_media_restriction());
		}

		static function GetTableDefinitions_zcm_media_restriction()
		{
			return array(
				"name" => "zcm_media_restriction",
				"columns" => array(
					DefineTableField("media_restriction_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("download_limit", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("day_limit", "INTEGER", "UNSIGNED NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "media_restriction_id",
				"engine" => "InnoDB"
			);
		}
	}

	/**
	 * Contains the information and validation routines for a
	 * MediaMember.
	 *
	 */
	class MediaMember
	{
		private $m_member_id;
		private $m_email;

		/**
		 * Get the member's ID, as stored in the zcm_member
		 * table of the database.
		 *
		 * @return int The member_id
		 */
		public function get_member_id()
		{
			return $this->m_member_id;
		}

		/**
		 * Set the member's ID, as stored in the zcm_member
		 * table of the database.
		 *
		 * @param int $newValue
		 */
		public function set_member_id($newValue)
		{
			$this->m_member_id = $newValue;
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
	}

	/**
	 * Contains a set of static methods used to populate either a single
	 * MediaMember object, or an array of MediaMember objects. Most of the
	 * methods populate the MediaMember object from the database, but the
	 * object may also be populated from a web form.
	 *
	 */
	class MediaMemberPopulator
	{
		/**
		 * Return all of the members in the database.
		 *
		 * @return MediaMember[]
		 */
		public static function PopulateAll()
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

			Zymurgy::$db->free_result($ri);

			return $members;
		}

		/**
		 * Return the member with the given ID in the database.
		 *
		 * @param int $member_id
		 * @return MediaMember
		 */
		public static function PopulateByID($member_id)
		{
			$sql = "SELECT `email` ".
				"FROM `zcm_member` WHERE `id` = '".
				Zymurgy::$db->escape_string($member_id)."'";

			$ri = Zymurgy::$db->query($sql) or die();

			$member = null;

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$member = new MediaMember();

				$member->set_member_id($member_id);
				$member->set_email($row["email"]);
			}

			Zymurgy::$db->free_result($ri);

			return $member;
		}
	}

	/**
	 * Contains the information and validation routines for a
	 * MediaRelation.
	 *
	 */
	class MediaRelation
	{
		private $m_media_relation_id;
		private $m_relation_type;
		private $m_relation_label;
		private $m_thumbnails;
		private $m_allowed_mimetypes = "";
		private $m_builtin;

		private $m_errors = array();

		/**
		 * Get the relation's ID, as stored in the database.
		 *
		 * @return int The media_relation_id
		 */
		public function get_media_relation_id()
		{
			return $this->m_media_relation_id;
		}

		/**
		 * Set the relation's ID, as stored in the database.
		 *
		 * @param int $newValue
		 */
		public function set_media_relation_id($newValue)
		{
			$this->m_media_relation_id = $newValue;
		}

		/**
		 * Get the one-word relation type code, used in the back-end
		 * code to identify relations without having to use the numeric
		 * ID automatically assigned by the database (which can change
		 * between deployments) or the user-friendly labels (which can
		 * change depending on the client's vocabulary).
		 *
		 * @return string
		 */
		public function get_relation_type()
		{
			return $this->m_relation_type;
		}

		/**
		 * Set the one-word relation type code, used in the back-end
		 * code to identify relations without having to use the numeric
		 * ID automatically assigned by the database (which can change
		 * between deployments) or the user-friendly labels (which can
		 * change depending on the client's vocabulary).
		 *
		 * @param string $newValue
		 */
		public function set_relation_type($newValue)
		{
			$this->m_relation_type = $newValue;
		}

		/**
		 * Get the user-friendly label for this relation.
		 *
		 * @return string
		 */
		public function get_relation_label()
		{
			return $this->m_relation_label;
		}

		/**
		 * Set the user-friendly label for this relation.
		 *
		 * @param string $newValue
		 */
		public function set_relation_label($newValue)
		{
			$this->m_relation_label = $newValue;
		}

		/**
		 * Get the list of thumbnails to automatically generate for
		 * media files assigned this relation, if the relation is
		 * meant to contain images.
		 *
		 * @return string Comma-seperated list of thumbnail sizes in
		 * WWxHH format.
		 */
		public function get_thumbnails()
		{
			return $this->m_thumbnails;
		}

		/**
		 * Set the list of thumbnails to automatically generate for
		 * media files assigned this relation, if the relation is
		 * meant to contain images.
		 *
		 * @param string $newValueComma-seperated list of thumbnail
		 * sizes in WWxHH format.
		 */
		public function set_thumbnails($newValue)
		{
			$this->m_thumbnails = $newValue;
		}

		/**
		 * Get the list of mime-types to allow for media files
		 * assigned this relation.
		 *
		 * @return Comma seperated list of mime-types.
		 */
		public function get_allowed_mimetypes()
		{
			return $this->m_allowed_mimetypes;
		}

		/**
		 * Set the list of mime-types to allow for media files
		 * assigned this relation.
		 *
		 * @param string $newValue Comma seperated list of mime-types
		 */
		public function set_allowed_mimetypes($newValue)
		{
			$this->m_allowed_mimetypes = $newValue;
		}

		/**
		 * Get the built-in flag for this relation. Built-in relations
		 * cannot be manipulated by the user through the GUI.
		 *
		 * @return unknown
		 */
		public function get_builtin()
		{
			return $this->m_builtin;
		}

		/**
		 * Set the built-in flag for this relation.
		 *
		 * @param unknown_type $newValue
		 */
		public function set_builtin($newValue)
		{
			$this->m_builtin = $newValue;
		}

		/**
		 * Get the list of errors in the media relation object. This is
		 * populated as part of the validate() method, and is typically
		 * called as part of the save process during data input.
		 *
		 * @return string[] The list of errors in the object.
		 */
		public function get_errors()
		{
			return $this->m_errors;
		}

		/**
		 * Determine if the given mime-type is in the list of mime-types
		 * allowed by this relation.
		 *
		 * @param string $mimetype
		 * @return boolean True if the mime-type is allowed. Otherwise false.
		 */
		public function isValidMimetype($mimetype)
		{
			if(strlen($this->m_allowed_mimetypes) <= 0) return true;

			$mimetypes = explode(",", $this->m_allowed_mimetypes);
			return in_array($mimetype, $mimetypes);
		}

		/**
		 * Validate the media relation object. This is typically called as
		 * part of the save process during data input. If there are
		 * errors, the errors array will be populated, and may be retrieved
		 * by calling get_errors().
		 *
		 * @param string $action The controller action. Used to enable add-only
		 * and edit-only specific validations. The supported actions are
		 * "act_add_media_relation" and "act_edit_media_relation".
		 * @return boolean True if the data is valid. Otherwise false.
		 */
		public function validate($action)
		{
			$isValid = true;

			if(strlen($this->m_relation_type) <= 0)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString("MediaRelation.Validate.TypeRequired");
				$isValid = false;
			}

			if(strlen($this->m_relation_label) <= 0)
			{
				$this->m_errors[] = Zymurgy::GetLocaleString("MediaRelation.Validate.LabelRequired");
				$isValid = false;
			}

			return $isValid;
		}
	}

	/**
	 * Contains a set of static methods used to populate either a single
	 * MediaMember object, or an array of MediaMember objects. Most of the
	 * methods populate the MediaMember object from the database, but the
	 * object may also be populated from a web form.
	 *
	 */
	class MediaRelationPopulator
	{
		/**
		 * Return all of the relations in the database.
		 *
		 * @return MediaRelation[]
		 */
		public static function PopulateAll()
		{
			$sql = "SELECT `media_relation_id`, `relation_type`, `relation_type_label`, ".
				"`thumbnails`, `builtin`, `allowed_mimetypes` ".
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
				$mediaRelation->set_thumbnails($row["thumbnails"]);
				$mediaRelation->set_allowed_mimetypes($row["allowed_mimetypes"]);
				$mediaRelation->set_builtin($row["builtin"]);

				$mediaRelations[] = $mediaRelation;
			}

			Zymurgy::$db->free_result($ri);

			return $mediaRelations;
		}

		/**
		 * Return the relation with the given ID in the database.
		 *
		 * @param int $media_relation_id
		 * @return MediaRelation
		 */
		public static function PopulateByID($media_relation_id)
		{
			$sql = "SELECT `relation_type`, `relation_type_label`, `thumbnails`, ".
				"`builtin`, `allowed_mimetypes` ".
				"FROM `zcm_media_relation` WHERE `media_relation_id` = '".
				Zymurgy::$db->escape_string($media_relation_id)."'";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve relation: ".mysql_error().", $sql");

			$mediaRelation = null;

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$row = Zymurgy::$db->fetch_array($ri);
				$mediaRelation = new MediaRelation();

				$mediaRelation->set_media_relation_id($media_relation_id);
				$mediaRelation->set_relation_type($row["relation_type"]);
				$mediaRelation->set_relation_label($row["relation_type_label"]);
				$mediaRelation->set_thumbnails($row["thumbnails"]);
				$mediaRelation->set_allowed_mimetypes($row["allowed_mimetypes"]);
				$mediaRelation->set_builtin($row["builtin"]);
			}

			Zymurgy::$db->free_result($ri);

			return $mediaRelation;
		}

		/**
		 * Return the relation with the given one-word type string.
		 *
		 * @param string $relation_type
		 * @return MediaRelation
		 */
		public static function PopulateByType($relation_type)
		{
			$sql = "SELECT `media_relation_id`,  `relation_type`, `relation_type_label`, ".
				"`thumbnails`, `allowed_mimetypes`, `builtin` FROM `zcm_media_relation` ".
				"WHERE `relation_type` = '".
				Zymurgy::$db->escape_string($relation_type)."'";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve relation: ".mysql_error().", $sql");

			$mediaRelation = null;

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
				$mediaRelation->set_thumbnails($row["thumbnails"]);
				$mediaRelation->set_allowed_mimetypes($row["allowed_mimetypes"]);
				$mediaRelation->set_builtit($row["builtin"]);
			}

			Zymurgy::$db->free_result($ri);

			return $mediaRelation;
		}

		/**
		 * Populate a MediaRelation object, given the input on a web form.
		 *
		 * @return MediaRelation
		 */
		public static function PopulateFromForm()
		{
			$mediaRelation = new MediaRelation();

			$mediaRelation->set_media_relation_id($_POST["media_relation_id"]);
			$mediaRelation->set_relation_type($_POST["relation_type"]);
			$mediaRelation->set_relation_label($_POST["relation_label"]);
			$mediaRelation->set_thumbnails($_POST["thumbnails"]);
			$mediaRelation->set_allowed_mimetypes($_POST["allowed_mimetypes"]);

			return $mediaRelation;
		}

		/**
		 * Save the media relation to the database. Note that the routine
		 * does not save the built-in flag.
		 *
		 * @param MediaRelation $mediaRelation
		 */
		public static function SaveRelation($mediaRelation)
		{
			$sql = "";

			if($mediaRelation->get_media_relation_id() <= 0)
			{
				$sql = "INSERT INTO `zcm_media_relation` ( `relation_type`, ".
					"`relation_type_label`, `thumbnails`, `allowed_mimetypes` ) VALUES ( '".
					Zymurgy::$db->escape_string($mediaRelation->get_relation_type()).
					"', '".
					Zymurgy::$db->escape_string($mediaRelation->get_relation_label()).
					"', '".
					Zymurgy::$db->escape_string($mediaRelation->get_thumbnails()).
					"', '".
					Zymurgy::$db->escape_string($mediaRelation->get_allowed_mimetypes()).
					"')";

				Zymurgy::$db->query($sql)
					or die("Could not insert relation record: ".mysql_error().", $sql");

				$mediaRelation->set_media_relation_id(
					Zymurgy::$db->insert_id());
			}
			else
			{
				$sql = "UPDATE `zcm_media_relation` SET `relation_type` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_relation_type()).
					"', `relation_type_label` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_relation_label()).
					"', `thumbnails` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_thumbnails()).
					"', `allowed_mimetypes` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_allowed_mimetypes()).
					"' WHERE `media_relation_id` = '".
					Zymurgy::$db->escape_string($mediaRelation->get_media_relation_id()).
					"'";

				Zymurgy::$db->query($sql)
					or die("Could not update relation record: ".mysql_error().", $sql");
			}
		}

		/**
		 * Remove the given media relation from the database.
		 *
		 * @param int $media_relation_id
		 */
		public static function DeleteRelation($media_relation_id)
		{
			$sql = "DELETE FROM `zcm_media_relation` WHERE `media_relation_id` = '".
				Zymurgy::$db->escape_string($media_relation_id)."'";

			Zymurgy::$db->query($sql)
				or die("Could not delete relation: ".mysql_error().", $sql");
		}

		static function GetTableDefinitions()
		{
			return array(
				MediaRelationPopulator::GetTableDefinitions_zcm_media_relation(),
				MediaRelationPopulator::GetTableDefinitions_zcm_media_file_relation());
		}

		static function GetTableDefinitions_zcm_media_relation()
		{
			return array(
				"name" => "zcm_media_relation",
				"columns" => array(
					DefineTableField("media_relation_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("relation_type", "VARCHAR(50)", "NOT NULL"),
					DefineTableField("relation_type_label", "VARCHAR(50)", "NOT NULL"),
					DefineTableField("allowed_mimetypes", "VARCHAR(200)", ""),
					DefineTableField("thumbnails", "VARCHAR(50)", ""),
					DefineTableField("builtin", "INTEGER", "NOT NULL DEFAULT '0'")
				),
				"indexes" => array(),
				"primarykey" => "media_relation_id",
				"engine" => "InnoDB"
			);
		}

		static function GetTableDefinitions_zcm_media_file_relation()
		{
			return array(
				"name" => "zcm_media_file_relation",
				"columns" => array(
					DefineTableField("media_file_relation_id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("media_file_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("related_media_file_id", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("media_relation_id", "INTEGER", "UNSIGNED NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "media_file_relation_id",
				"engine" => "InnoDB"
			);
		}
	}
?>