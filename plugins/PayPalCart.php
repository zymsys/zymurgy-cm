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
		$this->m_PayPalCmd = "_cart";
	}
	
	public function RenderCmdInformation()
	{
		$output = $this->RenderHiddenInput("amount", 1);
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

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function GetConfigItemTypes()
	{
		//Data types are in the format:
		//Implemented:
		//Not Implemented:
//		"input.$size.$maxlength"
//		"textarea.$width.$height"
//		"html.$widthpx.$heightpx"
//		"radio.".serialize($optionarray)
//		"drop.".serialize($optionarray)
//		"attachment"
//		"money"
//		"unixdate"
//		"lookup.$table"
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
		echo "<table>\r\n";
		foreach ($items as $item)
		{
			$ppca = new PayPalCartAdd();
			$ppca->itemname = $item['name'];
			$ppca->itemamount = $item['amount'] / 100;
			echo "<tr><td>{$item['name']}</td><td>{$item['amount']}</td><td>";
			$ppca->Process();
			echo "</td></tr>\r\n";
		}
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
		$dg->AddInput('name','Name:',64,64);
		$dg->AddInput('values','Values:',200,200);
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
		$dg->AddInput('name','Name:',127,127);
		$dg->AddMoneyEditor('amount','Amount');
		$dg->AddMoneyEditor('handling','Handling');
		$dg->AddMoneyEditor('shipping','Shipping');
		$dg->AddEditor('tax','Tax Override:','float');
		$dg->AddEditor('weight','Weight:','float');
		$dg->AddDropListEditor('weightunit','Weight Unit:',array('lbs'=>'lbs','kgs'=>'kgs'));
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