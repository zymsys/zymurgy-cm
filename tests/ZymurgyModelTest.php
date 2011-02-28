<?php
require_once 'testlib.php'; 
require_once Zymurgy::$root."/zymurgy/model.php";

class ZymurgyModelTest extends PHPUnit_Framework_TestCase
{
	private $testarray = array('a'=>'a','b'=>'b','c'=>'c');
	
	private function coreWriter($member,$tname)
	{
		TestHelper::$helper->become($member);
		$m = new ZymurgyModel($tname);
		$r = $m->write($this->testarray);
		$this->assertEquals($r,true);
	}
	
	public function testPrivWriteRootMember()
	{
		$this->coreWriter(TestHelper::$helper->member_priv, 'zcm_unittest_mroot');
	}
	
	public function testPrivWriteRootGlobal()
	{
		$this->coreWriter(TestHelper::$helper->member_priv, 'zcm_unittest_root');
	}
	
	private function coreReader($member,$tname)
	{
		TestHelper::$helper->become($member);
		$m = new ZymurgyModel($tname);
		return $m->read();
	}
	
	private function coreReaderSuccess($member,$tname)
	{
		$r = $this->coreReader($member, $tname);
		$this->assertArrayHasKey(1,$r);
		$row = $r[1];
		$this->assertArrayHasKey('id',$row);
		$this->assertEquals($row['id'],1);
		foreach (array_keys($this->testarray) as $key)
		{
			$this->assertArrayHasKey($key,$row);
			$this->assertEquals($row[$key],$this->testarray[$key]);
		}
	}
	
	private function coreReaderNoRows($member,$tname)
	{
		$r = $this->coreReader($member,$tname);
		$this->assertEquals(count($r),0);
	}
	
	/**
	 * @depends testPrivWriteRootMember
	 */
	public function testPrivReadRootMember()
	{
		$this->coreReaderSuccess(TestHelper::$helper->member_priv, 'zcm_unittest_mroot');
	}
	
	/**
	 * @depends testPrivWriteRootMember
	 */
	public function testRegReadRootOtherMember()
	{
		$this->coreReaderNoRows(TestHelper::$helper->member_reg, 'zcm_unittest_mroot');
	}
}
?>