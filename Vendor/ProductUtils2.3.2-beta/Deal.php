<?php
/**
 * 特卖相关操作.
 *
 * @author quans<quans@jumei.com>
 */

namespace ProductUtils;

/**
 * 工具包,工具.
 */
class Deal
{

    // 特卖类型相关设置.
    // 海淘自营
    const CATEGORY_RETAIL_GLOBAL = 'retail_global';
    // 海淘POP
    const CATEGORY_GLOBAL = 'global';
    // 新组合购
    const CATEGORY_NEW_COMBINATION_GLOBAL = 'new_combination_global';
    // 组合购
    const CATEGORY_COMBINATION_GLOBAL = 'combination_global';
    // 国内POP
    const CATEGORY_MEDIA = 'media';
    // 赠品(换购)
    const CATEGORY_REDEEM = 'redeem';
    // 虚拟商品
    const CATEGORY_VIRTUAL = 'virtual';

    // SALE_FORMS 相关值
    // 预售
    const SALE_FORMS_PRE = 'pre';
    // 赠品
    const SALE_FORMS_GIFT = 'gift';
    // 一起团
    const SALE_FORMS_YQT = 'yqt';
    // 普通
    const SALE_FORMS_NORMAL = 'normal';
    // 组合购
    const SALE_FORMS_COMBINATION = 'combination';

    // SHOW_CATEGORY 相关值.
    // 赠品
    const SHOW_CATEGORY_REDEEM = 'redeem';
    // 电影票
    const SHOW_CATEGORY_FILM = 'film';
    // 秒杀
    const SHOW_CATEGORY_SECKILL = 'seckill';

    // 商品类型
    const BASE_CODE = 0; // 00000000 基础
    const SALE_MODEL_CODE = 1; // 00000001 联营
    const SALE_PLATFORM_CODE = 2; // 00000010 海涛
    const MALL_CODE = 4; // 00000100 商城
    const CARDS_CODE = 8; // 00001000 红包现金券
    const REDEEM_CODE = 16; // 00010000 redeem
    const NEW_COMBINATION_CODE = 32; // 00100000 新组合购
    const FILM_TICKET_CODE = 64; // 01000000 电影票
    const REDEMPTION_CODE = 128; // 10000000 换购

    // 不可售状态
    const SKU_DISABLED_STATUS = 0;

    /**
     * Deal在售状态.
     *
     * @var array
     */
    public static $dealOnSaleStatus = array(0, 1);

    /**
     * 海淘商城在售状态.
     *
     * @var array
     */
    public static $globalMallOnSaleStatus = array(1);

    /**
     * 国内商城在售状态.
     *
     * @var array
     */
    public static $jumeiMallOnSaleStatus = array(1, 6);


    /**
     * 根据category判断是否海淘.
     *
     * @param string  $category Category.
     * @param boolean $isCom    判断海淘产品是否支持组合购.
     * @param array   $extends  扩展判断[目前包含新组合购].
     *
     * @return boolean
     */
    public static function isGlobalDealByCategory($category, $isCom = true, $extends = array('new_combination' => true))
    {

        $globalType = array(self::CATEGORY_GLOBAL, self::CATEGORY_RETAIL_GLOBAL);
        if ($isCom) {
            $globalType[] = self::CATEGORY_COMBINATION_GLOBAL;
        }

        if (isset($extends['new_combination'])) {
            $globalType[] = self::CATEGORY_NEW_COMBINATION_GLOBAL;
        }

        if (in_array($category, $globalType)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 根据category判断是否自营海淘.
     *
     * @param string  $category Category.
     * @param boolean $isCom    判断海淘产品是否支持组合购.
     * @param array   $extends  扩展判断[目前包含新组合购].
     *
     * @return boolean
     */
    public static function isRetailGlobalDealByCategory($category, $isCom = true, $extends = array('new_combination' => true))
    {

        $array = array(self::CATEGORY_RETAIL_GLOBAL);
        if ($isCom) {
            $array[] = self::CATEGORY_COMBINATION_GLOBAL;
        }

        if (isset($extends['new_combination'])) {
            $array[] = self::CATEGORY_NEW_COMBINATION_GLOBAL;
        }

        if (in_array($category, $array)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否是POP Deal.
     *
     * @param string  $category      Deal's category.
     * @param boolean $withOutGlobal 是否排除海淘.
     *
     * @return boolean
     */
    public static function isPOPDealByCategory($category, $withOutGlobal = false)
    {
        $category_data = array(self::CATEGORY_GLOBAL);
        if ($withOutGlobal) {
            $category_data[] = self::CATEGORY_MEDIA;
        }

        if (in_array($category, $category_data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否是预售.
     * 
     * @param integer $saleForms Jumei_deals_plugin.sale_forms.
     * 
     * @return boolean
     */
    public static function isPre($saleForms)
    {
        return self::SALE_FORMS_PRE === $saleForms;
    }

    /**
     * 判断是否是电影票依赖分类.
     * 
     * @param array $cond 分类.
     * 
     * @return boolean
     */
    public static function isFilmTicket($cond)
    {

        if ($cond['category'] == self::CATEGORY_VIRTUAL && $cond['show_category'] == self::SHOW_CATEGORY_FILM) {
            return true;
        }

        return false;
    }

    /**
     * 判断是否是一起团.
     * 
     * @param integer $saleForms Jumei_deals_plugin.sale_forms.
     * 
     * @return boolean
     */
    public static function isYQT($saleForms)
    {
        return self::SALE_FORMS_YQT === $saleForms;
    }

    /**
     * 判断是不是sku不可售状态.
     *
     * @param integer $status 启用状态.
     *
     * @return boolean
     */
    public static function isDisabledStatus($status)
    {
        return $status === self::SKU_DISABLED_STATUS;
    }

}
