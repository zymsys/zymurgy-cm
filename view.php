<?php 
class ZymurgyView
{
	/**
	 * The model we're building a view for
	 * 
	 * @var ZymurgyModel
	 */
	private $model;
	
	function __construct(ZymurgyModel $model)
	{
		$this->model = $model;
	}
	
	function getform($action,$actionParams)
	{
		$ap = array();
		foreach ($actionParams as $key=>$value) 
		{
			$ap[] = urlencode(key)."=".urlencode($value);
		}
		if (strpos($action, '?')!==false)
		{
			$action .= '&'.implode('&', $ap);
		}
		else 
		{
			$action .= '?'.implode('&', $ap);
		}
		$form = '<form method="POST" action="'.$action.'" enctype="multipart/form-data">';
		$ri = Zymurgy::$db->run("SELECT * FROM `zcm_customfield` WHERE `tableid`=".intval($this->model->getTableId()));
	}
}
?>