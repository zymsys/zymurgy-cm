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
		public function GetReturnQueryParameter();
		
		public function GetAmount();
		public function SetAmount($newValue);
		
		public function GetBillingInformation();
		public function SetBillingInformation($newValue);
		
		public function GetInvoiceID();
		public function SetInvoiceID($newValue);
		
		public function GetReturnURL();
		public function SetReturnURL($newValue);
		
		public function GetCancelURL();
		public function SetCancelURL($newValue);
		
		public function Process();	
	}
	
	abstract class PaymentProcessor
	{		
		protected $m_amount;
		protected $m_billingInformation;
		protected $m_invoiceID;
		protected $m_returnURL;
		protected $m_cancelURL;
		
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
		
		public function GetReturnURL()
		{
			return $this->m_returnURL;
		}
		
		public function SetReturnURL($newValue)
		{
			$this->m_returnURL = $newValue;
		}
		
		public function GetCancelURL()
		{
			return $this->m_cancelURL;
		}
		
		public function SetCancelURL($newValue)
		{
			$this->m_cancelURL = $newValue;
		}
		
		protected function ValidateConfigurationItem(&$issue, $name)
		{
			$isValid = true;
			
			if(!isset(Zymurgy::$config[$name]))
			{
				$issue .= "<li>The <b>$name</b> configuration must be set.</li>\n";
				$isValid = false;
			}
			
			return $isValid;
		}
		
		protected function RenderHiddenInput($name, $value)
		{
			return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
		}
		
		protected function RenderOptionalHiddenInput($name, $value)
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
		
		protected function RenderSubmitButton($value)
		{
			$output = "";
			
			$output .= "<script type=\"text/javascript\">\n";
			$output .= "setTimeout('document.frmPaymentGateway.submit();', 1000);\n";
			$output .= "</script>\n";
			
			$output .= "<noscript>\n";
			$output .= "<input type=\"submit\" value=\"$value\">\n";
			$output .= "</noscript>\n";			
			
			return $output;
		}
	}
	
	class PaypalIPNProcessor extends PaymentProcessor implements IPaymentProcessor 
	{
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
		
		public function GetPaymentProcessorName()
		{
			return "Paypal IPN";
		}
		
		public function GetReturnQueryParameter()
		{
			return "merchant_return_link";
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
			
			$output .= $this->RenderOptionalHiddenInput("return", $this->m_returnURL);
			$output .= $this->RenderOptionalHiddenInput("cancel_return", $this->m_cancelURL);
			
			$output .= $this->RenderSubmitButton(Zymurgy::$config["PaypalIPN.SubmitText"]);

			$output .= "</form>\n";
			
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
	}
	
	class MonerisEselectProcessor extends PaymentProcessor implements IPaymentProcessor 
	{
		public function MonerisEselectProcessor()
		{
			$this->m_billingInformation = new BillingInformation();
			
			$this->ValidateConfiguration();
		}
		
		private function ValidateConfiguration()
		{
			$issues = "";
			$isValid = true;
			
			$isValid = $this->ValidateConfigurationItem($issue, "MonerisEselect.URL");
			$isValid = $this->ValidateConfigurationItem($issue, "MonerisEselect.StoreID");
			$isValid = $this->ValidateConfigurationItem($issue, "MonerisEselect.HPPKey");
			$isValid = $this->ValidateConfigurationItem($issue, "MonerisEselect.SubmitText");
			
			if(!$isValid)
			{
				$issue = "Could not set up Moneris eSELECT Processor: <ul>\n".
					$issue.
					"</ul>\n";
					
				die($issue);
			}
		}
		
		public function GetPaymentProcessorName()
		{
			return "Moneris eSELECT Plus";
		}
		
		public function GetReturnQueryParameter()
		{
			return "";
		}
		
		public function Process()
		{
			$output = "";
			
			$output .= "<form name=\"frmPaymentGateway\" method=\"POST\" action=\"".
				Zymurgy::$config["MonerisEselect.URL"].
				"\">\n";
				
			$output .= $this->RenderHiddenInput("ps_store_id", Zymurgy::$config["MonerisEselect.StoreID"]);
			$output .= $this->RenderHiddenInput("hpp_key", Zymurgy::$config["MonerisEselect.HPPKey"]);
			$output .= $this->RenderHiddenInput("order_id", $this->m_invoiceID);
			$output .= $this->RenderHiddenInput("charge_total", $this->m_amount);
			$output .= $this->RenderBillingInformation();
			
			// $output .= $this->RenderOptionalHiddenInput("return", $this->m_returnURL);
			// $output .= $this->RenderOptionalHiddenInput("cancel_return", $this->m_cancelURL);
			
			$output .= $this->RenderSubmitButton(Zymurgy::$config["MonerisEselect.SubmitText"]);

			$output .= "</form>\n";
			
			echo $output;
		}
		
		private function RenderBillingInformation()
		{
			$output = "";
			
			$output .= $this->RenderOptionalHiddenInput(
				"bill_first_name", 
				$this->m_billingInformation->first_name);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_last_name", 
				$this->m_billingInformation->last_name);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_company_name", 
				$this->m_billingInformation->company_name);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_address_one", 
				$this->m_billingInformation->address1);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_city", 
				$this->m_billingInformation->city);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_state_or_province", 
				$this->m_billingInformation->province);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_postal_code", 
				$this->m_billingInformation->postal_code);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_country", 
				$this->m_billingInformation->country);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_phone", 
				$this->m_billingInformation->phone);
			$output .= $this->RenderOptionalHiddenInput(
				"bill_fax", 
				$this->m_billingInformation->fax);
			
			return $output;
		}
	}	
	
	class AuthorizeNetProcessor extends PaymentProcessor implements IPaymentProcessor 
	{
		public function AuthorizeNetProcessor()
		{
			$this->m_billingInformation = new BillingInformation();
			
			$this->ValidateConfiguration();
		}
		
		private function ValidateConfiguration()
		{
			$issues = "";
			$isValid = true;
			
			$isValid = $this->ValidateConfigurationItem($issue, "AuthorizeNET.URL");
			$isValid = $this->ValidateConfigurationItem($issue, "AuthorizeNET.Login");
			$isValid = $this->ValidateConfigurationItem($issue, "AuthorizeNET.TransactionKey");
			$isValid = $this->ValidateConfigurationItem($issue, "AuthorizeNET.SubmitText");
			
			if(!$isValid)
			{
				$issue = "Could not set up Authorize.NET Processor: <ul>\n".
					$issue.
					"</ul>\n";
					
				die($issue);
			}
		}
		
		public function GetPaymentProcessorName()
		{
			return "Authorize.NET";
		}
		
		public function GetReturnQueryParameter()
		{
			return "";
		}
		
		public function Process()
		{
			$output = "";
			
			$output .= "<form name=\"frmPaymentGateway\" method=\"POST\" action=\"".
				Zymurgy::$config["AuthorizeNET.URL"].
				"\">\n";
				
			$output .= $this->RenderHiddenInput("x_version", "3.1");
			$output .= $this->RenderHiddenInput("x_method", "CC");
			$output .= $this->RenderHiddenInput("x_type", "AUTH_CAPTURE");
			$output .= $this->RenderHiddenInput("x_login", Zymurgy::$config["AuthorizeNET.Login"]);
			$output .= $this->RenderHiddenInput("x_tran_key", Zymurgy::$config["AuthorizeNET.TransactionKey"]);
			$output .= $this->RenderHiddenInput("x_relay_response", "FALSE");
			$output .= $this->RenderHiddenInput("x_url", "FALSE");
			$output .= $this->RenderHiddenInput("x_po_num", $this->m_invoiceID);
			$output .= $this->RenderHiddenInput("x_amount", $this->m_amount);
			$output .= $this->RenderBillingInformation();
			
			// $output .= $this->RenderOptionalHiddenInput("return", $this->m_returnURL);
			// $output .= $this->RenderOptionalHiddenInput("cancel_return", $this->m_cancelURL);
			
			$output .= $this->RenderSubmitButton(Zymurgy::$config["MonerisEselect.SubmitText"]);

			$output .= "</form>\n";
			
			echo $output;
		}
		
		private function RenderBillingInformation()
		{
			$output = "";
						
			return $output;
		}
	}	
?>