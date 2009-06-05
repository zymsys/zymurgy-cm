<?
/*
https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_Appx_websitestandard_htmlvariables#id085LC0Z0E7U
*/

require_once(Zymurgy::$root.'/zymurgy/include/payment.php');

class PayPalCartItemOption
{
	public $name;
	public $value;
}

class PayPalCartAdd extends PaypalIPNProcessor
{
	public $itemamount;
	public $itemhandling;
	public $itemname;
	public $options = array();
	public $shipping;
	public $tax;
	public $weight;
	public $weightunit;

	function __construct()
	{
		$additionalItems = array("PaypalIPN.AddToCartText");

		parent::__construct($additionalItems);

		$this->m_PayPalCmd = "_cart";
		$this->m_buttonText = Zymurgy::$config["PaypalIPN.AddToCartText"];
	}

	public function RenderCmdInformation()
	{
		$output = "";

		$output .= $this->RenderHiddenInput("amount", $this->itemamount);
		$output .= $this->RenderHiddenInput("add", 1);

		return $output;
	}

	public function Process()
	{
		$this->SetInvoiceID($this->itemname);
		parent::Process();
	}
}

class PayPalCart extends PluginBase
{
	function GetTitle()
	{
		return 'PayPal Shopping Cart';
	}

	function GetUninstallSQL()
	{
		//return 'drop table bobotea';
	}

	function GetConfigItems()
	{
		return array();
	}

	function GetDefaultConfig()
	{
		return array();
	}

	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildMenuItem(
			$r,
			"View items",
			"pluginadmin.php?pid={pid}&iid={iid}&name={name}",
			0);
		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function GetConfigItemTypes()
	{
		return array();
	}

	function Initialize()
	{
		$tableDefinitions = array(
			array(
				"name" => "zcm_paypalcartitem",
				"columns" => array(
					DefineTableField("id", "SERIAL"),
					DefineTableField("instance", "bigint"),
					DefineTableField("amount", "bigint"),
					DefineTableField("handling", "bigint"),
					DefineTableField("shipping", "bigint"),
					DefineTableField("tax", "float"),
					DefineTableField("weight", "float"),
					DefineTableField("weightunit", "varchar(3)"),
					DefineTableField("name", "varchar(127)")
				),
				"indexes" => array(
					DefineIndexField('instance')
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_paypalcartitemoption",
				"columns" => array(
					DefineTableField("id", "SERIAL"),
					DefineTableField("paypalcartitem", "bigint"),
					DefineTableField("name", "varchar(64)"),
					DefineTableField("values", "varchar(200)")
				),
				"indexes" => array(),
				"primarykey" => "id",
				"engine" => "MyISAM")
		);

		ProcessTableDefinitions($tableDefinitions);
	}

	function Render()
	{
		$items = array();
		$ri = Zymurgy::$db->run("select * from zcm_paypalcartitem where instance={$this->iid}");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$items[$row['id']] = $row;
		}
		Zymurgy::$db->free_result($ri);
		foreach($items as $key=>$item)
		{
			$options = array();
			$ri = Zymurgy::$db->run("select * from zcm_paypalcartitemoption where paypalcartitem=$key");
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$options[$row['id']] = $row;
			}
			Zymurgy::$db->free_result($ri);
			$items[$key]['options'] = $options;
		}
		echo "<table class=\"zcmcart\">\r\n";

		echo "<thead>\r\n";
		echo "<th class=\"itemname\">Item</th>\r\n";
		echo "<th class=\"itemamount\">Price</th>\r\n";
		echo "<th class=\"itemcommand\">&nbsp;</th>\r\n";
		echo "</thead>\r\n";

		echo "<tbody>\r\n";

		foreach ($items as $item)
		{
			$ppca = new PayPalCartAdd();
			$ppca->itemname = $item['name'];
			$ppca->itemamount = $item['amount'] / 100;
			echo "<tr>\r\n";
			echo "<td class=\"itemname\">{$ppca->itemname}</td>\r\n";
			echo "<td class=\"itemamount\">\${$ppca->itemamount}</td>";
			echo "<td class=\"itemcommand\">";
			$ppca->Process();
			echo "</td>\r\n";
			echo "</tr>\r\n";
		}

		echo "</tbody>\r\n";

		$cart = new PaypalIPNProcessor();
		$cart->SetPaypalCommand("_cart");

		echo "<tfoot>\r\n";
		echo "<tr>\r\n";
		echo "<td colspan=\"3\" align=\"right\" class=\"itemcommand\">\r\n";
		echo $cart->Process();
		echo "</td>\r\n";
		echo "</tr>\r\n";

		echo "</table>\r\n";
	}

	function AdminMenuText()
	{
		return 'PayPalCart';
	}

	function RenderAdmin()
	{
		if (array_key_exists('ppcio',$_GET))
		{
			$this->RenderItemOptionAdmin();
		}
		else
		{
			$this->RenderItemAdmin();
		}
	}

	function RenderItemOptionAdmin()
	{
		$ppcio = 0 + $_GET['ppcio'];

		$ds = new DataSet('zcm_paypalcartitemoption','id');
		$ds->AddColumns('id','paypalcartitem','name','values');

		$dg = new DataGrid($ds);
		$dg->AddColumn('Name','name');
		$dg->AddColumn('Values','values');
		$dg->AddInput('name','Name:',64,50);
		$dg->AddInput('values','Values:',200,50);
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel = 'Add New Item Option';
		$dg->AddConstant('paypalcartitem',$ppcio);
		$dg->Render();
	}

	function RenderItemAdmin()
	{
		$ds = new DataSet('zcm_paypalcartitem','id');
		$ds->AddColumns('id','instance','amount','handling','shipping','tax','weight','weightunit','name');

		$dg = new DataGrid($ds);
		$dg->UsePennies = true;
		$dg->AddColumn('Name','name');
		$dg->AddColumn('Amount','amount');
		$dg->AddInput('name','Name:',127,50);
		$dg->AddMoneyEditor('amount','Amount');
		// $dg->AddMoneyEditor('handling','Handling');
		// $dg->AddMoneyEditor('shipping','Shipping');
		// $dg->AddEditor('tax','Tax Override:','float');
		// $dg->AddEditor('weight','Weight:','float');
		// $dg->AddDropListEditor('weightunit','Weight Unit:',array('lbs'=>'lbs','kgs'=>'kgs'));
		$dg->AddButton('Options',$dg->BuildSelfReference(array()).'&ppcio={0}');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel = 'Add New Item';
		$dg->AddConstant('instance',$this->iid);
		$dg->Render();
	}
}

/**
 * Make a PayPalCart
 *
 * @return PayPalCart
 */
function PayPalCartFactory()
{
	return new PayPalCart();
}
?>