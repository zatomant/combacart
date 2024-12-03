<?php
/**
 * GetImagePreset
 *
 * Return image`s preset ratio
 *
 * @category    snippet
 * @version     2.5
 * @package     evo
 * @internal    @modx_category Comba
 * @internal    @installset base
 * @lastupdate  22-02-2022
 */

if (empty($options)) return '';


$ratio_default = array(
    'img16x9' => 'far=C',
    'img4x3' => 'zc=C',
    'img1x1' => 'zc=C',
    'img2x3' => 'zc=C',
);
$presets = array(
    'page-goods' => ['ratio' => 'img4x3', 'name' => '4x3', 'value' => 'w=450,h=340,q=80,img4x3'],
    'catalog-goods' => ['ratio' => 'img4x3', 'name' => '4x3', 'value' => 'w=450,h=340,q=80,img4x3'],
    'catalog-themes' => ['ratio' => 'img4x3', 'name' => '4x3', 'value' => 'w=450,h=340,q=80,img4x3'],
    'page-companions' => ['ratio' => 'img4x3', 'name' => '4x3', 'value' => 'w=450,h=340,q=80,img4x3'],
    'page-goods-top' => ['ratio' => 'img1x1', 'name' => '1x1-page', 'value' => 'w=550,h=550,q=80,img1x1'],
    'page-images' => ['ratio' => 'img4x3', 'name' => '4x3-page-images', 'value' => 'w=200,h=150,q=80,img4x3'],
    'image-max' => ['ratio' => 'image-max', 'name' => 'image-max', 'value' => 'w=800,q=80,fltr{}=watermark'],
    'goods-slider' => ['ratio' => 'img1x1', 'name' => '1x1-indicators', 'value' => 'w=100,h=100,q=80,far=TL,iar=1,img1x1'],
    'cart-goods' => ['ratio' => 'img1x1', 'name' => '1x1-checkout', 'value' => 'w=250,h=250,q=80,far=TL,iar=1,img1x1'],
    'checkout-goods' => ['ratio' => 'img1x1', 'name' => '1x1-checkout', 'value' => 'w=250,h=250,q=80,far=TL,iar=1,img1x1']
);

$ratio_sfx = '';
$preset = '&options=`zc=C`'; // far=C,bg=ffffff
$ratio = 'image-max';

if ($_item = $presets[$options] ?? null) {
    $ratio = $_item['ratio'];
    $preset = $_item['value'];
    $ratio_sfx = ",ratio=" . $_item['name'];
}

$id = $modx->documentObject['id'];
$modxobject = $modx->getDocumentObject('id', $id, true);
$_images = json_decode($modxobject[GOODS_IMAGES][1], true);
foreach ($_images['fieldValue'] as $item) {
    if (isset($item['image'])) {
        $img = $item['image'];
        $imgratio = $item[$ratio] ?? null;

        //convert multitv image`s data for use in phpthumb class
        $imgratio = str_replace(array(':', 'x', 'y', 'width', 'height', ','), array('=', 'sx', 'sy', 'sw', 'sh', '&'), $imgratio);

        $ratio_default[$ratio] = $imgratio;
        break; // get only one image for sample
    }
}

//if (empty($ratio['img16x9']) && !empty($extimgratio)) {
//    $extratio = json_decode($extimgratio);
//
//    foreach ($extratio->fieldValue as $item) {
//        if (isset($item->image)) {
//            $img16x9 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item->img16x9);
//            $img4x3 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item->img4x3);
//            $img1x1 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item->img1x1);
//            $img2x3 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item->img2x3);
//            $ratio = compact('img16x9', 'img4x3', 'img1x1', 'img2x3');
//            break;
//        }
//    }
//}
//$ratio = array_merge($ratio_default, ($ratio ?? array()));

if (!empty($ratio_default)) {
    foreach ($ratio_default as $key => $value) {
        $preset = str_replace($key, $value, $preset);
    }
}
$preset .= $ratio_sfx;
$force = 1;

return $modx->runSnippet('GetImage',
    array(
        //'id' => $id,
        'imgsrc' => $img ?? null,
        'options' => $preset,
        'force' => !empty($force) ? 1 : 0
    )
);
