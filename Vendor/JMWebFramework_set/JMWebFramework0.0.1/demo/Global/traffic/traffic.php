<?php

function do_traffic($start_ts, $effects_uri, $url, $level = 2, $ttl = 600, $expire = 1800) {
    if ($level < 1) return;

    $time = time();
    if ($start_ts > $time) return;

    $req = $_SERVER['REQUEST_URI'];
    foreach ($effects_uri as $uri)
        if (strcmp($req, $uri) < 0)
            return;

    /**
     * 1. 用户首次进入，进行排队判断，有 level 的几率进入排队
     * 2. 再次回来，判断 tfo 是否过期，过期则进入，否则弹回继续排队
     */
    define('CK_TFO', 'tfo');

    $x_real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '';
    if ($x_real_ip) {
        $ips = explode(',', $x_real_ip);
        $client_ip = trim($ips[0]);
    } else {
        $client_ip = $_SERVER['REMOTE_ADDR'];
    }
    $client_ip = substr($client_ip, 0, strrpos($client_ip, '.'));

    // 新用户
    if (!isset($_COOKIE[CK_TFO])) {
        global $_TF_WHITE_IP_LIST;

        $rand = mt_rand(0, 100);
        if (($rand < $level) && !isset($_TF_WHITE_IP_LIST[$client_ip])) {
            $exp = $time + $ttl + $rand;
            setcookie(CK_TFO, $exp, $exp + $expire);
            header('X-TIME: H;'. date('YmdHis', $exp+$expire));
            header('Location: ' . $url);
            die();
        } else {
            header('X-TIME: T;'. date('YmdHis', $time+$expire));
            setcookie(CK_TFO, $time, $time + $expire);
        }
    } else {
        $tfo = (int)$_COOKIE[CK_TFO];
        if ($tfo > $time) {
            header('X-TIME: R;'. date('YmdHis', $time));
            header('Location: ' . $url);
            die();
        }
    }

} // do_traffic

if (file_exists(__DIR__ . '/includes/config_traffic.inc.php')) {
    include __DIR__ . '/includes/config_traffic.inc.php';
    do_traffic($_TF_START_TS, $_TF_EFFECTS_URI, $_TF_REDIRECT_TO, $_TF_LEVEL, $_TF_TTL, $_TF_EXPIRE_TS);
}
