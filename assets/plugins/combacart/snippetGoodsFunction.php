<?php

/**
 * GoodsFunction
 *
 * function for prepare read page
 *
 * @category    snippet
 * @version     2.5
 * @package     evo
 * @internal    @modx_category Comba
 * @author      zatomant
 * @lastupdate  22-02-2022
 */


use Comba\Bundle\Modx\ModxSeller;
use Comba\Bundle\Modx\ModxMarketplace;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
include_once(dirname(__FILE__) . '/autoload.php');

if (empty($fnct)) {
    return;
}

$out = null;

if (strpos($fnct, 'goodslguid') !== false) {
    $out = md5($string);
}

if (strpos($fnct, 'buttonbuy') !== false) {
    if (empty($avail) || $avail < 1) {
        $out = '[(__goods_status_outofstock)]';
    } else {
        $btnCaption = '[(__buy)]';
        $btnStyle = 'btn-success';
        $btnClass = '';

        if (!empty($class)) {
            $btnClass = $class;
        }

        if (!empty($ondem) && ($ondem == 1)) {
            $btnCaption = '[(__goods_status_ondemand)]';
            $btnStyle = 'btn-warning';
        } elseif (!empty($old) && $old > 0) {
            $btnStyle = 'btn-danger text-white';
        }
        $out = '<button type="submit" name="submit" class="submit buybutton text-capitalize rounded-start ' . $btnClass . ' btn ' . $btnStyle . '">' . $btnCaption . '</button>';
    }
}

if (strpos($fnct, 'is') !== false) {
    $values = explode(';', $cond);
    if (count($values) === count(array_filter($values, fn($value) => $value !== '' && $value > 0))) {
        $out = $then;
    }
}

if (strpos($fnct, 'getSellers') !== false) {
    $_out = '';
    $sellers_list = (new ModxMarketplace())->sellers();
    foreach ($sellers_list as $el) {
        $seller = (new ModxSeller())->setUID($el['uid'])->get();
        if (!empty($seller['uid'])) {
            $_out .= $seller['label'] . '==' . $seller['uid'] . '||';
        }
    }
    $_out = !empty($_out) ? substr($_out, 0, -2) : '';
    $out .= $_out;
}
if (strpos($fnct, 'showSeller') !== false && defined('COMBAMODX_SELLER_SHOW') && COMBAMODX_SELLER_SHOW) {
    $sellers_list = (new ModxMarketplace())->sellers($seller);
    foreach ($sellers_list as $el) {
        $seller = (new ModxSeller())->setUID($el['uid'])->get();
        if (!empty($seller['uid'])) {
            $out = '<span class="small">[(__seller)]: ' .$seller['label'] .'</span>';
            break;
        }
    }
    return $out;
}

return $out;
