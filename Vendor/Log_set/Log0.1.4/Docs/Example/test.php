<?php
require '../../Handler.php';
require 'Config/Log.php';

\Log\Handler::config((array)new \Config\Log);
$h = \Log\Handler::instance('testLogFile');
$h->log("abcd");
