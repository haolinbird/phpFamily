<?php

namespace JMFile;
/**
 * 聚美文件系统客户端
 * @author zhangjin
 *
 */
class JMFile {
	protected static $instances = array ();
	protected static $configs = array ();
	protected $cfg = array();

	const MAX_CONNECTION_TIME = 1.5;
	
	/**
	 * 设置客户端所需的配置。
	 *
	 * @param array $configs        	
	 */
	public static function config(array $configs) {
		static::$configs = $configs;
	}
	
	/**
	 * get an instance with the specified config
	 *
	 * @param string $endpoint cfg name
	 * @return \JMFile\JMFile
	 */
	public static function instance($endpoint = 'default') {
		if (! isset ( self::$instances [$endpoint] )) {
			self::$instances [$endpoint] = new self ( $endpoint );
		}
		return self::$instances [$endpoint];
	}
	
	/**
	 * return the current configurations
	 */
	public function getCfg() {
		return $this->cfg;
	}
	
	
	protected function __construct($endpoint = 'default') {	
		if(!static::$configs) {
			static::$configs = (array) new \Config\JMFile;
		}	
		if(!isset(static::$configs[$endpoint])) {
			throw new \Exception('JMFile "'.$endpoint.'" not configured!');
		}else {			
				$this->cfg = static::$configs[$endpoint];
		}		
	}
	
	
	/**
	 * 上传图片
	 *
	 * @param string $uploadFilePath 上传文件的具体路径
	 * @param string $path 用户指定的图片存储路径，以/开头的一串路径标识符，此路径由用户预先在用户管理系统中申请
	 * @param string $fileName 图片文件名
	 * @return  string 
	 */
	public function upload($uploadFilePath, $path, $fileName) {
		
		if (! is_file ( $uploadFilePath )) {
			$result = json_encode(array ( "code" => "1005", "info" => "can not find file, error path!" ));
			return $result;
		}		
		if (empty($path)) {
			$result = json_encode(array ( "code" => "1005", "info" => "path can not empty!"  ));
			return $result;
		}		
		if (empty($fileName) || trim($fileName)=='' ) {
			$result = json_encode(array ( "code" => "1005", "info" => "fileName can not empty!"  ));
			return $result;
		}

		$data = array ( 
				'fileName' => $fileName, 
				'user' => $this->cfg['user'], 
				'password' => $this->cfg['password'], 
				'path' => $path  
		);
		$data = json_encode ( $data );		
		// 上传文件需要在前面加＠
		$post = array ('imagefile' => class_exists('CURLFile', false) ? new \CURLFile($uploadFilePath) : '@' . $uploadFilePath, 'configfile' => $data );	
		$url = $this->cfg['baseUrl'].'/upload';
		
		$options = array (
				CURLOPT_URL => $url,
				CURLOPT_POSTFIELDS => $post,
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_TIMEOUT => 28,
				CURLOPT_CONNECTTIMEOUT => $this::MAX_CONNECTION_TIME
		);
	
		$result = $this->curlExec ($options);
		return $result;
	}
	
	/**
	 * 读取文件，实际为下载文件功能
	 *
	 * @param string $path 文件系统中图片所在路径
	 * @param string $folder 文件存储Url
	 * @param string $fileName 文件名字,默认为原名称
	 * @return string
	 */
	public function download($path, $folder, $fileName = "") {
		
		$result = json_encode(array ( "code" => 1000, "info" => "success!"));
		if (empty( $path ) || trim ( $path ) == '') {
			$result = json_encode(array ( "code" => 1005, "info" => "path error, can not empty!" ));
			return $result;
		}
		
		if (empty($folder) || !is_dir ( $folder )) {
			$result = json_encode(array ( "code" => 1005, "info" => "Folder does not exist!" ));
			return $result;
		}
		
		//未加文件前每加 '/' 
		$path = trim($path);
		if (!preg_match('/^\//', $path)) {
			$path = '/'.$path; 
		}
		//folder 末尾为加'/'
		$folder= trim($folder);
		if (!preg_match('/\/$/', $folder)) {
			$folder = $folder.'/';
		}		
		// 取得文件名
		$fileName = trim($fileName);
		if (empty($fileName)) {
			$fileName = basename ( $path );
		}
		
		$url = $this->cfg['exportUrl'].$path;
		$file = @fopen ( $url, "rb" );
		if ($file) {
			$newFile = @fopen ( $folder . $fileName, "wb" );
			if ($newFile) {
				while ( ! feof ( $file ) ) {
					// reset time limit for big files
					set_time_limit ( 0 );
					fwrite ( $newFile, fread ( $file, 1024 * 8 ), 1024 * 8 );
					flush ();
				}
				fclose ($newFile);//如果为资源用完后关闭
			}else {
				$result = json_encode(array ( "code" => 1005, "info" => "Permission denied, can not open new file path!" ));
			}
			fclose ( $file );
		}else {
			$result = json_encode(array ( "code" => 1005, "info" => "url error,url can not open!" ));
		}		
		
		return $result ;
	}
	
	/**
	 * 检测文件
	 * @param string $ids 检测文件Id
	 * @return string
	 */
	public function exist($ids) {
		// ids不能为空
		if (empty($ids) || !is_array($ids) || count($ids) > 100) {
			$result = json_encode(array ( "code" => "1005", "info" => "ids is array, can not empty and  not exceed 100!"  ));
			return $result;
		}
			
		$post = 'ids='.json_encode($ids);
		$url = $this->cfg['baseUrl'].'/image_exist';		
		$options = array (
				CURLOPT_URL => $url,				
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,					
				CURLOPT_POSTFIELDS => $post,
				CURLOPT_POST=> 1,
				CURLOPT_TIMEOUT => 28,
				CURLOPT_CONNECTTIMEOUT => $this::MAX_CONNECTION_TIME,				
		);
	
		$result = $this->curlExec ($options);
		return $result;
	}
	
	/**
	 * 文件重命名
	 * 
	 * @param string $oldFile 旧文件,上传raw返回路径＋文件名
	 * @param string $newFile 新文件
	 * @return string
	 */
	public function rename( $oldFile, $newFile) {
		$oldFile = trim($oldFile);
		$newFile = trim($newFile);
		if (empty($oldFile ) || empty($newFile) ) {
			$result = json_encode(array ( "code" => "1005", "info" => "old file name or new file name  can not empty!"  ));
			return $result;
		}
		
		$post = 'user='.$this->cfg['user'].'&password='. $this->cfg['password'].'&old_file='.$oldFile.'&new_file='.$newFile;	
		$url = $this->cfg['baseUrl'].'/image_rename';
		$options = array (
				CURLOPT_URL => $url,				
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,					
				CURLOPT_POSTFIELDS => $post,
				CURLOPT_POST=> 1,
				CURLOPT_TIMEOUT => 28,
				CURLOPT_CONNECTTIMEOUT => $this::MAX_CONNECTION_TIME,				
		);
		
		$result = $this->curlExec ($options );
		return $result;
	}
	
	/**
	 * 批量复制或剪切
	 * 
	 * @param string $moveFiles 批量上传文件的数组
	 * @param number $force 0 为普通复制，1为强制复制
	 * @return string
	 */
	public function copy($moveFiles, $force=0) {

		if (empty($moveFiles) || !is_array($moveFiles)) {
			$result = json_encode(array ( "code" => "1005", "info" => "moveFiles  can not empty and must be array!"  ));
			return $result;
		}
						
		$config = array('move_files' => $moveFiles);
		$post = 'user='.$this->cfg['user'].'&password='. $this->cfg['password'].'&config='.json_encode($config);
		if ($force == 0) {
			$url = $this->cfg['baseUrl'].'/image_batch_copy';
		}else {
			$url = $this->cfg['baseUrl'].'/image_batch_cover';
		}		
		$options = array (
				CURLOPT_URL => $url,				
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,					
				CURLOPT_POSTFIELDS => $post,
				CURLOPT_POST=> 1,
				CURLOPT_TIMEOUT => 28,
				CURLOPT_CONNECTTIMEOUT => $this::MAX_CONNECTION_TIME,				
		);
			
		$result = $this->curlExec ($options);
		return $result;
	}
	
	/**
	 * 调用curl
	 *
	 * @param array $options curl 参数数组     	
	 * @return string json
	 */
	private function curlExec($options) {
		$ch = curl_init ();		
		curl_setopt_array ( $ch, $options );
		// 返回json string
		$result = curl_exec ( $ch );
		// 检查是否有错误发生
		if (curl_errno ( $ch )) {
			$error = "curl exec error! " . curl_error ( $ch );
			$result = json_encode(array ( "code" => "1005", "info" => $error ));
		}
		curl_close ( $ch );
		return $result;
	}

}



