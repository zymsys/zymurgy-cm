<?php
/**
 * Warning: This feature is still under development and the ZymurgyViewInterface is
 * likely to change.  Use at your own risk - updates are likely to break your own
 * implemtations of ZymurgyViewInterface classes.
 * 
 * @author Vic Metcalfe
 */ 
interface ZymurgyViewInterface
{
	function showform($action,$actionParams,$submitValue="Save",$data = false);
}

class ZymurgyView implements ZymurgyViewInterface
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
	
	/**
	 * Take a model and construct a ZymurgyView for that table.
	 * You can define your own views as TablenameCustomView and the factory will generate those instead.
	 * For example, if the table behind the model was 'foo' the custom view would be called FooCustomView, 
	 * and it would still take its model as a parameter.  Custom views must implement ZymurgyViewInterface.
	 * 
	 * @param ZymurgyModel $model
	 */
	public static function factory(ZymurgyModel $model)
	{
		$table = $model->getTableName();
		$camelname = strtoupper($table[0]).strtolower(substr($table,1)).'CustomView';
		if (class_exists($camelname))
		{
			$m = new $camelname($model);
		}
		else if (class_exists('CustomView'))
		{
			$m = new CustomView($model);
		}
		else
		{
			$m = new ZymurgyView($model);
		}
		return $m;
	}
	
	function showform($action,$actionParams,$submitValue="Save",$data = false)
	{
		$table = $this->model->getTableData();
		if ($data === false)
		{//No form data provided?  Init to empty array.
			$data = array();
		}
		$ap = array();
		foreach ($actionParams as $key=>$value) 
		{
			$ap[] = urlencode($key)."=".urlencode($value);
		}
		if (strpos($action, '?')!==false)
		{
			$action .= '&'.implode('&', $ap);
		}
		else 
		{
			$action .= '?'.implode('&', $ap);
		}
		echo '<form method="POST" action="'.$action.'" enctype="multipart/form-data">';
		if (!empty($table['detailforfield']) && array_key_exists($table['detailforfield'], $data))
		{ //Create a hidden field which relates this data to a parent row
			echo '<input type="hidden" name="'.htmlspecialchars($table['detailforfield']).
				'" value="'.htmlspecialchars($data[$table['detailforfield']]).'">';
		}
		if (!empty($table['idfieldname']) && array_key_exists($table['idfieldname'], $data))
		{ //Create a hidden field for this row's key
			echo '<input type="hidden" name="'.htmlspecialchars($table['idfieldname']).
				'" value="'.htmlspecialchars($data[$table['idfieldname']]).'">';
		}
		echo '<table class="zv_table_'.htmlspecialchars($table['tname']).'">';
		$iw = new InputWidget();
		$ri = Zymurgy::$db->run("SELECT * FROM `zcm_customfield` WHERE `tableid`=".intval($this->model->getTableId()));
		$iseven = false;
		while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
		{
			echo '<tr id="zvr_'.$table['tname'].'_'.$row['id'].'" class="zvr_'.($iseven ? 'even' : 'odd').'">';
			$iseven = !$iseven;
			echo '<td class="zv_caption">'.htmlspecialchars($row['caption']).'</td>';
			echo '<td class="zv_input">';
			$iw->Render($row['inputspec'], $row['cname'], array_key_exists($row['cname'], $data) ? $data[$row['cname']] : '');
			echo "</td></tr>";
		}
		echo '<tr><td class="zv_submit" colspan="2"><input type="submit" value="'.
			htmlspecialchars($submitValue).'"></td></tr>';
		echo '</table></form>';
	}
	
	function gettable()
	{
		
	}
}
?>