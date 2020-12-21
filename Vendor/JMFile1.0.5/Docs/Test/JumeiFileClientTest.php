<?php
//init event client
require __DIR__.'/../../Vendor/Bootstrap/Autoloader.php';
use \JMFile\JMFile as  JMF;
\Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../Examples/')->addRoot(__DIR__.'/../../../')->init();


try{
	$file_name_with_full_path = realpath('./123.jpg');
	$JMFile = JMF::instance();
	
	//upload　　 '{"code":1000,"paths":{"raw":"/test/123.jpg"}}'
// 	$result = $JMFile->upload($file_name_with_full_path,  '/test', '123.jpg');
	
	
	//download 需要资源的URL,以及要下载到那个文件夹
// 	$url = "/test/20151210_202638_862.jpg";
// 	$folder = "./download/";
// 	$result = $JMFile->download($url, $folder, '123.jpg');
	
	
	//exist ids 为数组，可以写多个
	$ids = array('/test/123.jpg', '/test/20151210_202638_862.jpg');
	$result = $JMFile->exist($ids);

	
// 	//rename
// 	$result = $JMFile->rename('/test/xiaoxin88.jpg', '/test/xiaoxin8.jpg');
	
	//copy 批量复制，所以需要自己传入复制数组, del_source 0为复制，1为剪切, force 0 为普通复制，1为强制复制
// 	$moveFiles = array(
// 			array(
// 					'path' => '/test',
// 					'file_name' => '444.jpg',
// 					'source' => '/test/123.jpg',
// 					'del_source' => 0
// 			),
// 			array(
// 					'path' => '/test',
// 					'file_name' => '333.jpg',
// 					'source' => '/test/123.jpg',
// 					'del_source' => 0
// 			),
// 	);
// 	$result = $JMFile->copy( $moveFiles, $force=0);
	
	
	var_dump($result);

}
catch(\Exception $e)
{
	var_dump($ec->debugInfo());
	echo $e;
}
