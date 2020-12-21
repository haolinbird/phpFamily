<?php
require __DIR__.'/../../Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/')->init();

class Test implements Provider\IUserService\IUserServiceIf{
    public function followUser($user_id, $attetion_uid){
        $t = new Provider\IUserService\ResultModel();
        var_dump($t);
    }
}

$tc = new Test();
$tc->followUser();