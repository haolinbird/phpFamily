<?php
/**
 * 云账户配置文件示例.
 *
 * @author qiangd <qiangd@jumei.com>
 */

namespace Config;

/**
 * Create at 2019年12月5日 by qiangd <qiangd@jumei.com>.
 */
class YunZhangHu
{

    /**
     * API的host.
     *
     * @var string
     */
    const API_HOST = "#{shuabao-service.yunzhanghu.api_host}";

    /**
     * Des3加密key.
     *
     * @var string
     */
    const DES3_KEY = "#{shuabao-service.yunzhanghu.des_key}";

    const APP_KEY = "#{shuabao-service.yunzhanghu.app_key}";

    const DEALER_ID = "#{shuabao-service.yunzhanghu.dealer_id}";

}
