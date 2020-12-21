#聚美文件系统客户端

##历史版本
### 1.0.0   
聚美文件系统客户端调用server客户端实现以下功能：

#### 1.上传功能
参数：
$uploadFilePath 上传文件的路径
$path 以/开头的一串路径标识符，此路径由用户预先在管理系统中申请
$fileName 文件名，必须指定，如果在$path中已经存在该文件名则覆盖

#### 2.下载功能
读取文件请直接使用管理系统提供接口直接读取，如需下载请使用下载功能

参数：
$path 以/开头的一串路径标识符，此路径由用户预先在管理系统中申请
$folder 用户下载文件夹路径, 用户需拥有该文件夹权限
$fileName 可选参数，如果为填入将使用原文件名

注意：如果给定文件名在文件夹中已存在，需有具有权限才能进行覆盖操作，
失败信息：code:1005, info:Permission denied, can not open new file path!
（/Test/ 文件夹下测试调用后，用户为www-data, 会成phpunit 测试没有权限写入，phpunit权限为本机用户）

#### 3.检测文件功能
根据给定文件ids 检测在管理系统中是否存在

参数：
$ids　文件ids, 数组，可以一次性指定多个，为申请路径path加文件名


#### 4.文件重命名功能
$oldFile　旧文件名，申请路径path加文件名
$newFile  新文件名


#### 5. 批量复制或剪切
$moveFiles　批量文件的数组, 该数组参数必须有path, file_name, source, del_source, , 否则管理不会进行复制或剪切，根据返回信息检查修改
$force 指定该方法是否为强制性执行，0 为普通复制或剪切，1为强制复制或剪切

参数配置例子
<pre>
	array(
		'path' => '/test',
		'file_name' => '444.jpg',
		'source' => '/test/123.jpg',
		'del_source' => 0
	)
</pre>


### 配置文件
配置文件配置用户名，密码，上传url(baseUrl)，下载文件url(exportUrl) 


