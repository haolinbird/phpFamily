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
class Cart
{

    private static $instance;

    /**
     * 获取静态对象.
     * 
     * @return Cart
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(static::$instance[$class])) {
            self::$instance[$class] = new Cart();
        }
        return self::$instance[$class];
    }

    /**
     * 购物车临时接口只返回deal的start_time.
     *
     * @param array   $params          Params.
     * @param string  $redisConfig     Redis配置.
     * @param string  $phpClientConfig PHPClient的配置.
     * @param boolean $hashSkus        Sku及其对应的库存（min(sku_stocks,td.stocks - td.real_buyer_number)）.
     *
     * @return array
     * @throws \Exception 报错.
     */
    public function getCacheCartInfo(array $params,$redisConfig,$phpClientConfig = null, $hashSkus = false)
    {
        if (empty($params) || count($params) > 120) {
            throw new \Exception('$params count lte 120');
        }

        $data = array();
        if ($params) {
            $keys = array();
            $skuKeys = array();
            foreach ($params as $k => $v) {
                $keys[] = \ProductUtils\CartSetting::getCacheKey($k, \ProductUtils\CartSetting::MODEL_STORE_TYPE_STRING);
                if ($hashSkus) {
                    list($_skuNo,$_hashId) = explode(',',$k);
                    if ($_hashId) {
                        $skuKeys[$_hashId] = \ProductUtils\CartSetting::getSkuCacheKey($_hashId,\ProductUtils\CartSetting::MODEL_STORE_TYPE_STRING);
                    }
                }
            }

            if ($keys) {
                $nocacheHashIds = array();
                // 根据类型不同获取不同数据
                $cacheData = \ProductUtils\RedisHandler::getInstance()->batachGetStringData($keys, $redisConfig);
                $keyPrefix = \ProductUtils\CartSetting::getRedisKeyPrefix(\ProductUtils\CartSetting::MODEL_STORE_TYPE_STRING);
                foreach ($params as $k => $v) {
                    if (!empty($cacheData[$keyPrefix.$k])) {
                        $data[$k] = $cacheData[$keyPrefix.$k];
                    } else {
                        $nocacheHashIds[$k] = $v;
                    }
                }

                // 如果配置PHPCilent 启用懒加载
                if ($phpClientConfig && $nocacheHashIds) {
                    try {
                        $result = \PHPClient\Text::inst($phpClientConfig)->setClass('JumeiProduct_Cart_Write')->setCartInfoToRedisBySkuNos($nocacheHashIds);
                        if ($result && is_array($result)) {
                            $data = $this->arrayMerge($data,$result);
                        }
                    } catch (\Exception $e) {
                        // 懒加载异常,不需要抛出,记录到owl日志中.
                        try {
                            $ex = new \Exception("lazy load data : " . json_encode($nocacheHashIds), $e->getCode(), $e);
                            \MNLogger\EXLogger::instance()->log($ex);
                        } catch (\Exception $e) {
                            // 记录懒加载错误日志也失败,不处理了.
                        }
                    }
                }
            }
        }

        if ($data) {

            $skuMaps = array();
            if ($skuKeys) {
                $cacheSkuData = \ProductUtils\RedisHandler::getInstance()->batachGetStringData($skuKeys, $redisConfig);
                foreach ($skuKeys as $k => $v) {
                    if (isset($cacheSkuData[$v])) {
                        $skuMaps[$k] = json_decode($cacheSkuData[$v],1);
                    }
                }
            }

            foreach ($data as $k => $v) {
                $tmp = json_decode($v,1);
                $tmp['skus'] = array();
                list($_skuNo,$_hashId) = explode(',',$k);
                if (!empty($_hashId) && isset($skuMaps[$_hashId])) {
                    $tmp['skus'] = $skuMaps[$_hashId];
                }
                $data[$k] = $tmp;
            }
        }

        return $data;
    }

    /**
     * 购物车临时接口只返回deal的start_time.
     *
     * @param array  $hashIds         HashIds.
     * @param string $redisConfig     Redis配置.
     * @param string $phpClientConfig PHPClient的配置.
     *
     * @return array
     * @throws \Exception 报错.
     */
    public function getDealInfo(array $hashIds,$redisConfig,$phpClientConfig = null)
    {
        if (empty($hashIds) || count($hashIds) > 120) {
            throw new \Exception('hashIds count lte 120');
        }

        $data = array();
        if ($hashIds) {
            $keys = array();
            foreach ($hashIds as $k => $v) {
                $keys[] = \ProductUtils\CartSetting::getCacheKey($v, \ProductUtils\CartSetting::MODEL_STORE_TYPE_STRING);
            }
            if ($keys) {
                $nocacheHashIds = array();
                // 根据类型不同获取不同数据
                $cacheData = \ProductUtils\RedisHandler::getInstance()->batachGetStringData($keys, $redisConfig);
                $keyPrefix = \ProductUtils\CartSetting::getRedisKeyPrefix(\ProductUtils\CartSetting::MODEL_STORE_TYPE_STRING);
                foreach ($hashIds as $k => $v) {
                    if (!empty($cacheData[$keyPrefix.$v])) {
                        $data[$v] = $cacheData[$keyPrefix.$v];
                    } else {
                        $nocacheHashIds[] = $v;
                    }
                }
                // 如果配置PHPCilent 启用懒加载
                if ($phpClientConfig) {
                    if ($nocacheHashIds) {
                        try {
                            $result = \PHPClient\Text::inst($phpClientConfig)->setClass('JumeiProduct_Cart_Write')->setDealInfoToRedis($nocacheHashIds);
                            if ($result && is_array($result)) {
                                $data = array_merge($data,$result);
                            }
                        } catch (\Exception $e) {
                            // 懒加载异常,不需要抛出,记录到owl日志中.
                            try {
                                $ex = new \Exception("lazy load data : " . json_encode($nocacheHashIds), $e->getCode(), $e);
                                \MNLogger\EXLogger::instance()->log($ex);
                            } catch (\Exception $e) {
                                // 记录懒加载错误日志也失败,不处理了.
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 购物车接口.
     * 
     * @param array   $items           购物项.
     * @param string  $site            站点.
     * @param integer $userLevel       用户等级.
     * @param integer $time            加入购物车时间.
     * @param string  $redisConfig     Redis配置.
     * @param string  $phpClientConfig PHPClient的配置.
     * 
     * @return array
     */
    public function cart($items, $site, $userLevel, $time, $redisConfig, $phpClientConfig = null)
    {
        // 拼接key.
        $keys = array();
        $keyToIndex = array();
        $result = array();
        $stock = array();
        $keyGot = array();
        $return = array();
        $default = array(
            'sku_no' => '',
            'deal_hash_id' => '',
            'code' => 0,
            'msg' => '',
            'extra' => array(),
            'data' => array(),
            'extends' => array('brandIds' => '0', 'saleType' => '0', 'categoryIds' => '0', 'sku' => '', 'sku_category' => '', 'mainSku' => '', 'mainSaleType' => '', 'saleForms' => ''),
        );

        // 获取读取模式.
        $storeType = \ProductUtils\CartSetting::getReadRedisStoreType();
        foreach ($items as $k => $v) {

            if (!isset($v['sku_no'])) {
                $items[$k]['sku_no'] = '';
            }

            if (!isset($v['hash_id'])) {
                $items[$k]['hash_id'] = '';
            }

            $isMall = true;
            if (!empty($items[$k]['hash_id'])) {
                // 特卖
                $index = $items[$k]['hash_id'] . '_' . $items[$k]['sku_no'];
                $isMall = false;
            } elseif (!empty($items[$k]['sku_no'])) {
                // 商城
                $index = $items[$k]['sku_no'];
            }

            // 获取缓存数据,根据配置而来.
            // 混合模式和hashTable模式都是采用hmget; key/value模式采用mget模式.
            $cacheKey = \ProductUtils\CartSetting::getCacheKey($index, $storeType);
            $keyToIndex[$cacheKey][$k] = '';
            $keys[] = $cacheKey;

            $result[$k] = $default;
            $result[$k]['sku_no'] = $items[$k]['sku_no'];
            $result[$k]['deal_hash_id'] = $items[$k]['hash_id'];
            $result[$k]['extends']['sku'] = $items[$k]['sku_no'];
        }

        // 根据类型不同获取不同数据
        $data = \ProductUtils\RedisHandler::getInstance()->batachGetStringData($keys, $redisConfig);

        // 构造配置.
        $params = compact('site', 'userLevel', 'time', 'storeType');

        // 这里把没有获取到的数据从商品库service获取一次.
        if (!empty($phpClientConfig)) {
            try {
                $lazyParam = $this->getLazyParams($items, $keyToIndex, $data, $params);
                $lazyData = $this->getLazyDataByParams($lazyParam, $params, $phpClientConfig);
                $data = self::formatLazyDataAndMergeData($lazyData, $data);
            } catch (\Exception $e) {
                // 懒加载异常,不需要抛出,记录到owl日志中.
                try {
                    $ex = new \Exception("lazy load data : " . json_encode($lazyParam), $e->getCode(), $e);
                    \MNLogger\EXLogger::instance()->log($ex);
                } catch (\Exception $e) {
                    // 记录懒加载错误日志也失败,不处理了.
                }

            }
        }

        $result = self::formatData($result, $keyToIndex, $data, $stock, $params);
        // $result = $this->checkData($result, $params);

        $return['data'] = $result;
        $return['iwc'] = $this->getWarehouse($result);
        return $return;

    }



    /**
     * 格式化数据.
     *
     * @param array $result     返回数据.
     * @param array $keyToIndex 缓存和购物项关系.
     * @param array $data       数据.
     * @param array $stock      库存.
     * @param array $params     入参信息.
     * 
     * @return array
     */
    public function formatData($result, $keyToIndex, $data, $stock, $params)
    {

        // 获取基础参数.
        $baseParams = self::getBaseParams($params);

        foreach ($keyToIndex as $k => $vResultKeys) {
            if (!isset($data[$k])) {
                continue;
            }

            $tmpReturn = \ProductUtils\CartSetting::setDefaultForGoodsInfoOfCart();
            // 转化成正常的key
            // 根据类型,解析数据
            if ($data[$k]) {
                $data[$k] = json_decode($data[$k], true);
            } else {
                $data[$k] = array();
            }
            
            $dataTmp = $data[$k];
            foreach ($vResultKeys as $kResultKey => $v) {

                // 无可用数据
                if (!isset($dataTmp['short_name']) || $dataTmp['short_name'] === false) {
                    $result[$kResultKey]['code'] = \ProductUtils\CartSetting::FAILED_NO_PRODUCT;
                    $result[$kResultKey]['data'] = array();
                    $result[$kResultKey]['extends'] = json_encode($result[$kResultKey]['extends']);
                    continue;
                }

                // 如果存在
                $tmpReturn['sellable_num'] = 1;
                $tmpReturn = array_merge($tmpReturn, $dataTmp);
                // Deal的需要判断deal级别库存.
                if (!\ProductUtils\SaleType::isMall($tmpReturn['sale_type'])) {
                    $tmpReturn['sellable_num'] = max(0, $tmpReturn['stocks'] - $tmpReturn['real_buyer_number']);
                }
                
                $skuNo = $result[$kResultKey]['sku_no'];
                $tmpReturn['iwc'] = self::getIwcFromData($tmpReturn, $baseParams);
                // $tmpReturn['iwc'] = $tmpIwc;

                // 格式化基础信息.
                $tmpReturn = self::formatBaseData($tmpReturn, $params, $baseParams);

                // 设置扩展促销字段.
                $result[$kResultKey]['extends'] = $this->getExtensionData($tmpReturn, $result[$kResultKey]['extends']);

                // 清除多余的字段.
                $tmpReturn = $this->removeUnusedData($tmpReturn, $tmpReturn['sale_type']);

                // 拼接结果数据.
                $result[$kResultKey]['data'] = $tmpReturn;
                $result[$kResultKey]['extends'] = json_encode($result[$kResultKey]['extends']);
            }

        }

        return $result;
    }

    /**
     * 格式化基础数据.
     * 
     * @param array $return     基础数据.
     * @param array $params     基础入参.
     * @param array $baseParams 格式化基础入参.
     * 
     * @return array
     */
    public static function formatBaseData($return, $params, $baseParams)
    {
        // 是否还要求库存.
        if (\ProductUtils\SaleType::isPop($return['sale_type'])) {
            if (\ProductUtils\SaleType::isDeal($return['sale_type'])) {
                $tmpStocks = 0;
                if ($return['end_time'] >= $params['time']) {
                    $tmpStocks = min($return['sellable_num'], $return['tpi_stocks']);
                }
            } else {
                $tmpStocks = $return['tpi_stocks'];
            }
            $return['sellable_num'] = max(0, $tmpStocks);
        }

        $status = \ProductUtils\Deal::$dealOnSaleStatus;
        if (\ProductUtils\SaleType::isMall($return['sale_type'])) {
            $status = \ProductUtils\Deal::$globalMallOnSaleStatus;
            if (\ProductUtils\SaleType::isDomestic($return['sale_type'])) {
                $status = \ProductUtils\Deal::$jumeiMallOnSaleStatus;
            }
        }

        if (\ProductUtils\SaleType::isDomestic($return['sale_type']) && \ProductUtils\SaleType::isMall($return['sale_type'])) {
            $return['status'] = $return['show_status'];
        }

        // 状态的判定
        if (!in_array($return['status'], $status)) {
            // 商品状态不可售.
            $return['sellable_num'] = 0;
        } elseif (!empty($return['sku_no']) && \ProductUtils\Deal::isDisabledStatus($return['is_enable'])) {
            // Sku启用/禁用状态.
            $return['sellable_num'] = 0;
        }

        // 如果未开售强制可售.
        if (\ProductUtils\SaleType::isDeal($return['sale_type'])) {
            if ($return['start_time'] > $params['time']) {
                $return['sellable_num'] = max($return['sellable_num'], 1);
            }
            $return['is_pre_sale'] = $return['is_pre_sale'] == 1 ? true : false;
            $return['deal_tags'] = \ProductUtils\CartSetting::getDealTagAndSmallChange($return['deal_tags']);
        }

        // 电影票
        if (\ProductUtils\Deal::isFilmTicket(array('category' => $return['category'], 'show_category' => $return['show_category']))) {
            $return['movie_id'] = $return['virtual_goods_id'];
            $return['channel_id'] = $return['extension_id'];
        }

        // 标签
        $return['product_tags'] = $baseParams['isHash'] ? json_decode($return['product_tags'], true) : $return['product_tags'];
        // 平台.
        $return['platform'] = \ProductUtils\CartSetting::getReturnPlatform($return['platform'], $return['sale_forms']);
        // 处理childItems
        self::parseChildItems($return);

        if (\ProductUtils\SaleType::isDomestic($return['sale_type'])) {
            // 促销价格
            if (\ProductUtils\SaleType::isMall($return['sale_type'])) {
                $saleInfo = array(
                    'sale_price' => $return['sale_price'],
                    'sale_start_time' => $return['sale_start_time'],
                    'sale_end_time' => $return['sale_end_time'],
                );
                $return['item_price'] = \ProductUtils\CartSetting::getSalePriceOrOrignal($return['item_price'], $saleInfo, $params);
            }

            // 会员价格
            if ($return['product_id'] > 0) {
                $vipInfo = array(
                    'vip_price_end_time' => $return['vip_price_end_time'],
                    'vip_price_start_time' => $return['vip_price_start_time'],
                    'gold_member_price' => $return['gold_member_price'],
                    'platinum_member_price' => $return['platinum_member_price'],
                    'diamond_member_price' => $return['diamond_member_price'],
                );
                $return['item_price'] = \ProductUtils\CartSetting::getVipPriceOrOrignal($vipInfo, $return['item_price'], $params, $return['sale_type']);
            }
        }

        return $return;
    }

    /**
     * 格式化Sku基础信息.
     * 
     * @param array  $data       Redis中的数据.
     * @param string $skuNo      Sku_no.
     * @param array  $return     返回数据.
     * @param array  $baseParams 基础入参.
     * @param array  $stock      需要查库存的.
     * @param string $kResultKey 结果集key. 
     * 
     * @return array
     */
    public static function formatBaseSkuData($data, $skuNo, $return, $baseParams, $stock, $kResultKey)
    {
        
        if (empty($skuNo)) {
            return $return;
        }
        $cacheKey = \ProductUtils\CartSetting::getSkuCacheKey($skuNo, \ProductUtils\CartSetting::getReadRedisStoreType());
        $return['tpi_stocks'] = 0;
        if (isset($stock[$cacheKey]) && isset($stock[$cacheKey][$kResultKey])) {
            if (isset($data[$cacheKey])) {
                if ($baseParams['isMix']) {
                    $tpiTmp = isset($data[$cacheKey]['data']) ? json_decode($data[$cacheKey]['data'], true) : array();
                } elseif ($baseParams['isString']) {
                    $tpiTmp = isset($data[$cacheKey]) ? json_decode($data[$cacheKey], true) : array();
                } else {
                    $tpiTmp = isset($data[$cacheKey]) ? $data[$cacheKey] : array();
                }
                
                if (!empty($tpiTmp)) {
                    $tpi = \ProductUtils\CartSetting::restoreKeys($tpiTmp);
                    if (\ProductUtils\SaleType::isMall($return['sale_type'])) {
                        $return['item_price'] = $tpi['mall_price'];
                        $return['original_price'] = $tpi['original_price'];
                    } else {
                        unset(
                            $tpi['mall_price'],
                            $tpi['original_price']
                        );
                        
                        if (\ProductUtils\SaleType::isPop($return['sale_type'])) {
                            unset($tpi['media_rebate_ratio']);
                        }
                    }
                    $return = array_merge($return, $tpi);
                }
            }
        }

        return $return;
    }

    /**
     * 获取格式化数据基础入参.
     * 
     * @param array $params 基础入参.
     * 
     * @return array
     */
    public static function getBaseParams($params)
    {
        $isString = \ProductUtils\CartSetting::isStringModel($params['storeType']);
        $isHash = \ProductUtils\CartSetting::isHashTableModel($params['storeType']);
        $isMix = \ProductUtils\CartSetting::isMixModel($params['storeType']);
        $jdsrBasekey = \ProductUtils\CartSetting::JDSR_DATA_IN_STRING_MODEL;

        return compact('isString', 'isHash', 'jdsrBasekey', 'isMix');
    }

    /**
     * 格式化JDSR数据.
     * 
     * @param array  $data       Redis中的数据.
     * @param string $skuNo      Sku_no.
     * @param array  $return     返回数据.
     * @param array  $baseParams 基础入参.
     * 
     * @return array
     */
    public static function formatRelationData($data, $skuNo, $return, $baseParams)
    {

        if (empty($skuNo)) {
            return $return;
        }

        $jdsrkey = \ProductUtils\CartSetting::JDSR_PREFIX . $skuNo;
        $jdsrBasekey = $baseParams['jdsrBasekey'];
        $jdsr = array();
        if ($baseParams['isString']) {
            if (isset($data[$jdsrBasekey][$jdsrkey])) {
                $jdsr = $data[$jdsrBasekey][$jdsrkey];
                unset($return[$jdsrBasekey][$jdsrkey]);
            }
            unset($return[\ProductUtils\CartSetting::JDSR_DATA_IN_STRING_MODEL]);

        } else {
            $jdsr = isset($data[$jdsrkey]) ? json_decode($data[$jdsrkey], true) : array();
            if (isset($data[$jdsrkey])) {
                $jdsr = json_decode($data[$jdsrkey], true);
                unset($return[$jdsrkey]);
            }
        }

        $return['is_enable'] = 0;
        if (!empty($jdsr)) {
            $jdsr = \ProductUtils\CartSetting::restoreKeys($jdsr);
            if (\ProductUtils\SaleType::isNewCombination($return['sale_type'])) {
                // 处理childItems
                self::parseChildItems($jdsr);
            }
            $return = array_merge($return, $jdsr);
        }

        // 商城的需要把status转化对应的值.
        if (\ProductUtils\SaleType::isMall($return['sale_type'])) {
            if (\ProductUtils\SaleType::isDomestic($return['sale_type'])) {
                $return['is_enable'] = $return['is_enabled'];
            } else {
                $return['is_enable'] = $return['show_status'];
            }
        }

        return $return;
    }

    /**
     * 获取冗余仓库信息.
     * 
     * @param array $result 返回数据.
     * 
     * @return array
     */
    public function getWarehouse($result)
    {
        $iwc = array();
        foreach ($result as $k => $v) {
            if ($v['code'] == 0) {
                if (!\ProductUtils\SaleType::isSelfOperated($v['data']['sale_type'])) {
                    continue;
                }

                if (empty($v['sku_no'])) {
                    continue;
                }

                $iwc[$v['sku_no']] = $v['data']['iwc'];
            }
        }
        return $iwc;
    }

    /**
     * 检测接口.
     * 
     * @param array $result 数据.
     * @param array $params 入参.
     * 
     * @return array
     */
    public function checkData($result, $params)
    {
        foreach ($result as $k => $v) {
            
        }
        return $result;
    }

    /**
     * 获取促销数据.
     * 
     * @param array $result    数据.
     * @param array $extension 扩展参数.
     * 
     * @return array
     */
    public function getExtensionData($result, $extension)
    {
        $categories = '0';
        if ($result['product_id'] > 0) {
            $categories = implode(
                ",",
                array(
                    $result['category_v3_1'],
                    $result['category_v3_2'],
                    $result['category_v3_3'],
                    $result['category_v3_4'],
                )
            );
        } else {
            $result['brand_id'] = '0';
        }
        $result['sku_category'] = isset($result['sku_category']) ? $result['sku_category'] : '';
        $extends = array('brandIds' => $result['brand_id'], 'saleType' => $result['sale_type'], 'categoryIds' => $categories, 'sku_category' => $result['sku_category']);
        if (\ProductUtils\SaleType::isNewCombination($result['sale_type'])) {
            $extends['mainSku'] = $result['master_sku'];
            $extends['mainSaleType'] = $result['sale_type'] & ~\ProductUtils\Deal::NEW_COMBINATION_CODE;
        }

        if (\ProductUtils\SaleType::isDeal($result['sale_type'])) {
            $extends['saleForms'] = $result['sale_forms'];
        }

        return array_merge($extension, $extends);
    }

    /**
     * 剔除不需要的数据.
     * 
     * @param array  $result   结果数据.
     * @param string $saleType 类型.
     * 
     * @return array
     */
    public function removeUnusedData($result, $saleType)
    {
        unset(
            $result['extension_id'],
            $result['virtual_goods_id'],
            $result['sale_price'],
            $result['sale_end_time'],
            $result['sale_start_time'],
            $result['vip_price_end_time'],
            $result['vip_price_start_time'],
            $result['gold_member_price'],
            $result['platinum_member_price'],
            $result['diamond_member_price'],
            $result['tpi_stocks'],
            $result['deal_sellable_num'],
            $result['is_enable'],
            $result['master_sku'],
            $result['mall_price']
        );

        if (\ProductUtils\SaleType::isDomestic($result['sale_type']) && \ProductUtils\SaleType::isMall($result['sale_type'])) {
            unset(
                $result['show_status']
            );
        }

        return $result;
    }

    /**
     * 获取对应的配置.
     *
     * @param string $type 类型.
     *
     * @return void
     */
    public function getStocksKeys($type)
    {

    }

    /**
     * 获取库存信息.
     *
     * @param array $return 基础返回数据.
     * @param array $params 判断入参信息.
     *
     * @return array
     */
    public static function getIwcFromData($return, $params)
    {
        return !empty($return['iwc']) ? ($params['isHash'] ? json_decode($return['iwc'], true) : $return['iwc']) : array();
    }

    /**
     * 获取需要懒加载的key.
     *
     * @param array $items      入参.
     * @param array $keyToIndex 所有用于查询的key.
     * @param array $data       已经查询到的key.
     * @param array $params     基础参数.
     *
     * @return array
     */
    public function getLazyParams($items, $keyToIndex, $data, $params)
    {
        $return = array();
        if (empty($items) || empty($keyToIndex)) {
            return $return;
        }

        $keys = array();
        foreach ($data as $k => $v) {
            if (\ProductUtils\CartSetting::isStringModel($params['storeType'])) {
                if (!empty($v)) {
                    $keys[$k] = '';
                }
            } elseif (!\ProductUtils\CartSetting::isHashTableModel($params['storeType'])) {
                if (!empty($v['short_name'])) {
                    $keys[$k] = '';
                }
            } else {
                if (!empty($v['data'])) {
                    $keys[$k] = '';
                }
            }
        }

        $insectKeys = array_diff_key($keyToIndex, $keys);
        foreach ($insectKeys as $cacheKey => $itemKeys) {
            $return = array_merge($return, array_intersect_key($items, $itemKeys));
        }

        return $return;
    }

    /**
     * 获取懒加载数据.
     *
     * @param array  $items           购物项.
     * @param array  $params          基础入参.
     * @param string $phpClientConfig PHPClient配置.
     *
     * @return array
     */
    public function getLazyDataByParams($items, $params, $phpClientConfig)
    {
        if (empty($items)) {
            return array();
        }
        return \PHPClient\Text::inst($phpClientConfig)->setClass('JumeiProduct_Cart_Write')->setDataInfoToRedisBySkuNos($items, $params['storeType'], false);
    }

    /**
     * 格式化并且合并懒加载数据.
     *
     * @param array $lazyData 懒加载获取到的数据.
     * @param array $data     结果数据.
     *
     * @return array
     */
    public static function formatLazyDataAndMergeData($lazyData, $data)
    {
        $data = self::checkSetAndMergeData($lazyData, 'D', $data, 'main');
        $data = self::checkSetAndMergeData($lazyData, 'T', $data, 'tpi');
        return $data;
    }

    /**
     * 判断数据是否存在,并且循环归并到数组中.
     *
     * @param array  $data   需要归并的数据.
     * @param string $key    判断是否存在的数据key.
     * @param array  $return 归并如的接口.
     * @param string $type   数据类型,是主体信息还是TPI信息.
     *
     * @return array
     */
    public static function checkSetAndMergeData($data, $key, $return, $type)
    {
        if (!isset($data[$key])) {
            return $return;
        }

        $func = $type == 'main' ? 'getCacheKey' : 'getSkuCacheKey';

        foreach ($data[$key] as $k => $v) {
            $key = \ProductUtils\CartSetting::$func($k, \ProductUtils\CartSetting::getReadRedisStoreType());
            $return[$key] = $v;
        }

        return $return;

    }

    /**
     * 批量获取比对数据.
     *
     * @param array  $keys         需要查询的Key.
     * @param string $getWhich     获取类型.
     * @param array  $redisIpList  Redis Ip列表.
     * @param string $storeType    存储类型.
     * @param string $redisSetting Redis配置.
     *
     * @return array
     */
    public function batachGetDataByGivenForCompareign($keys, $getWhich, $redisIpList, $storeType, $redisSetting)
    {

        if (empty($redisIpList)) {
            return array();
        }

        $result = array(
            'relation' => array()
        );
        $ogrinalConfig = (array) new \Config\Redis();
        foreach ($redisIpList as $k => $ip) {
            $redisSettingKey = md5($ip);
            $ogrinalConfig[$redisSettingKey] = $ogrinalConfig[$redisSetting];
            $ogrinalConfig[$redisSettingKey]['nodes'] = array(
                array(
                    'master' => $ip,
                    'master-alia' => $ip
                )
            );
            \ProductUtils\RedisHandler::setRedisConfig($ogrinalConfig);
            $data = \ProductUtils\RedisHandler::batchGetDataForCart($keys, $storeType, $getWhich, $redisSettingKey);
            $result[$ip] = $data;
            foreach ($data as $kId => $item) {
                $result['relation'][$kId][] = $ip;
            }
        }

        return $result;
    }

    /**
     * 处理ChildItems.
     *
     * @param array $data Data.
     *
     * @return void
     */
    private static function parseChildItems(&$data)
    {
        if (isset($data['child_items']) && !empty($data['child_items'])) {
            foreach ($data['child_items'] as $k => $v) {
                $data['child_items'][$k] = \ProductUtils\CartSetting::exchangeKeys($v, array_flip(\ProductUtils\CartSetting::$sr));
            }
        }
    }

    /**
     * 合并数组，保留key.
     *
     * @param array $master MasterArr.
     * @param array $marge  MergeArr.
     *
     * @return array
     */
    public function arrayMerge(array $master,array $marge)
    {
        if ($marge) {
            foreach ($marge as $k => $v) {
                $master[$k] = $v;
            }
        }
        return $master;
    }

}
