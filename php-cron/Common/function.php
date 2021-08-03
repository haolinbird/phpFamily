<?php

function sig_handler($sig)
{
    global $curChildPro;
    switch ($sig) {
        case SIGCHLD:
            echo 'SIGCHLD', PHP_EOL;
            $curChildPro--;
            break;
        case SIGTERM:
            echo 'SIGTERM', PHP_EOL;
            $curChildPro--;
            break;
        case SIGHUP:
            echo 'SIGHUP', PHP_EOL;
            $curChildPro--;
            break;

    }
}