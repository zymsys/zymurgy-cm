<?php
/**
 * 
 * @package Zymurgy
 * @subpackage auth
 */
	class oscommerceMember extends ZymurgyMember
	{
		/**
		 * Try to relate an existing Z:CM member to session data from osCommerce.  If a link is found fill $member and create an auth key.
		 * Returns true if the link could be made.
		 *
		 * @return boolean
		 */
		private function findmemberfromsession()
		{
			/* $sid = session_id();
			if (empty($sid))
			{
				session_start();
			} */

			echo("Customer ID Exists: ".(array_key_exists('customer_id',$_SESSION) ? "YES" : "NO")."<br>");

			if (array_key_exists('customer_id',$_SESSION))
			{
				$member = Zymurgy::$db->get("select * from zcm_member where mpkey='".
					Zymurgy::$db->escape_string($_SESSION['customer_id'])."'");
				if ($member === false)
				{
					$member = Zymurgy::$db->get("select * from zcm_member where email='".
						Zymurgy::$db->escape_string($_SESSION['customer_name'])."'");

					if (is_array($member))
					{
						//Found by email; update their mpkey
						Zymurgy::$db->run("update zcm_member set mpkey='".
							Zymurgy::$db->escape_string($_SESSION['customer_id'])."' where id={$member['id']}");
					}
				}

				if (is_array($member))
				{
					$this->populatememberfromrow($member);
					$this->createauthkey($member['id']);

					return true;
				}
			}

			return false;
		}

		private function findmemberfromid($id, $email)
		{
			$member = Zymurgy::$db->get("select * from zcm_member where mpkey='".
				Zymurgy::$db->escape_string($id)."'");
			if ($member === false)
			{
				$member = Zymurgy::$db->get("select * from zcm_member where email='".
					Zymurgy::$db->escape_string($email)."'");

				if (is_array($member))
				{
					//Found by email; update their mpkey
					Zymurgy::$db->run("update zcm_member set mpkey='".
						Zymurgy::$db->escape_string($id)."' where id={$member['id']}");
				}
			}

			if (is_array($member))
			{
				$this->populatememberfromrow($member);
				$this->createauthkey($member['id']);

				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Authenticate that the user is logged in.
		 *
		 * @return boolean
		 */
		public function memberauthenticate()
		{
			// ZK: Disable for now
			return false;

			$sname = session_name('osCsid');

   			$sid = session_id("hd4sjsek8k5hfrn7eel2mr0c17"); // session_id('osCsid');

   			die($sid.": ".$sname);

			/* $sid = session_id();
			if (empty($sid))
			{
				session_start();
			} */

			if (!parent::memberauthenticate())
			{
				echo("Member not authenticated by parent.<br>");
			}
			else
			{
				//Parent think's we're logged in, but are we still logged into the MP?
				echo("Customer Name Exists: ".array_key_exists('customer_name',$_SESSION));

				if (array_key_exists('customer_name',$_SESSION))
				{
					if ($_SESSION['customer_name'] == Zymurgy::$member['email'])
					{
						return true;
					}
				}
			}

			return $this->findmemberfromsession();
		}


		/**
		 * Try to log in with the provided user ID and password using the osCommerce database.
		 *
		 * @param string $userId
		 * @param string $password
		 * @return boolean
		 */
		public function memberdologin($userId, $password)
		{
			// die(var_dump(debug_backtrace()));

			require_once(Zymurgy::$root."/zymurgy/include/nusoap.php");

			$client = new soapclient2(Zymurgy::$config['oscommerce Server Path']."/soap.php");
			$r = $client->call(
				'authenticate_user',
				array('user_name' => "$userId",'user_password'=>"$password"),
				Zymurgy::$config['oscommerce Server Path'],
				Zymurgy::$config['oscommerce Server Path']);

			$member = $r[0];
			if (is_array($member))
			{
				$sid = session_id();
				if (empty($sid))
				{
					session_start();
				}

				$_SESSION['customer_id'] = $member['id'];
				$_SESSION['customer_name'] = $member['user_name'];

				if (!$this->findmemberfromsession())
				{
					//Member isn't yet known to Z:CM, add it.
					Zymurgy::$db->run("insert into zcm_member (email,password,regtime,lastauth,mpkey) values ('".
						Zymurgy::$db->escape_string($member['user_name'])."','".
						Zymurgy::$db->escape_string($member['user_password'])."',now(),now(),'".
						Zymurgy::$db->escape_string($member['id'])."')");

					$this->findmemberfromid(
						$member['id'],
						$member['user_name']);
				}
				return true;
			}
			else
			{
				$this->memberaudit("Failed login attempt for [$userId]: $member");
				return false;
			}
		}

		public function membersignup(
			$formname,
			$useridfield,
			$passwordfield,
			$confirmfield,
			$redirect)
		{
			// die("Child membersignup called");

			$pi = Zymurgy::mkplugin('Form',$formname);
			$pi->LoadInputData();
			$userid = $password = $confirm = '';
			$firstname = $lastname = $birthdate = $email = $street = $postalcode = $city =
				$province = $country = $phone = '';
			$authed = $this->memberauthenticate();

			if ($_SERVER['REQUEST_METHOD']=='POST')
			{
				if ($_POST['formname']!=$pi->InstanceName)
				{
					//Another form is posting, just render the form as usual.
					$pi->RenderForm();
					return ;
				}
				//Look for user id, password and password confirmation fields
				$values = array(); //Build a new array of inputs except for password.

				$this->membersignup_GetValuesFromOSCommerceForm(
					$pi,
					$values,
					$userid,
					$password,
					$confirm,
					$firstname,
					$lastname,
					$birthdate,
					$street,
					$postalcode,
					$city,
					$province,
					$country,
					$phone,
					$useridfield,
					$passwordfield,
					$confirmfield,
					'firstname',
					'lastname',
					'birthdate',
					'street',
					'postalcode',
					'city',
					'province',
					'country',
					'phone');
				$this->membersignup_ValidateOSCommerceForm(
					$userid,
					$password,
					$confirm,
					$firstname,
					$lastname,
					$birthdate,
					$street,
					$postalcode,
					$city,
					$province,
					$country,
					$phone,
					$authed,
					$pi);

				if (!$pi->IsValid())
				{
					$pi->RenderForm();
					return;
				}

				if (array_key_exists('rurl',$_GET))
					$rurl = $_GET['rurl'];
				else
					$rurl = $redirect;

				if (strpos($rurl,'?')===false)
					$joinchar = '?';
				else
					$joinchar = '&';

				if (!$authed)
				{
					// die("Creating member");

					//New registration
					$ri = $this->membersignup_CreateOSCommerceMember(
						$userid,
						$password,
						$firstname,
						$lastname,
						$birthdate,
						$street,
						$postalcode,
						$city,
						$province,
						$country,
						$phone);

					// die(print_r($ri));

					if($ri)
					{
						$this->membersignup_AuthenticateNewMember($userid, $password);
					}
				}
				else
				{ //Update existing registration
					//Has email changed?
					if (Zymurgy::$member['email']!==$userid)
					{
						$this->membersignup_UpdateUserID($userid);
					}
					//Has password changed?
					if (!empty($password))
					{
						$this->membersignup_UpdatePassword($password);
					}
					//Update other user info (XML)
					$sql = "update zcm_form_capture set formvalues='".Zymurgy::$db->escape_string($pi->MakeXML($values))."' where id=".Zymurgy::$member['formdata'];
					Zymurgy::$db->query($sql) or die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
					Zymurgy::JSRedirect($rurl.$joinchar.'memberaction=update');
				}
			}
			else
			{
				if ($authed)
				{
					//We're logged in so update existing info.
					$sql = "select formvalues from zcm_form_capture where id=".Zymurgy::$member['formdata'];
					$ri = Zymurgy::$db->query($sql) or die("Can't get form data ($sql): ".Zymurgy::$db->error());
					$xml = Zymurgy::$db->result($ri,0,0);
					$pi->XmlValues = $xml;
					return $pi->Render();
				}
				else
					return $pi->Render();
			}
			return '';
		}

		function membersignup_GetValuesFromOSCommerceForm(
			&$pi,
			&$values,
			&$userid,
			&$password,
			&$confirm,
			&$firstname,
			&$lastname,
			&$birthdate,
			&$street,
			&$postalcode,
			&$city,
			&$province,
			&$country,
			&$phone,
			$useridfield,
			$passwordfield,
			$confirmfield,
			$firstnamefield,
			$lastnamefield,
			$birthdatefield,
			$streetfield,
			$postalcodefield,
			$cityfield,
			$provincefield,
			$countryfield,
			$phonefield)
		{
			foreach($pi->InputRows as $row)
			{
				$fldname = 'Field'.$row['fid'];

				if (array_key_exists($fldname,$_POST))
					$row['value'] = $_POST[$fldname];
				else
					$row['value'] = '';

				$values[$row['header']] = $row['value'];

				$userid = $this->membersignup_GetValuesFromForm($useridfield, $userid, $row);
				$password = $this->membersignup_GetValuesFromForm($passwordfield, $password, $row);
				$confirm = $this->membersignup_GetValuesFromForm($confirmfield, $confirm, $row);
				$firstname = $this->membersignup_GetValuesFromForm($firstnamefield, $firstname, $row);
				$lastname = $this->membersignup_GetValuesFromForm($lastnamefield, $lastname, $row);
				$birthdate = $this->membersignup_GetValuesFromForm($birthdatefield, $birthdate, $row);
				$street = $this->membersignup_GetValuesFromForm($streetfield, $street, $row);
				$postalcode = $this->membersignup_GetValuesFromForm($postalcodefield, $postalcode, $row);
				$city = $this->membersignup_GetValuesFromForm($cityfield, $city, $row);
				$province = $this->membersignup_GetValuesFromForm($provincefield, $province, $row);
				$country = $this->membersignup_GetValuesFromForm($countryfield, $country, $row);
				$phone = $this->membersignup_GetValuesFromForm($phonefield, $phone, $row);
			}
		}

		private function membersignup_GetValuesFromForm(
			$field,
			$originalValue,
			$row)
		{
			if($row['header'] == $field)
			{
				return $row['value'];
			}
			else
			{
				return $originalValue;
			}
		}

		private function membersignup_ValidateOSCommerceForm(
			$userid,
			$password,
			$confirm,
			$firstname,
			$lastname,
			$birthdate,
			$street,
			$postalcode,
			$city,
			$province,
			$country,
			$phone,
			$authed,
			&$pi)
		{
			parent::ValidateForm(
				$userid,
				$password,
				$confirm,
				$authed);

			if ($firstname == '')
				$pi->ValidationErrors[] = 'First name is a required field.';

			if ($lastname == '')
				$pi->ValidationErrors[] = 'Last name is a required field.';

			if ($birthdate == '')
				$pi->ValidationErrors[] = 'Birthdate is a required field.';

			if ($street == '')
				$pi->ValidationErrors[] = 'Street Address is a required field.';

			if ($postalcode == '')
				$pi->ValidationErrors[] = 'Postal code is a required field.';

			if ($city == '')
				$pi->ValidationErrors[] = 'City is a required field.';

			if ($province == '')
				$pi->ValidationErrors[] = 'Province is a required field.';

			if ($country == '')
				$pi->ValidationErrors[] = 'Country is a required field.';

			if ($phone == '')
				$pi->ValidationErrors[] = 'Phone number is a required field.';
		}

		private function membersignup_CreateOSCommerceMember(
			$userid,
			$password,
			$firstname,
			$lastname,
			$birthdate,
			$street,
			$postalcode,
			$city,
			$province,
			$country,
			$phone)
		{
			// die("membersignup_CreateOSCommerceMember() Start");

			parent::membersignup_CreateMember($userid, $password);

			if($ri)
			{
				require_once(Zymurgy::$root."/zymurgy/include/nusoap.php");
				$client = new soapclient2(
					Zymurgy::$config['oscommerce Server Path']."/soap.php");

				$input_array = array(
		    		'firstname' => $firstname,
		    		'lastname' => $lastname,
		    		'email' => $userid,
		    		'password' => $password,
		    		'birthdate' => $birthdate,
		    		'street' => $street,
		    		'postalcode' => $postalcode,
		    		'city' => $city,
		    		'province' => $province,
		    		'country' => $country,
		    		'phone' => $phone,
		    		'fromemailname' => Zymurgy::$config['oscommerce E-mail Name'],
		    		'fromemailaddress' => Zymurgy::$config['oscommerce E-mail Address']);

			    $result = $client->call(
			    	'create_member',
			    	$input_array);

			    // die(print_r($result));
			}
		}
	}
?>