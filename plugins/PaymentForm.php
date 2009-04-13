<?php
	require_once("Form.php");

	class PaymentForm extends Form
	{
		private $m_invoiceID;

		function GetTitle()
		{
			return 'Payment Form Plugin';
		}

		function Initialize()
		{
			parent::Initialize();

			$sql = "CREATE TABLE `zcm_form_paymentresponse` (".
				"`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,".
				"`instance` INTEGER UNSIGNED NOT NULL,".
				"`invoice_id` VARCHAR(50) NOT NULL,".
				"`response_date` DATETIME NOT NULL,".
				"`status_code` VARCHAR(50) NOT NULL,".
				"`post_vars` TEXT NOT NULL,".
				"PRIMARY KEY (`id`)".
				") ENGINE = InnoDB;";
			Zymurgy::$db->query($sql)
				or die("Could not create zcm_form_paymentresponse table: ".mysql_error());
		}

		function GetDefaultConfig()
		{
			$r = parent::GetDefaultConfig();

			$this->BuildConfig(
				$r,
				"Payment Gateway",
				"Paypal IPN",
				"drop.Paypal IPN, Moneris eSELECT Plus, Authorize.NET");
			$this->BuildConfig(
				$r,
				"Item Amount",
				"",
				"input.10.9");
			$this->BuildConfig(
				$r,
				"Invoice Prefix",
				"Invoice",
				"input.30.100");
			$this->BuildConfig(
				$r,
				"Cancellation URL",
				"/index.php",
				"input.50.200");

			$fieldList = array();
			$fieldList[] = "(none)";

			if(isset($_GET["instance"]))
			{
				$sql = "SELECT `header` FROM `zcm_form_input` WHERE `instance` = '".
					mysql_escape_string($_GET["instance"]).
					"' ORDER BY `disporder`";
				$ri = Zymurgy::$db->query($sql)
					or die("Could not retrieve field list: ".mysql_error().", $sql");

				while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
				{
					$fieldList[] = $row["header"];
				}
			}

			$this->AddBillingInformationLink($r, $fieldList, "First Name");
			$this->AddBillingInformationLink($r, $fieldList, "Last Name");
			$this->AddBillingInformationLink($r, $fieldList, "Company Name");
			$this->AddBillingInformationLink($r, $fieldList, "Address 1");
			$this->AddBillingInformationLink($r, $fieldList, "Address 2");
			$this->AddBillingInformationLink($r, $fieldList, "City");
			$this->AddBillingInformationLink($r, $fieldList, "Province/State");
			$this->AddBillingInformationLink($r, $fieldList, "Country");
			$this->AddBillingInformationLink($r, $fieldList, "Postal Code");
			$this->AddBillingInformationLink($r, $fieldList, "Phone");
			$this->AddBillingInformationLink($r, $fieldList, "Fax");
			$this->AddBillingInformationLink($r, $fieldList, "E-mail Address");

			return $r;
		}

		private function AddBillingInformationLink(&$r, $fieldList, $caption)
		{
			$this->BuildConfig(
				$r,
				$caption,
				"",
				"drop.".implode(", ", $fieldList));
		}

		function GetValues()
		{
			//Build substitution array out of supplied headers/values
			$values = array();
			foreach($this->InputRows as $row)
			{
				$fieldname = "Field".$row['fid'];
				if (!array_key_exists($fieldname,$_POST))
					continue; //Don't report missing fields...  Think checkboxes.
	                        if (empty($row['header']))
	                                $key = $fieldname;
	                        else
	                                $key = $row['header'];
	                        $values[$key] = $_POST['Field'.$row['fid']];
			}
			if (is_array($this->extra))
			{
				$values = array_merge($this->extra,$values);
			}

			// Add the invoice ID
			if(!isset($this->m_invoiceID)) $this->SetInvoiceID();
			$values["Invoice ID"] = $this->m_invoiceID;

			return $values;
		}

		function SetInvoiceID()
		{
			$invoicePrefix = $this->GetConfigValue("Invoice Prefix");
			$this->m_invoiceID = $invoicePrefix . date("YmdHis");
		}

		function RenderPaymentForm()
		{
			if ($this->GetConfigValue('Email Form Results To Address') != '')
				$this->SendEmail();

			if ($this->GetConfigValue('Capture to Database') == 1)
				$this->StoreCapture();

			$paymentProcessor = $this->GetPaymentProcessor();
			$values = $this->GetValues();

			// echo "<pre>";
			// print_r($values);
			// echo "</pre>";

			$paymentProcessor->SetAmount(
				$this->GetConfigValue("Item Amount"));

			if(!isset($this->m_invoiceID)) $this->SetInvoiceID();
			$paymentProcessor->SetInvoiceID(
				$this->m_invoiceID);

			$billing = $paymentProcessor->GetBillingInformation();

			if($this->GetConfigValue("First Name") !== "" && $this->GetConfigValue("First Name") !== "(none)")
			{
				// echo($values["first_name"]."<br>");
				// echo "'".$this->GetConfigValue("First Name")."': ".$values[trim($this->GetConfigValue("First Name"))];
				// die();

				$billing->first_name =
					$values[trim($this->GetConfigValue("First Name"))];
			}

			$this->SetBillingInformationItem($billing, $values, "First Name", "first_name");
			$this->SetBillingInformationItem($billing, $values, "Last Name", "last_name");
			$this->SetBillingInformationItem($billing, $values, "Company Name", "company_name");
			$this->SetBillingInformationItem($billing, $values, "Address 1", "address1");
			$this->SetBillingInformationItem($billing, $values, "Address 2", "address2");
			$this->SetBillingInformationItem($billing, $values, "City", "city");
			$this->SetBillingInformationItem($billing, $values, "Province/State", "province");
			$this->SetBillingInformationItem($billing, $values, "Country", "country");
			$this->SetBillingInformationItem($billing, $values, "Postal Code", "postal_code");
			$this->SetBillingInformationItem($billing, $values, "Phone", "phone");
			$this->SetBillingInformationItem($billing, $values, "Fax", "fax");
			$this->SetBillingInformationItem($billing, $values, "E-mail Address", "email");

			// echo "<pre>";
			// print_r($billing);
			// echo "</pre>";

			$paymentProcessor->SetBillingInformation($billing);

			$paymentProcessor->SetReturnURL(
				"http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
			$paymentProcessor->SetNotifyURL(
				"http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
			if(trim($this->GetConfigValue("Cancellation URL")) !== "")
			{
				$paymentProcessor->SetCancelURL(
					"http://".$_SERVER['HTTP_HOST'].$this->GetConfigValue("Cancellation URL"));
			}

			// echo "<pre>";
			// print_r($paymentProcessor->GetBillingInformation());
			// echo "</pre>";

			$paymentProcessor->Process();
		}

		private function GetPaymentProcessor()
		{
			switch(trim($this->GetConfigValue("Payment Gateway")))
			{
				case "Paypal IPN":
					return new PaypalIPNProcessor();

				case "Moneris eSELECT Plus":
					return new MonerisEselectProcessor();

				case "Authorize.NET":
					return new AuthorizeNetProcessor();

				default:
					die("The ".$this->GetConfigValue("Payment Gateway")." payment gateway is not supported at this time.");
			}
		}

		private function SetBillingInformationItem(&$billing, $values, $configValue, $propertyName)
		{
			if($this->GetConfigValue($configValue) !== "" && $this->GetConfigValue($configValue) !== "(none)")
			{
				// echo($values["first_name"]."<br>");
				// echo "'".$this->GetConfigValue("First Name")."': ".$values[trim($this->GetConfigValue("First Name"))];
				// die();

				$billing->$propertyName =
					$values[trim($this->GetConfigValue($configValue))];
			}
		}

		function RenderThanks()
		{
			echo $this->GetConfigValue('Thanks');
		}

		function Render()
		{
			if (!$this->InputDataLoaded)
				$this->LoadInputData();

			$paymentProcessor = $this->GetPaymentProcessor();

			if(!($paymentProcessor->GetCallbackQueryParameter() == "nocallback") && (
				isset($_GET[$paymentProcessor->GetCallbackQueryParameter()])
				|| isset($_POST[$paymentProcessor->GetCallbackQueryParameter()])))
			{
				$paymentProcessor->Callback();
				$this->SavePostbackData($paymentProcessor->GetPaymentTransaction());

				// Paypal IPN does not require any output, but
				// Moneris eSELECT does
				$this->RenderThanks($paymentProcessor);
			}
			else
			{
				if ($_SERVER['REQUEST_METHOD']=='POST')
				{
					if ($_POST['formname']!=$this->InstanceName)
					{
						//Another form is posting, just render the form as usual.
						$this->RenderForm();
					}
					else
					{
						if ($this->IsValid())
						{
							//This does the send/store and the thanks.
							$this->RenderPaymentForm();
						}
						else
						{
							$this->RenderForm();
						}
					}
				}
				else
				{
					if(isset($_GET[$paymentProcessor->GetReturnQueryParameter()]))
					{
						$this->RenderThanks($paymentProcessor);
					}
					else
					{
						$this->RenderForm();
					}
				}
			}
		}

		function SavePostbackData($transaction)
		{
			$postVarString = "";

			foreach($transaction->postback_variables as $key=>$value)
			{
				$postVarString .= "$key: $value\n";
			}

			$sql = "INSERT INTO `zcm_form_paymentresponse` ( `instance`, `invoice_id`, ".
				"`response_date`, `status_code`, `post_vars` ) VALUES ( '".
				mysql_escape_string($this->iid).
				"', '".
				mysql_escape_string($transaction->invoice_id).
				"', '".
				mysql_escape_string(date("Y/m/d H:i:s")).
				"', '".
				mysql_escape_string($transaction->status_code).
				"', '".
				mysql_escape_string($postVarString).
				"' )";
			//throw(new Exception($sql));
			Zymurgy::$db->query($sql)
				or die("Could not insert payment response: ".mysql_error());
		}
	}

	function PaymentFormFactory()
	{
		return new PaymentForm();
	}
?>