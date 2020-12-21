<?php
/**
 * 商品sale type.
 *
 * @author qiangh<qiangh@jumei.com>
 */

namespace ProductUtils;

/**
 * 变更记录:
 *     2016-03-30 新增新组合购相关类型与逻辑.
 *     2017-04-13 新增生鲜类型与逻辑.
 */

/**
 * 商品sale type.
 */
class SaleType
{
    const ST_POP = 0x1;
    const ST_GLOBAL = 0x2;
    const ST_MALL = 0x4;
    const ST_PROMO_CARD = 0x8;
    const ST_GIFT = 0x10;
    const ST_NEW_COMBINATION = 0x20;
    const ST_FILM_TICKET = 0x40;
    const ST_REDEMPTION = 0x80;
    const ST_FRESH = 0x100;

    const EN_JUMEI_DEAL = 'jumei_deal';
    const EN_JUMEI_POP = 'jumei_pop';
    const EN_GLOBAL_DEAL = 'jumei_global';
    const EN_GLOBAL_POP = 'global_pop';
    const EN_JUMEI_MALL = 'jumei_mall';
    const EN_POP_MALL = 'pop_mall';
    const EN_GLOBAL_MALL = 'global_mall';
    const EN_GLOBAL_POP_MALL = 'global_pop_mall';
    const EN_PROMO_CARDS = 'promo_cards';
    const EN_JUMEI_GIFT = 'jumei_gift';
    const EN_GLOBAL_GIFT = 'global_gift';
    const EN_GLOBAL_NEW_COMBINATION = 'global_deal_new_combination';
    const EN_GLOBAL_MALL_NEW_COMBINATION = 'global_mall_new_combination';
    const EN_FILM_TICKET = 'jumei_film';
    const EN_JUMEI_REDEMPTIONT = 'jumei_redemption';
    const EN_GLOBAL_REDEMPTIONT = 'global_redemption';
    const EN_FRESH = 'fresh_goods';

    /**
     * 是海淘商品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isGlobal($mask)
    {
        return $mask & self::ST_GLOBAL;
    }

    /**
     * 是国内商品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isDomestic($mask)
    {
        return !self::isGlobal($mask);
    }

    /**
     * 是Pop商品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isPop($mask)
    {
        return $mask & self::ST_POP;
    }

    /**
     * 是自营商品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isSelfOperated($mask)
    {
        return !self::isPop($mask);
    }

    /**
     * 是商城商品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isMall($mask)
    {
        return $mask & self::ST_MALL;
    }

    /**
     * 是Deal商品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isDeal($mask)
    {
        return !self::isMall($mask);
    }

    /**
     * 红包/现金券.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isPromoCard($mask)
    {
        return $mask & self::ST_PROMO_CARD;
    }

    /**
     * 赠品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isGift($mask)
    {
        return $mask & self::ST_GIFT;
    }

    /**
     * 配送区域限制,不光是生鲜.
     *
     * @param integer $mask SaleType.
     *
     * @return integer
     */
    public static function isFresh($mask)
    {
        return $mask & self::ST_FRESH;
    }

    /**
     * 赠品.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isFilmTicket($mask)
    {
        return $mask & self::ST_FILM_TICKET;
    }

    /**
     * 换购.
     *
     * @param integer $mask SaleType.
     *
     * @return integer
     */
    public static function isRedemption($mask)
    {
        return $mask & self::ST_REDEMPTION;
    }

    /**
     * 国内pop商城(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isPopMall($mask)
    {
        return self::isDomestic($mask)
            && self::isPop($mask)
            && self::isMall($mask);
    }

    /**
     * 新组合购.
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isNewCombination($mask)
    {
        return $mask & self::ST_NEW_COMBINATION;
    }

    /**
     * 把二进位标识影射成数组key => value :).
     *
     * @param integer $mask SaleType.
     *
     * @return array
     */
    public static function map($mask)
    {
        return array(
            'is_self_operated' => self::isSelfOperated($mask), // 自营.
            'is_pop' => self::isPop($mask), // 联营.
            'is_domestic' => self::isDomestic($mask), // 国内.
            'is_global' => self::isGlobal($mask), // 海淘.
            'is_deal' => self::isDeal($mask), // 特卖.
            'is_mall' => self::isMall($mask), // 商城.
            'is_promo_card' => self::isPromoCard($mask), // 红包(现金券).
            'is_gift' => self::isGift($mask), // 赠品.
            'is_new_combination' => self::isNewCombination($mask), // 新组合购.
            'is_film_ticket' => self::isFilmTicket($mask), // 电影票.
            'is_redemption' => self::isRedemption($mask), // 换购.
            'is_fresh' => self::isFresh($mask), // 配送区域限制,不光是生鲜.
        );
    }

    /**
     * 国内自营商城(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isJumeiMall($mask)
    {
        return self::isDomestic($mask) && self::isSelfOperated($mask) && self::isMall($mask);
    }

    /**
     * 海淘自营商城(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isGlobalMall($mask)
    {
        return self::isGlobal($mask) && self::isSelfOperated($mask) && self::isMall($mask);
    }

    /**
     * 海淘POP商城(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isGlobalPopMall($mask)
    {
        return self::isGlobal($mask) && self::isPop($mask) && self::isMall($mask);
    }

    /**
     * 国内自营特卖(deal)(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isJumeiDeal($mask)
    {
        return self::isDomestic($mask) && self::isSelfOperated($mask) && self::isDeal($mask);
    }

    /**
     * 国内联营(POP)特卖(deal)(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isJumeiPop($mask)
    {
        return self::isDomestic($mask) && self::isPop($mask) && self::isDeal($mask);
    }

    /**
     * 海涛自营特卖(deal)(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isGlobalDeal($mask)
    {
        return self::isGlobal($mask) && self::isSelfOperated($mask) && self::isDeal($mask);
    }

    /**
     * 国内联营(POP)特卖(deal)(如果商品有红包,赠品等属性也会返回true，如果要排除其它的商品属性，需要业务做排除).
     *
     * @param integer $mask SaleType.
     *
     * @return boolean
     */
    public static function isGlobalPop($mask)
    {
        return self::isGlobal($mask) && self::isPop($mask) && self::isDeal($mask);
    }

    /**
     * 把二进制标识转化成具体的枚举类型.
     *
     * @param integer $mask Sale_Type.
     *
     * @return string
     */
    public static function saleTypeToEnumeration($mask)
    {

        $return = '';
        if (self::isGift($mask)) {
            // 赠品
            $return = self::EN_JUMEI_GIFT;
            if (self::isGlobal($mask)) {
                $return = self::EN_GLOBAL_GIFT;
            }
            return $return;
        }

        if (self::isFilmTicket($mask)) {
            return self::EN_FILM_TICKET;
        }

        if (self::isRedemption($mask)) {
            $return = self::EN_GLOBAL_REDEMPTIONT;
            if (self::isJumeiDeal($mask)) {
                $return = self::EN_JUMEI_REDEMPTIONT;
            }
            return $return;
        }

        if (self::isNewCombination($mask)) {
            // 新组合购.
            $return = self::EN_GLOBAL_NEW_COMBINATION;
            if (self::isGlobalMall($mask)) {
                $return = self::EN_GLOBAL_MALL_NEW_COMBINATION;
            }
            return $return;
        }

        if (self::isPromoCard($mask)) {
            return self::EN_PROMO_CARDS;
        }

        if (self::isJumeiDeal($mask)) {
            return self::EN_JUMEI_DEAL;
        }

        if (self::isJumeiPop($mask)) {
            return self::EN_JUMEI_POP;
        }

        if (self::isGlobalDeal($mask)) {
            return self::EN_GLOBAL_DEAL;
        }

        if (self::isGlobalPop($mask)) {
            return self::EN_GLOBAL_POP;
        }

        if (self::isJumeiMall($mask)) {
            return self::EN_JUMEI_MALL;
        }

        if (self::isPopMall($mask)) {
            return self::EN_POP_MALL;
        }

        if (self::isGlobalMall($mask)) {
            return self::EN_GLOBAL_MALL;
        }

        if (self::isGlobalPopMall($mask)) {
            return self::EN_GLOBAL_POP_MALL;
        }

        return $return;
    }

}
