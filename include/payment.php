<?php
	class BillingInformation
	{
		public $first_name = "";
		public $last_name = "";
		public $company_name = "";
		public $address1 = "";
		public $address2 = "";
		public $city = "";
		public $province = "";
		public $country = "";
		public $postal_code = "";
		public $phone = "";
		public $fax = "";
		public $email = "";
	}

	interface IPaymentProcessor
	{
		public function GetPaymentProcessorName();
		
		public function GetAmount();
		public function SetAmount($newValue);
		
		public function GetBillingInformation();
		public function SetBillingInformation($newValue);
		
		public function GetInvoiceID();
		public function SetInvoiceID($newValue);
		
		public function Process();	
		public function Callback();
	}
	
	class PaypalIPNProcessor implements IPaymentProcessor 
	{
		private $m_amount;
		private $m_billingInformation;
		private $m_invoiceID;
		
		public function PaypalIPNProcessor()
		{
			$this->m_billingInformation = new BillingInformation();
			
			$this->ValidateConfiguration();
		}
		
		private function ValidateConfiguration()
		{
			$issues = "";
			$isValid = true;
			
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.URL");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.PageStyle");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.Business");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.CurrencyCode");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.SubmitText");
			
			if(!$isValid)
			{
				$issue = "Could not set up Paypal IPN Processor: <ul>\n".
					$issue.
					"</ul>\n";
					
				die($issue);
			}
		}
		
		private function ValidateConfigurationItem(&$issue, $name)
		{
			$isValid = true;
			
			if(!isset(Zymurgy::$config[$name]))
			{
				$issue .= "<li>The <b>$name</b> configuration must be set.</li>\n";
				$isValid = false;
			}
			
			return $isValid;
		}
		
		public function GetPaymentProcessorName()
		{
			return "Paypal IPN";
		}
		
		public function GetAmount()
		{
			return $this->m_amount;
		}
		
		public function SetAmount($newValue)
		{
			$this->m_amount = $newValue;
		}
		
		public function GetBillingInformation()
		{
			return $this->m_billingInformation;
		}
		
		public function SetBillingInformation($newValue)
		{
			$this->m_billingInformation = $newValue;
		}
		
		public function GetInvoiceID()
		{
			return $this->m_invoiceID;
		}
		
		public function SetInvoiceID($newValue)
		{
			$this->m_invoiceID = $newValue;
		}
		
		public function Process()
		{
			$output = "";
			
			$output .= "<form name=\"frmPaymentGateway\" method=\"POST\" action=\"".
				Zymurgy::$config["PaypalIPN.URL"].
				"\">\n";
				
			$output .= $this->RenderHiddenInput("cmd", "_xclick");
			$output .= $this->RenderHiddenInput("page_style", Zymurgy::$config["PaypalIPN.PageStyle"]);
			$output .= $this->RenderHiddenInput("business", Zymurgy::$config["PaypalIPN.Business"]);
			$output .= $this->RenderHiddenInput("item_name", $this->m_invoiceID);
			$output .= $this->RenderHiddenInput("currency_code", Zymurgy::$config["PaypalIPN.CurrencyCode"]);
			$output .= $this->RenderHiddenInput("amount", $this->m_amount);
			$output .= $this->RenderBillingInformation();
			
			$output .= $this->RenderSubmitButton(Zymurgy::$config["PaypalIPN.SubmitText"]);

			$output .= "</form>\n";
			
			// ZK: Not sure if this should just echo, or if it should return a string.
			// For now, it echoes.
			// return $output;
			echo $output;
		}
		
		private function RenderBillingInformation()
		{
			$output = "";
			
			$output .= $this->RenderOptionalHiddenInput(
				"first_name", 
				$this->m_billingInformation->first_name);
			$output .= $this->RenderOptionalHiddenInput(
				"last_name", 
				$this->m_billingInformation->last_name);
			$output .= $this->RenderOptionalHiddenInput(
				"address1", 
				$this->m_billingInformation->address1);
			$output .= $this->RenderOptionalHiddenInput(
				"address2", 
				$this->m_billingInformation->address2);
			$output .= $this->RenderOptionalHiddenInput(
				"city", 
				$this->m_billingInformation->city);
			$output .= $this->RenderOptionalHiddenInput(
				"state", 
				$this->m_billingInformation->province);
			$output .= $this->RenderOptionalHiddenInput(
				"zip", 
				$this->m_billingInformation->postal_code);
			$output .= $this->RenderOptionalHiddenInput(
				"country", 
				$this->m_billingInformation->country);
			$output .= $this->RenderOptionalHiddenInput(
				"day_phone_a", 
				$this->m_billingInformation->phone);
			$output .= $this->RenderOptionalHiddenInput(
				"email", 
				$this->m_billingInformation->email);
			
			return $output;
		}
		
		private function RenderHiddenInput($name, $value)
		{
			return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
		}
		
		private function RenderOptionalHiddenInput($name, $value)
		{
			if($value == "")
			{
				return "";
			}
			else 
			{
				return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
			}			
		}
		
		private function RenderSubmitButton($value)
		{
			$output = "";
			
			$output .= "<script type=\"text/javascript\">\n";
			$output .= "setTimeout('document.frmPaymentGateway.submit();', 1000);\n";
			$output .= "</script>\n";
			
			$output .= "<noscript>\n";
			$output .= "<input type=\"submit\" value=\"$value\">\n;";
			$output .= "</noscript>\n";			
			
			return $output;
		}
		
		public function Callback()
		{
			// do nothing
		}
	}
?>