<?php
	require_once("Form.php");

	class PaymentForm extends Form
	{
		function GetTitle()
		{
			return 'Payment Form Plugin';
		}
	
		function GetDefaultConfig()
		{
			$r = parent::GetDefaultConfig();
			
			$this->BuildConfig(
				$r, 
				"Payment Gateway",
				"Paypal IPN",
				"drop.Paypal IPN, Moneris, Authorize.NET");
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
				
			$sql = "SELECT `header` FROM `zcm_form_input` WHERE `instance` = '".
				mysql_escape_string($_GET["instance"]).
				"' ORDER BY `disporder`";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve field list: ".mysql_error().", $sql");
				
			$fieldList = array();
			$fieldList[] = "(none)";
				
			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$fieldList[] = $row["header"];
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
	
		function RenderThanks()
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
				
			$invoicePrefix = $this->GetConfigValue("Invoice Prefix");
			$paymentProcessor->SetInvoiceID(
				$invoicePrefix . date("YmdHis"));
				
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
			
			// echo "<pre>";
			// print_r($paymentProcessor->GetBillingInformation());
			// echo "</pre>";
			
			$paymentProcessor->Process();
		}
		
		private function GetPaymentProcessor()
		{
			switch($this->GetConfigValue("Payment Gateway"))
			{
				case "Paypal IPN":
					return new PaypalIPNProcessor();
					
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
	}
		
	function PaymentFormFactory()
	{
		return new PaymentForm();
	}
?>