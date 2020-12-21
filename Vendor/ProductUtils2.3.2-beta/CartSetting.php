<?php
/**
 * 购物车类.
 *
 * @author quans<quans@jumei.com>
 */

namespace ProductUtils;

/**
 * 购物车逻辑.
 */
class CartSetting
{

    private static $instance;

    /**
     * 购物车前缀Key.
     */
    // const DEFAULT_KEY_PREFIX = 'Shopping_Cart_Data_';
    const DEFAULT_KEY_PREFIX = 'Cart_';

    /**
     * 库存中间key.
     */
    const SKU_NO_KEY_PREFIX = 'Sku_';

    /**
     * HashTable和混合模式下,商城jdsr/jmsr/jdmsr的前缀.
     */
    const JDSR_PREFIX = 'sr_';

    /**
     * 混合模式下,基础数据key.
     */
    const BASE_DATA_IN_MIX_MODEL = 'data';

    /**
     * String模式下关系数据Key.
     */
    const JDSR_DATA_IN_STRING_MODEL = 'sr';

    /**
     * 购物车在redis中存储的数据格式.
     */
    const MODEL_STORE_TYPE_MIX = 'mix';
    const MODEL_STORE_TYPE_STRING = 'string';
    const MODEL_STORE_TYPE_HASH_TABLE = 'hash_table';
    const MODEL_STORE_TYPE_PROTOC = 'protoc';

    // 购物车错误CODE.
    const FAILED_NO_PRODUCT = 1; // 商品不存在.
    const FAILED_ERROR_PRODUCT = 2; // 商品信息有误.
    const FAILED_PRODUCT_NOT_START = 3; // 商品未开售.
    const FAILED_PRODUCT_IS_ENDED = 4; // 商品已结束售卖.(包含时间和状态结束售卖）
    const FAILED_PRODUCT_SITE_ERROR = 5; // 售卖站点错误.
    const FAILED_PRODUCT_PLATFORM_ERROR = 6; // 售卖平台错误.
    const FAILED_PRODUCT_STOCKS_NOT_ENOUGH = 7; // 库存不足.
    const FAILED_PRODUCT_PRE_SALE_NOT_USED = 8; // 商品不支持预售.
    const FAILED_PRODUCT_PRE_SALE_NOT_START = 9; // 商品预售未开始.
    const FAILED_PRODUCT_PRE_SALE_IS_NED = 10; // 商品预售已结束.
    const FAILED_PRODUCT_PRE_SALE_PRICE_WRONG = 11; // 商品预售金额有误.
    const FAILED_PRODUCT_MEMBER_PURCHASE_LIMIT = 12; // 会员限购.
    const FAILED_PRODUCT_IS_NOT_NEW_COMBINATION = 13; // 新组合购
    
    /**
     * 商品错误code,对于信息.
     * 
     * @var array 
     */
    public static $addInCartErrorMsg = array(
        self::FAILED_NO_PRODUCT => '商品不存在',
        self::FAILED_ERROR_PRODUCT => '商品信息有误',
        self::FAILED_PRODUCT_NOT_START => '商品未开售',
        self::FAILED_PRODUCT_IS_ENDED => '商品已结束售卖',
        self::FAILED_PRODUCT_SITE_ERROR => '售卖站点错误',
        self::FAILED_PRODUCT_PLATFORM_ERROR => '售卖平台错误',
        self::FAILED_PRODUCT_STOCKS_NOT_ENOUGH => '库存不足',
        self::FAILED_PRODUCT_PRE_SALE_NOT_USED => '商品不支持预售',
        self::FAILED_PRODUCT_PRE_SALE_NOT_START => '商品预售未开始',
        self::FAILED_PRODUCT_PRE_SALE_IS_NED => '商品预售已结束',
        self::FAILED_PRODUCT_PRE_SALE_PRICE_WRONG => '商品预售金额有误',
        self::FAILED_PRODUCT_MEMBER_PURCHASE_LIMIT => '只支持【{replace}】等级用户购买',
        self::FAILED_PRODUCT_IS_NOT_NEW_COMBINATION => '商品库必须是新组合购',
        
    );


    /**
     * Vip Code与Tip的值.
     * @var array
     */
    public static $vipLevelToTip = array(
        1 => 'gold_member_price',
        2 => 'platinum_member_price',
        3 => 'diamond_member_price'
    );


    /**
     * 平台和站点的对应关系.
     * 
     * @var array
     */
    public static $platformToWhere = array(
        '' => 'all',
        'www' => 'www',
        'mobile' => 'app',
        'app_first' => 'app',
        'wap' => 'wap'
    );

    /**
     * Key和平台的关系.
     * 
     * @var array
     */
    public static $keyToPlatform = array(
        'web' => '',
        'mobile' => array(
            '',
            'mobile',
            'app_first'
        )
    );

    /**
     * Rename Key.
     * 
     * @var array
     */
    public static $keyRenamed = array(
        'category' => 'a',
        'short_name' => 'b',
        'brand_id' => 'c',
        'product_id' => 'd',
        'shipping_system_id' => 'e',
        'shipping_system_type' => 'f',
        'shipping_system_name' => 'g',
        'attribute' => 'h',
        'size' => 'i',
        'delivery_fee' => 'j',
        'original_price' => 'k',
        'item_price' => 'l',
        'end_time' => 'm',
        'iwc' => 'n',
        'user_purchase_limit' => 'o',
        'product_tags' => 'p',
        'show_category' => 'q',
        'special_deal_group_name' => 'r',
        'category_v3_3' => 's',
        'category_v3_2' => 't',
        'category_v3_1' => 'u',
        'category_v3_4' => 'v',
        'site' => 'x',
        'media_rebate_ratio' => 'y',
        'real_buyer_number' => 'z',
        'mall_id' => 'A',
        'platform' => 'B',
        'sale_forms' => 'C',
        'member_purchase_limit' => 'D',
        'gift_type' => 'E',
        'sale_type' => 'F',
        'sale_model' => 'G',
        'start_time' => 'H',
        'is_pre_sale' => 'I',
        'deposit' => 'J',
        'payment_end_time' => 'K',
        'payment_start_time' => 'L',
        'sku_category' => 'M',
        'deal_tags' => 'N',
        // 'tax' => 'O',
        'stocks' => 'P',
        'status' => 'Q',
        'hash_id' => 'R',
        'extension_id' => 'S',
        'virtual_goods_id' => 'T',
        'product_type' => 'U',
        'sale_price' => 'V',
        'sale_end_time' => 'W',
        'sale_start_time' => 'X',
        'vip_price_end_time' => 'Y',
        'vip_price_start_time' => 'Z',
        'gold_member_price' => 'aa',
        'platinum_member_price' => 'ab',
        'diamond_member_price' => 'ac',
        'tpi_stocks' => 'ad',
        'mall_price' => 'ae',
        'value_of_tax' => 'af',
        'value_of_goods' => 'ag',
        'name' => 'ah'
    );

    public static $mallKeyRenamed = array(
        'category' => 'a',
        'short_name' => 'b',
        'brand_id' => 'c',
        'product_id' => 'd',
        'shipping_system_id' => 'e',
        'shipping_system_type' => 'f',
        'shipping_system_name' => 'g',
        'original_price' => 'k',
        'iwc' => 'n',
        'product_tags' => 'p',
        'category_v3_3' => 's',
        'category_v3_2' => 't',
        'category_v3_1' => 'u',
        'category_v3_4' => 'v',
        'mall_id' => 'A',
        'sale_type' => 'F',
        'sale_model' => 'G',
        'sku_category' => 'M',
        'status' => 'Q',
        'product_type' => 'U',
        'sale_price' => 'V',
        'sale_end_time' => 'W',
        'sale_start_time' => 'X',
        'vip_price_end_time' => 'Y',
        'vip_price_start_time' => 'Z',
        'gold_member_price' => 'aa',
        'platinum_member_price' => 'ab',
        'diamond_member_price' => 'ac',
        'tpi_stocks' => 'ad',
        'mall_price' => 'ae',
        'value_of_tax' => 'af',
        'value_of_goods' => 'ag'
    );

    /**
     * TPI库存相关字段.
     * 
     * @var array
     */
    public static $tpiKeyRenamed = array(
        'attribute' => 'h',
        'size' => 'i',
        'tpi_stocks' => 'ad',
        'sku_category' => 'M',
        'product_type' => 'U',
        'product_tags' => 'p',
        'mall_price' => 'ae'
    );

    /**
     * 关系数据.
     * 
     * @var array
     */
    public static $sr = array(
        'parent_no' => 'ah',
        'part_no' => 'ai',
        'quantity' => 'aj',
        'is_main' => 'ak',
        'size' => 'i',
        'attribute' => 'h',
        'item_price' => 'l',
        'value_of_goods' => 'ag',
        'tax' => 'O',
        'short_name' => 'b',
        'product_id' => 'd',
        'value_of_tax' => 'af'
    );

    /**
     * 会员设置.
     * 
     * @var array
     */
    public static $memberSetting = array(
        0 => 1,
        1 => 2,
        2 => 4,
        3 => 8,
    );

    /**
     * 获取静态对象.
     *
     * @return RedisHandler
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(static::$instance[$class])) {
            self::$instance[$class] = new self();
        }

        return self::$instance[$class];
    }

    /**
     * 重命名字段.
     * 
     * @param array $data 原始数据.
     * 
     * @return array
     */
    public static function renameKeys($data)
    {
        return self::exchangeKeys($data, self::$keyRenamed);
    }

    /**
     * 将重命名过后的字段转化为为原始KEY.
     * 
     * @param array $data Redis数据.
     * 
     * @return array
     */
    public static function restoreKeys($data)
    {
        $flipKeyNamed = self::doRenamedSetFlip();
        return self::exchangeKeys($data, $flipKeyNamed);
    }

    /**
     * 将数据转化成需要的key.
     * 
     * @param array $data   数据.
     * @param array $keyMap 键值对.
     * 
     * @return array
     */
    public static function exchangeKeys($data, $keyMap)
    {
        $result = array();
        foreach ($data as $k => $v) {
            $key = $k;
            if (isset($keyMap[$k])) {
                $key = $keyMap[$k];
            }
            $result[$key] = $v;
        }

        return $result;
    }

    /**
     * 检查字段重命名是否重复.
     * 
     * @return boolean
     */
    public static function checkRepeated()
    {
        return count(self::$keyRenamed) != count(self::doRenamedSetFlip());
    }

    /**
     * 翻转数组中的数据.
     * 
     * @return array
     */
    public static function doRenamedSetFlip()
    {
        return array_flip(self::$keyRenamed);
    }

    /**
     * 获取缓存key.
     * 
     * @param string $key       主数据Key(hash_id/sku_no).
     * @param string $storeMode 存储类型.
     * 
     * @return string
     */
    public static function getCacheKey($key, $storeMode = '')
    {

        if (!self::checkModel($storeMode)) {
            self::showException('$storeMode must given or value is wrong!');
        }
        return self::getRedisKeyPrefix($storeMode) . $key;
    }

    /**
     * 返回Redis保存key前缀.
     *
     * @param string $storeMode StoreMode.
     *
     * @return string
     */
    public static function getRedisKeyPrefix($storeMode = '')
    {
        return self::DEFAULT_KEY_PREFIX . $storeMode . '_';
    }

    /**
     * 获取Sku数据.
     * 
     * @param string $key       Sku相关缓存.
     * @param string $storeMode 存储类型.
     * 
     * @return string
     */
    public static function getSkuCacheKey($key, $storeMode = '')
    {
        if (!self::checkModel($storeMode)) {
            self::showException('$storeMode must given or value is wrong!');
        }
        return self::getCacheKey(self::SKU_NO_KEY_PREFIX . $key, $storeMode);
    }

    /**
     * 批量获取缓存Key.
     * 
     * @param array  $keys      缓存key.
     * @param string $storeMode 存储类型.
     * 
     * @return array
     */
    public static function batchGetCacheKey($keys, $storeMode = '')
    {
        $result = array();
        foreach ($keys as $k => $v) {
            $result[] = self::getCacheKey($v, $storeMode);
        }

        return $result;
    }

    /**
     * 批量获取TPI缓存Key.
     *
     * @param array  $keys      缓存key.
     * @param string $storeMode 存储类型.
     *
     * @return array
     */
    public static function batchGetSkuCacheKey($keys, $storeMode = '')
    {
        $result = array();
        foreach ($keys as $k => $v) {
            $result[] = self::getSkuCacheKey($v, $storeMode);
        }

        return $result;
    }

    /**
     * 是否可以在限购情况购买deal.
     *
     * @param integer $userLevelCode       会员对应的Code.
     * @param integer $memberPurchaseLimit Deal上得限购.
     *
     * @return boolean
     */
    public static function matchMemberPurchaseLimit($userLevelCode, $memberPurchaseLimit)
    {
        $notMatched = false;
        if (!empty($memberPurchaseLimit) && $userLevelCode && !($userLevelCode & $memberPurchaseLimit)) {
            $notMatched = true;
        }

        return $notMatched;
    }

    /**
     * 价格价格是否有误.
     * 
     * @param string $category     分类.
     * @param string $showCategory Show_category.
     * @param string $saleForms    Sale_forms.
     * @param string $type         类型.
     * @param float  $price        价格.
     * @param float  $deposit      预售订金.
     * 
     * @return boolean
     */
    public static function checkPriceByCond($category, $showCategory, $saleForms, $type, $price, $deposit = 0)
    {
        $priceCheck = (bccomp($price, 0, 2) <= 0);
        // \Model\Util::checkFormat($type, 'inArray', '类型', false, array('contain' => array('deal', 'mall')));
        if ($type == 'mall') {
            return $priceCheck;
        }

        if (\ProductUtils\Deal::isPre($saleForms)) {
            if ((bccomp($deposit, 0, 2) <= 0)) {
                return true;
            }
        }
        if ($category != \ProductUtils\Deal::CATEGORY_REDEEM && $priceCheck) {
            if ($category == \ProductUtils\Deal::CATEGORY_RETAIL_GLOBAL) {
                if ($showCategory != \ProductUtils\Deal::SHOW_CATEGORY_REDEEM && $saleForms != \ProductUtils\Deal::SALE_FORMS_GIFT) {
                    return $priceCheck;
                }
                
            } else {
                return $priceCheck;
            }
        }
        return false;
    }

    /**
     * 获取促销价格.
     * 
     * @param float $originalPrice 价格.
     * @param array $saleInfo      促销相关信息.
     * @param array $params        入参.
     *
     * @return float
     */
    public static function getSalePriceOrOrignal($originalPrice, $saleInfo, $params)
    {

        if (!empty($saleInfo['sale_price']) && $saleInfo['sale_start_time'] <= $params['time'] && $saleInfo['sale_end_time'] >= $params['time']) {
            $originalPrice = $saleInfo['sale_price'];
        }

        return $originalPrice;
    }

    /**
     * 获取VIP价格.
     * 
     * @param array   $vipInfo  Vip价格信息.
     * @param float   $original 价格.
     * @param array   $params   入参.
     * @param integer $saleType SaleType.
     * 
     * @return float
     */
    public static function getVipPriceOrOrignal($vipInfo, $original, $params, $saleType)
    {

        if ($params['userLevel'] <= 0) {
            return $original;
        }

        if (!\ProductUtils\SaleType::isDomestic($saleType)) {
            return $original;
        }

        if (!isset(self::$vipLevelToTip[$params['userLevel']])) {
            return $original;
        }

        $vipSTime = strtotime($vipInfo['vip_price_start_time']);
        $vipETime = strtotime($vipInfo['vip_price_end_time']);
        if ($vipSTime <= $params['time'] && $vipETime >= $params['time']) {
            return $vipInfo[self::$vipLevelToTip[$params['userLevel']]];
        }

        return $original;
    }

    /**
     * 返回默认字段.
     * 
     * @return array
     */
    public static function setDefaultForGoodsInfoOfCart()
    {
        
        return array(
            'category' => '',
            'short_name' => '',
            'brand_id' => '',
            'product_id' => '',
            'shipping_system_id' => 0,
            'shipping_system_type' => '',
            'shipping_system_name' => '',
            'attribute' => '',
            'size' => '',
            'delivery_fee' => '',
            'original_price' => '',
            'item_price' => '',
            'end_time' => 0,
            'deal_sellable_num' => 0,
            'sellable_num' => 0,
            'user_purchase_limit' => 0,
            'product_tags' => '',
            'show_category' => '',
            'special_deal_group_name' => '',
            'category_v3_3' => '',
            'category_v3_2' => '',
            'category_v3_1' => '',
            'category_v3_4' => '',
            'product_type' => '',
            'site' => '',
            'media_rebate_ratio' => '',
            'mall_id' => '',
            'platform' => 'all',
            'sale_forms' => '',
            'member_purchase_limit' => 0,
            'gift_type' => '',
            'sale_type' => \ProductUtils\Deal::BASE_CODE,
            'sale_model' => '',
        );
    }

    /**
     * 获取返回平台.
     * 
     * @param string $platform  数据库中的平台.
     * @param string $saleForms Sale_forms.
     * 
     * @return array
     */
    public static function getReturnPlatform($platform, $saleForms)
    {
        $returnPlatform = '';
        if (isset(\ProductUtils\CartSetting::$platformToWhere[$platform])) {
            $returnPlatform = self::$platformToWhere[$platform];
        } else {
            $returnPlatform = self::$platformToWhere[''];
        }

        $wapYes = false;
        if ($returnPlatform == 'app' && \ProductUtils\Deal::isYQT($saleForms)) {
            $wapYes = true;
        }

        if ($wapYes) {
            $returnPlatform .= ",wap";
        }

        return $returnPlatform;
    }

    /**
     * 获取redis存储类型.
     * 
     * @return string
     */
    public static function getReadRedisStoreType()
    {
        return self::MODEL_STORE_TYPE_STRING;
    }

    /**
     * 获取redis存储类型.
     * 
     * @return string
     */
    public static function getWriteRedisStoreType()
    {
        if (defined('\Config\Config::CART_REDIS_WRITE_CACHE_TYPE')) {
            return \Config\Config::CART_REDIS_WRITE_CACHE_TYPE;
        }

        return self::MODEL_STORE_TYPE_STRING;
    }

    /**
     * 混合模式.
     * 
     * @param string $type 类型.
     * 
     * @return boolean
     */
    public static function isMixModel($type)
    {
        return $type === self::MODEL_STORE_TYPE_MIX;
    }

    /**
     * Key/Value模式.
     * 
     * @param string $type 类型.
     * 
     * @return boolean
     */
    public static function isStringModel($type)
    {
        return $type === self::MODEL_STORE_TYPE_STRING;
    }

    /**
     * HashTable模式.
     * 
     * @param string $type 类型.
     * 
     * @return boolean
     */
    public static function isHashTableModel($type)
    {
        return $type === self::MODEL_STORE_TYPE_HASH_TABLE;
    }

    /**
     * Protoc压缩模式.
     *
     * @param string $type 类型.
     *
     * @return boolean
     */
    public static function isProtocModel($type)
    {
        return $type === self::MODEL_STORE_TYPE_PROTOC;
    }

    /**
     * 检查是否是正确的存储类型.
     * 
     * @param string $type 类型.
     * 
     * @return boolean
     */
    public static function checkModel($type)
    {
        return self::isMixModel($type) || self::isStringModel($type) || self::isHashTableModel($type) || self::isProtocModel($type);
    }

    /**
     * 异常数据.
     * 
     * @param string $message 异常信息.
     * 
     * @return void
     *
     * @throws \Exception 抛出异常.
     */
    public static function showException($message)
    {
        $e = new \Exception($message);
        throw $e;
    }

    /**
     * 获取protoc压缩数据.
     *
     * @param array  $data     原始数据.
     * @param string $dataFrom 数据来源D/T.
     *
     * @return array
     */
    public function batchGetProtocData($data, $dataFrom)
    {

        $result = array();
        if ($dataFrom == 'T') {
            foreach ($data as $k => $v) {
                $key = \ProductUtils\CartSetting::getSkuCacheKey($k, \ProductUtils\CartSetting::getReadRedisStoreType());
                $result[$key] = $this->getTpiProtocData($data);
            }
            return $result;
        }

        foreach ($data as $k => $v) {

            $key = \ProductUtils\CartSetting::getCacheKey($k, \ProductUtils\CartSetting::getReadRedisStoreType());
            if (\ProductUtils\SaleType::isMall($v['sale_type'])) {
                $result[$key] = $this->getMallProtocData($v);
            } else {
                $result[$key] = $this->getDealProtocData($v);
            }
        }
        return $result;
    }

    /**
     * 获取TPI压缩数据.
     *
     * @param array $data 需要压缩的TPI数据.
     *
     * @return Cart\Tpi
     */
    public function getTpiProtocData($data)
    {
        $tpi = new \ProductUtils\Cart\Tpi();
        return $this->getProtocString($tpi, $data);
    }

    /**
     * 获取商城压缩数据.
     *
     * @param array $data 需要压缩的商城数据.
     *
     * @return Cart\Mall
     */
    public function getMallProtocData($data)
    {
        $mall = new \ProductUtils\Cart\Mall();
        return $this->getProtocString($mall, $data);
    }

    /**
     * 获取特卖压缩数据.
     *
     * @param array $data 需要压缩的特卖数据.
     *
     * @return mixed
     */
    public function getDealProtocData($data)
    {
        $deal = new \ProductUtils\Cart\Deal();
        return $this->getProtocString($deal, $data);
    }

    /**
     * 通过protoc对象和数据获取压缩过后的数据.
     *
     * @param Object $protocObj Protoc对象.
     * @param array  $data      需要压缩的数据.
     *
     * @return mixed
     */
    public function getProtocString($protocObj, $data)
    {
        foreach ($data as $k => $v) {
            $func = \ProductUtils\ProtocHandler::getProtocSetFunc($k);
            $one = is_array($data[$k]) ? json_encode($data[$k]) : (string)$data[$k];
            $protocObj->$func($one);
        }
        return $protocObj->serializeToString();
    }

    /**
     * 返回指定结构的deal标签格式.
     *
     * @param string $dealTags 标签json格式.
     *
     * @return array
     */
    public static function getDealTagAndSmallChange($dealTags)
    {
        $return = array();
        if (!empty($dealTags)) {
            $dealTags = json_decode($dealTags, true);
            if (is_array($dealTags)) {
                $return = $dealTags;
                if (isset($dealTags['userLimit'])) {
                    foreach ($dealTags['userLimit'] as $k => $vals) {
                        $return['userLimit'][$k] = array_shift($vals);
                    }
                }
            }
        }

        return $return;
    }

}
