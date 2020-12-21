#!/usr/bin/env php
<?php
$bootstrap = $argv[1];
$provider_dir = $argv[2];
$handler_dir = $argv[3];
$address = $argv[4];

function scanDirectories($rootDir, $allData=array()) {
    // set filenames invisible if you want
    $invisibleFileNames = array(".", "..", ".htaccess", ".htpasswd");
    // run through content of root directory
    $dirContent = scandir($rootDir);
    foreach($dirContent as $key => $content) {
        // filter all files not accessible
        $path = $rootDir.'/'.$content;
        if(!in_array($content, $invisibleFileNames)) {
            // if content is file & readable, add to array
            if(is_dir($path) && is_readable($path)) {
                // recursive callback to open new directory
                $allData[] = $path;
                $allData = scanDirectories($path, $allData);
            }
        }
    }
    return $allData;
}
if(!empty($bootstrap) && is_file($bootstrap)) require $bootstrap;
if(!empty($provider_dir)){
    $thriftServiceArray = array();
    $client_config = array();
    $provider_dir_length = count(explode(DIRECTORY_SEPARATOR, realpath($provider_dir)));   
    $allProviderDirs = scanDirectories($provider_dir);
    $allProviderDirs[] = $provider_dir;
    foreach ($allProviderDirs as $dir)
    {
        $tmp_arr = explode("/", $dir);
        array_splice($tmp_arr, 0, $provider_dir_length);
        if(count($tmp_arr) < 1){
            continue;
        }
        $service_name = implode('\\', $tmp_arr);

        $path_array = explode('/', $handler_dir);
        $handlerNamespace = $handlerNamespace = $path_array[count($path_array)-1];

        if($handlerNamespace == 'Provider')
        {
            $class_name = "\\$handlerNamespace\\$service_name\\{$service_name}Handler";
            $handler_file = $handler_dir.'/'.$service_name.'/'.str_replace('\\', DIRECTORY_SEPARATOR, $service_name).'Handler.php';
        }
        else
        {
            $class_name = "\\$handlerNamespace\\{$service_name}";
            $handler_file = $handler_dir.'/'.str_replace('\\', DIRECTORY_SEPARATOR, $service_name).'.php';
        }

        if(class_exists($class_name))
        {
            $re = new ReflectionClass($class_name);
            $method_array = $re->getMethods(ReflectionMethod::IS_PUBLIC);
            $thriftServiceArray[$service_name] = array();
            foreach($method_array as $m)
            {
                $params_arr = $m->getParameters();
                $method_name = $m->name;
                $params = array();
                foreach($params_arr as $p)
                {
                    $params[] = $p->name;
                }
                $thriftServiceArray[$service_name][$method_name] = $params;
            }
            $client_config[$service_name] = array(
                'nodes' =>array(
                    $address
                ),
                'provider'  => $provider_dir,
            );
        }
    }
    echo json_encode(array(
        'config' => $client_config,
        'services' => $thriftServiceArray
    ));
}


