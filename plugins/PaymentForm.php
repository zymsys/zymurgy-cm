<?php
/**
 * 
 * @package Zymurgy_Plugins
 */
	require_once("Form.php");

	class PaymentForm extends Form
	{
		private $m_invoiceID;
		private $m_invoiceNumber;

		function GetTitle()
		{
			return 'Payment Form Plugin';
		}

		function Initialize()
		{
			parent::Initialize();
			$this->VerifyTableDefinitions();
		}

		function Upgrade()
		{
			$this->VerifyTableDefinitions();

			parent::Upgrade();
		}

		private function CreatePaymentResponseTable()
		{
			require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');

			$tableDefinitions = array(
				array(
					"name" => "zcm_form_paymentresponse",
					"columns" => array(
						DefineTableField("id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
						DefineTableField("instance", "INTEGER", "UNSIGNED NOT NULL"),
						DefineTableField("invoice_id", "VARCHAR(50)", "NOT NULL"),
						DefineTableField("capture_id", "INTEGER", "UNSIGNED NOT NULL"),
						DefineTableField("responsedate", "DATETIME", "NOT NULL"),
						DefineTableField("processor", "VARCHAR(50)", "NOT NULL"),
						DefineTableField("status_code", "VARCHAR(50)", "NOT NULL"),
						DefineTableField("post_vars", "TEXT", "NOT NULL")
					),
					"indexes" => array(),
					"primarykey" => "id",
					"engine" => "InnoDB"
				)
			);

			ProcessTableDefinitions($tableDefinitions);
		}

		function GetConfigItems()
		{
			$configItems = parent::GetConfigItems();

			$configItems["Payment Gateway"] = array(
				"name" => "Payment Gateway",
				"default" => "Paypal IPN",
				"inputspec" => "drop.Paypal IPN,Moneris eSELECT Plus,Authorize.NET,Google Checkout",
				"authlevel" => 0);
			$configItems["Item Amount"] = array(
				"name" => "Item Amount",
				"default" => "",
				"inputspec" => "input.10.9",
				"authlevel" => 0);
			$configItems["Amount Lookup Field"] = array(
				"name" => "Amount Lookup Field",
				"default" => "",
				"inputspec" => "input.20.50",
				"authlevel" => 0);
			$configItems["Amount Lookup Column"] = array(
				"name" => "Amount Lookup Column",
				"default" => "",
				"inputspec" => "input.20.50",
				"authlevel" => 0);
			$configItems["Invoice Prefix"] = array(
				"name" => "Invoice Prefix",
				"default" => "Invoice",
				"inputspec" => "input.30.100",
				"authlevel" => 0);
			$configItems["Cancellation URL"] = array(
				"name" => "Cancellation URL",
				"default" => "/index.php",
				"inputspec" => "input.50.200",
				"authlevel" => 0);

			return $configItems;
		}

		function GetDefaultConfig()
		{
			$r = parent::GetDefaultConfig();

			$this->BuildConfig(
				$r,
				"Payment Gateway",
				"Paypal IPN",
				"drop.Paypal IPN,Moneris eSELECT Plus,Authorize.NET,Google Checkout");
			$this->BuildConfig(
				$r,
				"Item Amount",
				"",
				"input.10.9");
			$this->BuildConfig(
				$r,
				"Amount Lookup Field",
				"",
				"input.20.50");
			$this->BuildConfig(
				$r,
				"Amount Lookup Column",
				"",
				"input.20.50");
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
			// ZK: Removing - Invoice ID now based on capture record's ID
			// if(!isset($this->m_invoiceID)) $this->SetInvoiceID();
			// $values["Invoice ID"] = $this->m_invoiceID;

			return $values;
		}

		function SetInvoiceID()
		{
			$invoicePrefix = $this->GetConfigValue("Invoice Prefix");
			$this->m_invoiceID = $invoicePrefix . date("YmdHis");
		}

		function RenderSubmitButton()
		{
			if($this->GetConfigValue("Payment Gateway") == "Google Checkout")
			{
				$paymentProcessor = $this->GetPaymentProcessor();
				return $paymentProcessor->RenderSubmitButton("", true);
			}
			else
			{
				return parent::RenderSubmitButton();
			}
		}
		
		function getAmount()
		{
			$amt = $this->GetConfigValue("Item Amount");
			$ilf = $this->GetConfigValue("Amount Lookup Field");
			$ilc = $this->GetConfigValue("Amount Lookup Column");
			if (!empty($ilf) && !empty($ilc))
			{
				$ir = $this->InputRows[$ilf];
				$is = $ir['specifier'];
				$sp = explode('.',$is);
				if ($sp[0] == 'lookup')
				{
					$table = $sp[1];
					$idcol = $sp[2];
					$id = $_POST["Field{$ir['fid']}"];
					$sql = "SELECT `$ilc` FROM `$table` WHERE ID='".
						Zymurgy::$db->escape_string($id)."'";
					$amt = Zymurgy::$db->get($sql);
				}
			}
			return $amt;
		}

		function RenderPaymentForm()
		{
			$id = 0;

			if ($this->GetConfigValue('Email Form Results To Address') != '')
				$this->CallExtensionMethod("SendEmail");

			// ZK: PaymentForm always captures to the database
			// TODO: Remove the option from the config
			//if ($this->GetConfigValue('Capture to Database') == 1)
			$id = $this->CallExtensionMethod("CaptureFormData");

			$paymentProcessor = $this->GetPaymentProcessor();
			$values = $this->GetValues();

			// echo "<pre>";
			// print_r($values);
			// echo "</pre>";

			$paymentProcessor->SetAmount($this->getAmount());

			// ZK: Removing - Invoice ID now based on capture record's ID
			// if(!isset($this->m_invoiceID)) $this->SetInvoiceID();
			$this->m_invoiceID = $this->GetConfigValue("Invoice Prefix") . $id;
			$this->m_invoiceNumber = $id;

			$paymentProcessor->SetInvoiceID($this->m_invoiceID);
			$paymentProcessor->SetInvoiceNumber($this->m_invoiceNumber);

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

		private function GetPaymentProcessor(
			$processorName = '')
		{
			if($processorName == '')
			{
				$processorName = $this->GetConfigValue("Payment Gateway");
				if (array_key_exists("Payment Gateway",Zymurgy::$config) && ($processorName == ''))
				{
					$processorName = Zymurgy::$config["Payment Gateway"];
				}
			}

			switch(trim($processorName))
			{
				case "Paypal IPN":
					return new PaypalIPNProcessor();

				case "Moneris eSELECT Plus":
					return new MonerisEselectProcessor();

				case "Authorize.NET":
					return new AuthorizeNetProcessor();

				case "Google Checkout":
					return new GoogleCheckoutProcessor();

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

			$invoiceID = $transaction->invoice_id;

			Zymurgy::DbgLog($transaction,$postVarString,$invoiceID);
			if(strpos($invoiceID, $this->GetConfigValue("Invoice Prefix")) === FALSE)
			{
				$sql = "SELECT `invoice_id` FROM `zcm_form_paymentresponse` WHERE `post_vars` LIKE ".
					"'%<google-order-number>".
					Zymurgy::$db->escape_string($invoiceID).
					"</google-order-number>%' AND NOT `invoice_id` = ''";
				$ri = Zymurgy::$db->query($sql)
					or die("Could not retrieve Invoice based on Google Order Number: ".Zymurgy::$db->error().", $sql");

				if(Zymurgy::$db->num_rows($ri) > 0)
				{
					$row = Zymurgy::$db->fetch_array($ri);

					$transaction->invoice_id = $row["invoice_id"];
				}

				Zymurgy::$db->free_result($ri);
			}

			$sql = "INSERT INTO `zcm_form_paymentresponse` ( `instance`, `invoice_id`, ".
				"`capture_id`, `responsedate`, `processor`, `status_code`, `post_vars` ) VALUES ( '".
				mysql_escape_string($this->iid).
				"', '".
				mysql_escape_string($transaction->invoice_id).
				"', ".
				mysql_escape_string(str_replace(
					$this->GetConfigValue("Invoice Prefix"),
					"",
					$transaction->invoice_id)).
				", '".
				mysql_escape_string(date("Y/m/d H:i:s")).
				"', '".
				mysql_escape_string($transaction->processor).
				"', '".
				mysql_escape_string($transaction->status_code).
				"', '".
				mysql_escape_string(print_r($transaction->postback_variables,true)).
				"' )";
			//throw(new Exception($sql));
			Zymurgy::DbgLog($sql);
			Zymurgy::$db->query($sql)
				or die("Could not insert payment response: ".mysql_error().", $sql");
		}

		function RenderAdminDataManagementDownload()
		{
			// die();

			$exported = array();
			$headers = array();
			$rows = array();

			$from = "";
			$to = "";

			$this->RenderAdminDoDownload_GetCaptureArrays(
				$this->RenderAdminDataManagementDownload_Query($from, $to),
				$exported,
				$headers,
				$rows);
			$this->RenderAdminDoDownload_PaymentResponses(
				$exported,
				$headers,
				$rows);
			$this->RenderAdminDoDownload_OutputExcel(
				$headers,
				$rows,
				$this->InstanceName."-$from-$to");
		}

		function RenderAdminDoDownload($expid)
		{
			// die("Using PaymentForm RenderAdminDoDownload");

			$exported = array();
			$headers = array();
			$rows = array();

			$this->RenderAdminDoDownload_GetCaptureArrays(
				$this->RenderAdminDoDownload_Query($expid),
				$exported,
				$headers,
				$rows);
			$this->RenderAdminDoDownload_PaymentResponses(
				$exported,
				$headers,
				$rows);
			$this->RenderAdminDoDownload_OutputExcel(
				$headers,
				$rows,
				"formrecords".$expid);
		}

		function RenderAdminDoDownload_PaymentResponses(
			&$exported,
			&$headers,
			&$rows)
		{
			$sql = "SELECT `invoice_id`, `capture_id`, `processor`, `status_code`, ".
				"`responsedate`, `post_vars` FROM `zcm_form_paymentresponse` ".
				"INNER JOIN `zcm_form_capture` ON `zcm_form_capture`.`id` = `zcm_form_paymentresponse`.`capture_id` ".
				"WHERE `zcm_form_capture`.`instance` = ".
				mysql_escape_string($this->iid);

			// die($sql);

			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
			{
				if (Zymurgy::$db->errno() == 1146)
				{
					$this->CreatePaymentResponseTable();
					$ri = Zymurgy::$db->query($sql);
				}
				if (!$ri)
				{
					die("Could not retrieve payment responses: ".mysql_error().", $sql");
				}
			}

			if(Zymurgy::$db->num_rows($ri) > 0)
			{
				$headers[] = "invoice_id";
				$headers[] = "payment_processor";
				$headers[] = "status_code";
				$headers[] = "responsedate";

				while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
				{
					if(($exportedIndex = array_search($row["capture_id"], $exported)) !== FALSE)
					{
						$baseRow = $rows[$exportedIndex];

						$baseRow["invoice_id"] = $row["invoice_id"];
						$baseRow["payment_processor"] = $row["processor"];
						$baseRow["status_code"] = $row["status_code"];
						$baseRow["responsedate"] = $row["responsedate"];

						$paymentProcessor = $this->GetPaymentProcessor($row["processor"]);

						if($paymentProcessor instanceof GoogleCheckoutProcessor)
						{
							// Google Checkout returns XML instead of a simple POST
							// dump. Since the Excel XML document cannot display this
							// raw XML, suppress the output.
						}
						else
						{
							$responseFields = explode("\n", $row["post_vars"]);

							foreach($responseFields as $responseField)
							{
								$keyValue = explode(":", $responseField);

								if(trim($keyValue[0]) !== "" && $paymentProcessor->IsReportedPostVar($keyValue[0]))
								{
									if(!in_array($keyValue[0], $headers))
									{
										$sql = "insert into zcm_form_header (instance,header) values (".
											"{$this->iid},'".Zymurgy::$db->escape_string($keyValue[0])."')";
										Zymurgy::$db->query($sql)
											or die ("Unable to create new header [{$keyValue[0]}] ($sql): ".Zymurgy::$db->error());
										$headers[] = $keyValue[0];
									}

									$baseRow[$keyValue[0]] = htmlspecialchars($keyValue[1]);
								}
							}
						}


						$rows[$exportedIndex] = $baseRow;
					}
				}
			}

			// echo("<pre>");
			// print_r($rows);
			// print_r($headers);
			// echo("<pre>");

			// die();
		}
	}

	function PaymentFormFactory()
	{
		return new PaymentForm();
	}
?>
