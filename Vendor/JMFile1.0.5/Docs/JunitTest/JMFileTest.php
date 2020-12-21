<?php
require __DIR__.'/../../Vendor/Bootstrap/Autoloader.php';
use \JMFile\JMFile as  JMF;
\Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../Examples/')->addRoot(__DIR__.'/../../../')->init();

class JMFile extends PHPUnit_Framework_TestCase
{
	
	protected static $JMFile;
	
	public static function setUpBeforeClass()
	{
		self::$JMFile = JMF::instance();
	}
	
	public static function tearDownAfterClass()
	{
		self::$JMFile = NULL;
	}

	/**
	 * upload 方法测试
	 */
    public function testUpload() {
    	//正常
    	$filepath = realpath('../Test/123.jpg');
    	$path = "/test/";
    	$filename = '123.jpg';    	    	    	
    	$result = self::$JMFile->upload($filepath, $path, $filename);    		  
		$this->assertInternalType('string', $result);
		$result =json_decode($result, TRUE);
		$this->assertInternalType('array', $result);
		$this->assertNotSame(null, $result);		
		$this->assertArrayHasKey('code', $result);
		$this->assertEquals(1000, $result['code']);
		$this->assertArrayHasKey('paths', $result);
		
		//非正常文件, (代码覆盖)
		$filepath = './123.jpg';
		$path = "/test/";
		$filename = '123.jpg';
		$result = self::$JMFile->upload($filepath, $path, $filename);
		$this->assertInternalType('string', $result);
		$result =json_decode($result, TRUE);
		$this->assertInternalType('array', $result);
		$this->assertNotSame(null, $result);
		$this->assertArrayHasKey('code', $result);
		$this->assertEquals(1005, $result['code']);
		$this->assertArrayHasKey('info', $result);
		$this->assertEquals("can not find file, error path!", $result['info']);
		
		//$path为空或null
		$filepath = realpath('../Test/123.jpg');
    	$path = array(null, '');
    	$filename = '123.jpg';   
    	foreach ($path as $value) {    		    	
			$result = self::$JMFile->upload($filepath, $value, $filename);
			$this->assertInternalType('string', $result);
			$result =json_decode($result, TRUE);
			$this->assertInternalType('array', $result);
			$this->assertArrayHasKey('code', $result);
			$this->assertEquals(1005, $result['code']);
			$this->assertArrayHasKey('info', $result);
			$this->assertEquals("path can not empty!", $result['info']);
    	}
    	
    	//$path为无权限访问地址
    	$filepath = realpath('../Test/123.jpg');
    	$path = '/test2';
    	$filename = '123.jpg';
    	$result = self::$JMFile->upload($filepath, $path, $filename);
    	$this->assertInternalType('string', $result);
    	$result =json_decode($result, TRUE);
    	$this->assertInternalType('array', $result);
    	$this->assertArrayHasKey('code', $result);
    	$this->assertEquals(1001, $result['code']);
    	$this->assertArrayHasKey('info', $result);
    	$this->assertEquals("user no permission operate the dir.", $result['info']);

    	//文件名为空或Null
    	$filepath = realpath('../Test/123.jpg');
    	$path = '/test';
    	$filename = array(null, '');
    	foreach ($filename as $value) {
    		$result = self::$JMFile->upload($filepath, $path, $value);
    		$this->assertInternalType('string', $result);
    		$result =json_decode($result, TRUE);
    		$this->assertInternalType('array', $result);
    		$this->assertArrayHasKey('code', $result);
    		$this->assertEquals(1005, $result['code']);
    		$this->assertArrayHasKey('info', $result);
    		$this->assertEquals("fileName can not empty!", $result['info']);
    	}

    }
    
    /**
     * download 方法测试
     */
    public function testDownload() {
    	
    	//正常
    	$path = "/test/20151210_202638_862.jpg";
		$folder = "../Test/download/";
    	$filename = '123.jpg';   	
    	$result = self::$JMFile->download($path, $folder, $filename);
    	$this->assertInternalType('string', $result);
    	$result =json_decode($result, TRUE);
    	$this->assertInternalType('array', $result);
    	$this->assertArrayHasKey('code', $result);
    	$this->assertEquals(1000, $result['code']);
    	$this->assertArrayHasKey('info', $result);
    	$this->assertEquals("success!", $result['info']);
    	$this->assertFileExists($folder);//成功后文件存在
    	
    	
    	//path 为空情况
    	$path = array(null, '', '    ');
    	$folder = "../Test/download/";
    	$filename = '123.jpg';   	
    	foreach ($path as $value) {
	    	$result = self::$JMFile->download($value, $folder, $filename);
	    	$this->assertInternalType('string', $result);
	    	$result =json_decode($result, TRUE);
	    	$this->assertInternalType('array', $result);
	    	$this->assertArrayHasKey('code', $result);
	    	$this->assertEquals(1005, $result['code']);
	    	$this->assertArrayHasKey('info', $result);
	    	$this->assertEquals("path error, can not empty!", $result['info']);
    	}
    	           	
    	//folder 为空或者不是一个可以用的路径
    	$path = "/test/20151210_202638_862.jpg";
    	$folder = array(null, '', './download');
    	$filename = '123.jpg';
    	foreach ($folder as $value) {
    		$result = self::$JMFile->download($path, $value, $filename);
    		$this->assertInternalType('string', $result);
    		$result =json_decode($result, TRUE);
    		$this->assertInternalType('array', $result);
    		$this->assertArrayHasKey('code', $result);
    		$this->assertEquals(1005, $result['code']);
    		$this->assertArrayHasKey('info', $result);
    		$this->assertEquals("Folder does not exist!", $result['info']);
    	}
    	
    	//没有filename
    	$path = "/test/20151210_202638_862.jpg";
    	$folder = "../Test/download/";
    	$result = self::$JMFile->download($path, $folder);
    	$this->assertInternalType('string', $result);
    	$result =json_decode($result, TRUE);
    	$this->assertInternalType('array', $result);
    	$this->assertArrayHasKey('code', $result);
    	$this->assertEquals(1000, $result['code']);
    	$this->assertArrayHasKey('info', $result);
    	$this->assertEquals("success!", $result['info']);
    	$this->assertFileExists($folder);//成功后文件存在
    	
    	//path 为不正确url, 包路径不正确以及文件不正确
    	$path = array('/test12/20151210_202638_862.jpg','/test/20151210.jpg');
    	$folder = "../Test/download/";
    	$filename = '123.jpg';
    	foreach ($path as $value) {
	    	$result = self::$JMFile->download($value, $folder, $filename);
	    	$this->assertInternalType('string', $result);
	    	$result =json_decode($result, TRUE);
	    	$this->assertInternalType('array', $result);
	    	$this->assertArrayHasKey('code', $result);
	    	$this->assertEquals(1005, $result['code']);
	    	$this->assertArrayHasKey('info', $result);
	    	$this->assertEquals("url error,url can not open!", $result['info']);
    	}
    	
    	/**
    	 * 文件夹不正确，由调用api的使用人员来保证，保证有权限，
    	 * 如果文件夹无权限返回code:1005,info:Permission denied, can not open new file path!
    	 */
    	
    }
    
	public function testExist() {
		//正常,存在为1 
		$ids = array('/test/123.jpg', '/test/20151210_202638_862.jpg');
		$result = self::$JMFile->exist($ids);
    	$this->assertInternalType('string', $result);
    	$result =json_decode($result, TRUE);
    	$this->assertInternalType('array', $result);
    	$this->assertArrayHasKey('code', $result);
    	$this->assertEquals(1000, $result['code']);
    	foreach ($ids as $v) {
    		$this->assertArrayHasKey($v, $result);
    		$this->assertEquals(1, $result[$v]);
    	}
    	
    	//正常不存在
    	$ids = array('/test/123456.jpg', '/test2123/20151210_202638_862.jpg');
    	$result = self::$JMFile->exist($ids);
    	$this->assertInternalType('string', $result);
    	$result =json_decode($result, TRUE);
    	$this->assertInternalType('array', $result);
    	$this->assertArrayHasKey('code', $result);
    	$this->assertEquals(1000, $result['code']);
    	foreach ($ids as $v) {
    		$this->assertArrayHasKey($v, $result);
    		$this->assertEquals(0, $result[$v]);
    	}
    	
    	//ids 异常输入 
    	$tmpArray = array_pad(array(), 101, 0);//长度101越界
    	$ids = array(null, '', array(), $tmpArray); 
    	foreach ($ids as $value) {
    		$result = self::$JMFile->exist($value);
	    	$this->assertInternalType('string', $result);
	    	$result =json_decode($result, TRUE);
	    	$this->assertInternalType('array', $result);
	    	$this->assertArrayHasKey('code', $result);
	    	$this->assertEquals(1005, $result['code']);
	    	$this->assertArrayHasKey('info', $result);
	    	$this->assertEquals("ids is array, can not empty and  not exceed 100!", $result['info']);
    	}
				
	}

	
	public function testRename() {
		//正常，改名成功或者不成功
    	$oldName = '/test/xiaoxin8.jpg';
    	$newName = '/test/xiaoxin88.jpg';
    	$result = self::$JMFile->rename($oldName, $newName);
		$this->assertInternalType('string', $result);
		$result =json_decode($result, TRUE);
		$this->assertInternalType('array', $result);
		$this->assertNotSame(null, $result);
		$this->assertArrayHasKey('code', $result);
		if ($result['code'] == 1000) {
			$this->assertArrayHasKey('paths', $result);
		}else if ($result['code'] == 2010){
			$this->assertArrayHasKey('info', $result);
			$this->assertEquals("new file name already exists or old file not exists", $result['info']);
		}else {
			//没有其他情况，要不改名成功要不改名不成功
		}

		//旧名字为空，或新名字为空异常情况
		$oldName = array(null, '', '   ');
		$newName = array(null, '', '   ');				
		foreach ($oldName as $value) {
			foreach ($newName as $v) {
				$result = self::$JMFile->rename($value, $v);
				$this->assertInternalType('string', $result);
				$result =json_decode($result, TRUE);
				$this->assertInternalType('array', $result);
				$this->assertArrayHasKey('code', $result);
				$this->assertEquals(1005, $result['code']);
				$this->assertArrayHasKey('info', $result);
				$this->assertEquals("old file name or new file name  can not empty!", $result['info']);
			}
		}

	}
    

	
	public function testCopy() {
		
		//正常copy , 非强制性force为0, 或不写
		$moveFiles = array(
				array(
						'path' => '/test',
						'file_name' => '444.jpg',
						'source' => '/test/123.jpg',
						'del_source' => 0
				),
				array(
						'path' => '/test',
						'file_name' => '333.jpg',
						'source' => '/test/123.jpg',
						'del_source' => 0
				),
		);
		$force=0;
    	$result = self::$JMFile->copy( $moveFiles, $force);
		$this->assertInternalType('string', $result);
		$result =json_decode($result, TRUE);
		$this->assertInternalType('array', $result);
		$this->assertNotSame(null, $result);
		$this->assertArrayHasKey('code', $result);
		$this->assertEquals(1000, $result['code']);
		foreach ($moveFiles as $value) {
			//返回值中存在改名文字
			$this->assertArrayHasKey('/test/'.$value['file_name'], $result);					
		}
		
		// moveFile 为空
		$moveFiles = array( array(), '', null, 'aaa');
		$force=0;
		foreach ($moveFiles as $value) {
			$result = self::$JMFile->copy( $value, $force);
			$this->assertInternalType('string', $result);
			$result =json_decode($result, TRUE);
			$this->assertInternalType('array', $result);
			$this->assertNotSame(null, $result);
			$this->assertArrayHasKey('code', $result);
			$this->assertEquals(1005, $result['code']);
			$this->assertArrayHasKey('info', $result);
			$this->assertEquals("moveFiles  can not empty and must be array!", $result['info']);	
			
		}
		
		
		//$moveFiles json 格式不正确
		$moveFiles = array(
				array(
						'path' => '/test',
						'file_name' => '333.jpg',	
				),
				213,
				'aaa'
				
				
		);
		$force=0;
		$result = self::$JMFile->copy( $moveFiles, $force);
		$this->assertInternalType('string', $result);
		$result =json_decode($result, TRUE);
		$this->assertInternalType('array', $result);
		$this->assertNotSame(null, $result);
		$this->assertArrayHasKey('code', $result);
		$this->assertEquals(2011, $result['code']);
		$this->assertArrayHasKey('info', $result);
		//错误信息种类比较多,根据错误信息修改
		
		//正常copy , 强制性force为1
		$moveFiles = array(
				array(
						'path' => '/test',
						'file_name' => '444.jpg',
						'source' => '/test/123.jpg',
						'del_source' => 0
				),
				array(
						'path' => '/test',
						'file_name' => '333.jpg',
						'source' => '/test/123.jpg',
						'del_source' => 0
				),
		);
		$force=1;
		$result = self::$JMFile->copy( $moveFiles, $force);
		$this->assertInternalType('string', $result);
		$result =json_decode($result, TRUE);
		$this->assertInternalType('array', $result);
		$this->assertNotSame(null, $result);
		$this->assertArrayHasKey('code', $result);
		$this->assertEquals(1000, $result['code']);
		foreach ($moveFiles as $value) {
			//返回值中存在改名文字,强制的必定成功为1
			$this->assertArrayHasKey('/test/'.$value['file_name'], $result);
			$this->assertEquals(1, $result['/test/'.$value['file_name']]);
		}

	}
	
	
}