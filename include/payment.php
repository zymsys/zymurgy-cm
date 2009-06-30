<?php
	ini_set("display_errors", 1);

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

	class PaymentTransaction
	{
		public $processor = "";
		public $invoice_id = "";
		public $status_code = "";
		public $postback_variables = array();
	}

	interface IPaymentProcessor
	{
		public function GetPaymentProcessorName();
		public function GetReturnQueryParameter();
		public function GetCallbackQueryParameter();

		public function GetAmount();
		public function SetAmount($newValue);

		public function GetInvoiceID();
		public function SetInvoiceID($newValue);

		public function GetReturnURL();
		public function SetReturnURL($newValue);

		public function GetCancelURL();
		public function SetCancelURL($newValue);

		public function GetNotifyURL();
		public function SetNotifyURL($newValue);

		public function GetBillingInformation();
		public function SetBillingInformation($newValue);

		public function GetPaymentTransaction();

		public function IsReportedPostVar($var);

		public function Process();
		public function Callback();
	}

	abstract class PaymentProcessor
	{
		protected $m_amount;
		protected $m_billingInformation;
		protected $m_paymentTransaction;
		protected $m_invoiceID;
		protected $m_returnURL;
		protected $m_cancelURL;
		protected $m_notifyURL;

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

		public function GetPaymentTransaction()
		{
			return $this->m_paymentTransaction;
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

		public function GetNotifyURL()
		{
			return $this->m_notifyURL;
		}

		public function SetNotifyURL($newValue)
		{
			$this->m_notifyURL = $newValue;
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

		protected function RenderSubmitButton($value, $forceRender = false)
		{
			$output = "";

			if(!$forceRender && Zymurgy::$config["PaymentProcessor.SilentPost"])
			{
				$output .= "<script type=\"text/javascript\">\n";
				$output .= "setTimeout('document.frmPaymentGateway.submit();', 1000);\n";
				$output .= "</script>\n";

				$output .= "<noscript>\n";
				$output .= "<input type=\"submit\" value=\"$value\">\n";
				$output .= "</noscript>\n";
			}
			else
			{
				$output .= "<input type=\"submit\" value=\"$value\">\n";
			}

			return $output;
		}
	}

	class PaypalIPNProcessor extends PaymentProcessor implements IPaymentProcessor
	{
		protected $m_PayPalCmd = "_xclick";
		protected $m_buttonText = "";

		public function PaypalIPNProcessor(
			$additionalItems = array())
		{
			$this->m_billingInformation = new BillingInformation();
			$this->m_paymentTransaction = new PaymentTransaction();

			$this->ValidateConfiguration($additionalItems);

			$this->m_buttonText = Zymurgy::$config["PaypalIPN.SubmitText"];
		}

		private function ValidateConfiguration($additionalItems)
		{
			$issues = "";
			$isValid = true;

			$isValid = $this->ValidateConfigurationItem($issue, "PaymentProcessor.SilentPost");

			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.URL");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.PageStyle");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.Business");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.CurrencyCode");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.SubmitText");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.CallbackServer");
			$isValid = $this->ValidateConfigurationItem($issue, "PaypalIPN.CallbackPort");

			foreach($additionalItems as $item)
			{
				$isValid = $this->ValidateConfigurationItem($issue, $item);
			}

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

		public function GetCallbackQueryParameter()
		{
			return "txn_id";
		}

		public function GetPaypalCommand()
		{
			return $this->m_PayPalCmd;
		}

		public function SetPaypalCommand($newValue)
		{
			$this->m_PayPalCmd = $newValue;
		}

		public function RenderCmdInformation()
		{
			$output = "";

			switch($this->m_PayPalCmd)
			{
				case "_cart":
					$output .= $this->RenderHiddenInput("display", "1");
					break;

				default:
					$output .= $this->RenderHiddenInput("amount", $this->m_amount);
					break;
			}

			return $output;
		}

		public function Process()
		{
			$output = "";

			$output .= "<form name=\"frmPaymentGateway\" method=\"POST\" action=\"".
				Zymurgy::$config["PaypalIPN.URL"].
				"\">\n";

			$output .= $this->RenderHiddenInput("cmd", $this->m_PayPalCmd);
			$output .= $this->RenderHiddenInput("page_style", Zymurgy::$config["PaypalIPN.PageStyle"]);
			$output .= $this->RenderHiddenInput("business", Zymurgy::$config["PaypalIPN.Business"]);
			$output .= $this->RenderHiddenInput("item_name", $this->m_invoiceID);
			$output .= $this->RenderHiddenInput("currency_code", Zymurgy::$config["PaypalIPN.CurrencyCode"]);
			$output .= $this->RenderCmdInformation();
			$output .= $this->RenderBillingInformation();

			$output .= $this->RenderOptionalHiddenInput("return", $this->m_returnURL);
			$output .= $this->RenderOptionalHiddenInput("cancel_return", $this->m_cancelURL);
			$output .= $this->RenderOptionalHiddenInput("notify_url", $this->m_notifyURL);

			$output .= $this->RenderSubmitButton($this->m_buttonText);

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

		public function Callback()
		{
			$this->m_paymentTransaction->processor = $this->GetPaymentProcessorName();
			$this->m_paymentTransaction->invoice_id = $_POST["transaction_subject"];
			$this->m_paymentTransaction->status_code = "UNCONFIRMED";
			$this->m_paymentTransaction->postback_variables = $_POST;
			return;

			$req = "cmd=_notify-validate";

			foreach($_POST as $key => $value)
			{
				$value = urlencode(stripslashes($value));
				$req .= "&$key=$value";
			}

			$header = "";
			$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Host: ".str_replace("ssl://", "", Zymurgy::$config["PaypalIPN.CallbackServer"])."\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: ".strlen($req)."\r\n\r\n";

			$fp = fsockopen(
				Zymurgy::$config["PaypalIPN.CallbackServer"],
				Zymurgy::$config["PaypalIPN.CallbackPort"],
				$errno,
				$errstr,
				30);

			if(!$fp)
			{
				$this->m_paymentTransaction->status_code = "NORESPONSE";

				$err = array();
				$err["Err#".$errno] = $errstr;

				$this->m_paymentTransaction->postback_variables = $err;
			}
			else
			{
				fputs($fp, $header, $req);

				$res = "";
				$cntr = 0;
				while(!feof($fp) && $cntr < 100)
				{
					$res .= fgets($fp, 1024);
					$cntr++;
				}

				$this->m_paymentTransaction->status_code = $res;
				$this->m_paymentTransaction->postback_variables = $_POST;
			}
		}

		public function IsReportedPostVar($var)
		{
			return true;
		}
	}

	class MonerisEselectProcessor extends PaymentProcessor implements IPaymentProcessor
	{
		public function MonerisEselectProcessor()
		{
			$this->m_billingInformation = new BillingInformation();
			$this->m_paymentTransaction = new PaymentTransaction();

			$this->ValidateConfiguration();
		}

		private function ValidateConfiguration()
		{
			$issues = "";
			$isValid = true;

			$isValid = $this->ValidateConfigurationItem($issue, "PaymentProcessor.SilentPost");

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

		public function GetCallbackQueryParameter()
		{
			return "response_order_id";
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

		public function Callback()
		{
			$this->m_paymentTransaction->processor = $this->GetPaymentProcessorName();
			$this->m_paymentTransaction->invoice_id = $_POST["response_order_id"];
			$message = explode(" ", $_POST["message"]);
			$this->m_paymentTransaction->status_code = $message[0];
			$this->m_paymentTransaction->postback_variables = $_POST;
		}

		private $m_invalidVars = array(
				"response_order_id",
				"date_stamp",
				"time_stamp",
				"message");

		public function IsReportedPostVar($var)
		{
			return !in_array($var, $this->m_invalidVars);
		}
	}

	class AuthorizeNetProcessor extends PaymentProcessor implements IPaymentProcessor
	{
		public function AuthorizeNetProcessor()
		{
			$this->m_billingInformation = new BillingInformation();
			$this->m_paymentTransaction = new PaymentTransaction();

			$this->ValidateConfiguration();
		}

		private function ValidateConfiguration()
		{
			$issues = "";
			$isValid = true;

			$isValid = $this->ValidateConfigurationItem($issue, "PaymentProcessor.SilentPost");

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

		public function GetCallbackQueryParameter()
		{
			return "nocallback";
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

		public function Callback()
		{
			$this->m_paymentTransaction->processor = $this->GetPaymentProcessorName();
			$this->m_paymentTransaction->invoice_id = "n/a";
			$this->m_paymentTransaction->status_code = "Posted Back";
			$this->m_paymentTransaction->postback_variables = $_POST;
		}

		public function IsReportedPostVar($var)
		{
			return true;
		}
	}

	class GoogleCheckoutProcessor extends PaymentProcessor implements IPaymentProcessor
	{
		public function GoogleCheckoutProcessor(
			$additionalItems = array())
		{
			$this->m_billingInformation = new BillingInformation();
			$this->m_paymentTransaction = new PaymentTransaction();

			$this->ValidateConfiguration($additionalItems);

			$this->m_buttonText = "";
		}

		private function ValidateConfiguration($additionalItems)
		{
			$issues = "";
			$isValid = true;

			$isValid = $this->ValidateConfigurationItem($issue, "PaymentProcessor.SilentPost");

			$isValid = $this->ValidateConfigurationItem($issue, "GoogleCheckout.URL");
			$isValid = $this->ValidateConfigurationItem($issue, "GoogleCheckout.MerchantID");
			$isValid = $this->ValidateConfigurationItem($issue, "GoogleCheckout.MerchantKey");
			$isValid = $this->ValidateConfigurationItem($issue, "GoogleCheckout.PageStyle");
			$isValid = $this->ValidateConfigurationItem($issue, "GoogleCheckout.CurrencyCode");

			foreach($additionalItems as $item)
			{
				$isValid = $this->ValidateConfigurationItem($issue, $item);
			}

			if(!$isValid)
			{
				$issue = "Could not set up Google Checkout Processor: <ul>\n".
					$issue.
					"</ul>\n";

				die($issue);
			}
		}
		public function GetPaymentProcessorName()
		{
			return "Google Checkout";
		}

		public function GetReturnQueryParameter()
		{
			return "merchant_return_link";
		}

		public function GetCallbackQueryParameter()
		{
			return "txn_id";
		}

		public function Process()
		{
			$output = "";

			$output .= "<form name=\"frmPaymentGateway\" method=\"POST\" action=\"".
			 	Zymurgy::$config["GoogleCheckout.URL"].
			 	"/api/checkout/v2/checkoutForm/Merchant/".
			 	Zymurgy::$config["GoogleCheckout.MerchantID"].
				"\" id=\"BB_BuyButtonForm\" name=\"BB_BuyButtonForm\">\n";

			$output .= $this->RenderHiddenInput("item_name_1", $this->m_invoiceID);
			$output .= $this->RenderHiddenInput("item_description_1", "");
			$output .= $this->RenderHiddenInput("item_quantity_1", "1");
			$output .= $this->RenderHiddenInput("item_price_1", $this->m_amount);
			$output .= $this->RenderHiddenInput(
				"item_currency_1",
				Zymurgy::$config["GoogleCheckout.CurrencyCode"]);

			$output .= $this->RenderOptionalHiddenInput("_charset_", "utf-8");

			$output .= $this->RenderSubmitButton("");

			$output .= "</form>\n";

			echo $output;
		}

		public function RenderSubmitButton($value, $forceRender = false)
		{
			$output = "";

			if(!$forceRender && Zymurgy::$config["PaymentProcessor.SilentPost"])
			{
				$output .= "<script type=\"text/javascript\">\n";
				$output .= "setTimeout('document.frmPaymentGateway.submit();', 1000);\n";
				$output .= "</script>\n";

				$output .= "<noscript>\n";
				$output .= "<input alt=\"\" src=\"".
				 	Zymurgy::$config["GoogleCheckout.URL"].
					"/buttons/buy.gif?merchant_id=".
				 	Zymurgy::$config["GoogleCheckout.MerchantID"].
					"&amp;w=117&amp;h=48&amp;style=".
				 	Zymurgy::$config["GoogleCheckout.PageStyle"].
					"&amp;variant=text&amp;loc=en_US\" type=\"image\"/>";
				$output .= "</noscript>\n";
			}
			else
			{
				$output .= "<input alt=\"\" src=\"".
				 	Zymurgy::$config["GoogleCheckout.URL"].
					"/buttons/buy.gif?merchant_id=".
				 	Zymurgy::$config["GoogleCheckout.MerchantID"].
					"&amp;w=117&amp;h=48&amp;style=".
				 	Zymurgy::$config["GoogleCheckout.PageStyle"].
					"&amp;variant=text&amp;loc=en_US\" type=\"image\"/>";
			}

   			return $output;
		}

		public function Callback()
		{
			$this->m_paymentTransaction->processor = $this->GetPaymentProcessorName();

			$postVarString = file_get_contents("php://input");
			$postVarString = str_replace("xmlns=", "a=", $postVarString);
			$postVarString = str_replace("UTF-8", "ISO-8859-1", $postVarString);
			$this->m_paymentTransaction->postback_variables["xml"] = $postVarString;

			$xml = new SimpleXMLElement($postVarString);
			$notification = $xml[0];

			// Set the default status code, for items where we are not pulling it
			// from the content of the XML response
			$this->m_paymentTransaction->status_code = str_replace(
				"-notification",
				"",
				$notification->getName());

			// Set the default invoice ID to the Google Order Number, for items where
			// we cannot derive the real invoice ID from the XML response. It will then
			// be up to the consumer (PaymentForm, etc.) to cross-reference the Google
			// Order Number with the real invoice ID
			$this->m_paymentTransaction->invoice_id = $notification->{"google-order-number"};

			switch($notification->getName())
			{
				case "new-order-notification":
					$itemPath = $xml->xpath("//item");
					$item = $itemPath[0];
					$this->m_paymentTransaction->invoice_id = $item->{"item-name"};

					$this->m_paymentTransaction->status_code = $notification->{"financial-order-state"};

					break;

				case "risk-information-notification":
					// no change to status code - this callback is informational only
					break;

				case "order-state-change-notification":
					$this->m_paymentTransaction->status_code = $notification->{"new-financial-order-state"};

					break;

				case "charge-amount-notification":
					break;

				case "refund-amount-notification":
					break;

				case "chargeback-amount-notification":
					break;

				case "authorization-amount-notification":
					break;
			}

			return;
		}

		public function IsReportedPostVar($var)
		{
			return true;
		}
	}
?>