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
				"amount",
				"input.10.9");
			$this->BuildConfig(
				$r,
				"Invoice Prefix",
				"Invoice",
				"input.30.100");
			
			return $r;
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
	}
		
	function PaymentFormFactory()
	{
		return new PaymentForm();
	}
?>