<?
require_once(Zymurgy::$root.'/zymurgy/xmlrpc/xmlrpc.inc');

class ZymurgyInfusionsoftWrapper
{
	private $client;

	function __construct()
	{
		$isValid = true;

		$isValid = $this->ValidateConfigurationItem($issue, "Infusionsoft URL");
		$isValid = $this->ValidateConfigurationItem($issue, "Infusionsoft API Key");

		if(!$isValid)
		{
			$issue = "Could not set up Paypal IPN Processor: <ul>\n".
				$issue.
				"</ul>\n";

			die($issue);
		}

		$this->client = new xmlrpc_client(Zymurgy::$config['Infusionsoft URL']);
		$this->client->setSSLVerifyPeer(false);
		$this->client->return_type = "phpvals";
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

	/**
	 * Execute an Infusionsoft API, and return an array of result values.
	 * This method takes API paramters as arguments to the call.  These must match the arguments expected by Infusionsoft or an error will be thrown.
	 *
	 * @param string $cmd
	 * @param varargs $args
	 * @return array
	 */
	function execute_fetch_array_va($cmd, $args)
	{
		$params = func_get_args();
		$cmd = array_shift($params);
		return $this->execute_fetch_array($cmd,$params);
	}

	/**
	 * Execute an Infusionsoft API, and return an array of result values.
	 * This method takes API paramters as an array.  These must match the arguments expected by Infusionsoft or an error will be thrown.
	 *
	 * @param string $cmd
	 * @param array $params
	 * @return array
	 */
	function execute_fetch_array($cmd,$params)
	{
		$r = $this->execute($cmd,$params);
		return count($r->val) > 0 ? $r->val[0] : null;
	}

	/**
	 * Execute an Infusionsoft API, and return an xmlrpcresp object from XMLRPC.
	 * This method takes API paramters as arguments to the call.  These must match the arguments expected by Infusionsoft or an error will be thrown.
	 *
	 * @param string $cmd
	 * @param varargs $args
	 * @return xmlrpcresp
	 */
	function execute_va($cmd, $args)
	{
		$params = func_get_args();
		$cmd = array_shift($params);
		return $this->execute($cmd,$params);
	}

	/**
	 * Execute an Infusionsoft API, and return an xmlrpcresp object from XMLRPC.
	 * This method takes API paramters as an array.  These must match the arguments expected by Infusionsoft or an error will be thrown.
	 *
	 * @param string $cmd
	 * @param array $params
	 * @return xmlrpcresp
	 */
	function execute($cmd,$params)
	{
		array_unshift($params,Zymurgy::$config['Infusionsoft API Key']);
		$eparams = array();
		foreach ($params as $param)
		{
			$eparams[] = php_xmlrpc_encode($param,array('auto_dates'));
		}
		$call = new xmlrpcmsg($cmd, $eparams);
		$result = $this->client->send($call);
		if ($result->faultCode())
		{
			throw new Exception("Fault [".$result->faultCode()."] running $cmd: ".$result->faultString());
		}
		return $result;
	}
}
?>