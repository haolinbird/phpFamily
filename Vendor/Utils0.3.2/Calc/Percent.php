<?php
/**
 * @author: dengjing<jingd3@jumei.com>.
 *
 */
namespace Utils\Calc;

/**
 * 计算器工具 - 按照元素所占比例分摊金额.
 */
class Percent extends \Utils\Singleton {

    /**
     * Get instance of the derived class.
     *
     * @return \Utils\Calc\Percent
     */
    public static function instance()
    {
        return parent::instance();
    }

    /**
     * 按元素的金额比例进行价格计算.
     *
     * @param float $totalAmount 总价.
     * @param array $items       元素的数量和价格数组.
     *
     * @return array
     */
    public static function amountPercent($totalAmount, array $items)
    {
        $itemTotalAmount = 0.00;
        $freeItems = array();
        foreach ($items as $key => &$item) {
            if (bccomp($item['price'], 0.000, 3) == 0) {
                $item['percent'] = 0;
                $item['use_amount'] = 0.00;
                $item['use_price'] = 0.000;
                $freeItems[$key] = $item;
                unset($items[$key]);
                continue;
            }
            $itemAmount = $item['quantity'] * $item['price'];
            $item['amount'] = $itemAmount;
            $itemTotalAmount += $itemAmount;
        }
        unset($item);

        if ($itemTotalAmount <= 0.000) {
            foreach ($items as &$item) {
                $item['percent'] = 0.00;
                $item['use_amount'] = 0.00;
                $item['use_price'] = 0.00;
            }
            return $items;
        }

        $count = count($items);
        $itemUseTotalPercent = 0.00;
        $itemUseTotalAmount = 0.00;
        $i = 1;
        foreach ($items as $key => &$item) {
            $itemPercent = 0.00;
            $itemUseAmount = 0.00;

            if ($i == $count) {
                $item['percent'] = 1 - $itemUseTotalPercent;
                $item['use_amount'] = $totalAmount - $itemUseTotalAmount;
                $item['use_price'] = round($item['use_amount'] / $item['quantity'], 3);
            } else {
                $itemPercent = $item['amount'] / $itemTotalAmount;
                $itemUseAmount = $totalAmount * $itemPercent;
                $item['percent'] = $itemPercent;
                $item['use_amount'] = round($itemUseAmount, 2);
                $item['use_price'] = round($itemUseAmount / $item['quantity'], 3);
                $itemUseTotalAmount += $item['use_amount'];
                $itemUseTotalPercent += $itemPercent;
            }
            $i++;
        }
        if (!empty($freeItems)) {
            $items += $freeItems;
        }
        return $items;
    }

    /**
     * 按元素均分计算器.
     *
     * @param float   $totalAmount 总价.
     * @param array   $items       元素的数量和价格数组.
     * @param boolean $useIntegral 是否启用取整操作.
     *
     * @return array
     */
    public function average($totalAmount, array $items, $useIntegral = true)
    {
        if (!$items || $totalAmount <= 0.00) {
            return array();
        }

        $itemUsedTotalAmount = $totalAmount;
        $count = count($items);
        $itemAmount = $totalAmount / $count; // 均分单价.

        $i = 1;
        foreach ($items as &$item) {
            $itemUseAmount = 0.00;
            if ($i == $count) {
                $item['use_amount'] = $itemUsedTotalAmount;
            } else {
                if ($useIntegral) {
                    $itemUseAmount = intval($itemAmount);
                } else {
                    $itemUseAmount = $itemAmount;
                }
                $item['use_amount'] = round($itemUseAmount, 2);
                $itemUsedTotalAmount -= $item['use_amount'];
            }
            $i++;
        }
        return $items;
    }

}
