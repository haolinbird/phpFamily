<?php
if (in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')))
{
    apc_clear_cache();
    echo json_encode(array('success' => true));
}
else
{
    die('Access denied');
}
