<?php
/**
 * GetImagePreset
 *
 * Return image`s preset ratio
 *
 * @category    snippet
 * @version     2.6
 * @package     evo
 * @internal    @modx_category Comba
 * @internal    @installset base
 * @lastupdate  22-02-2022
 */

use Comba\Core\Entity;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

if (empty($options)) {
    return '';
}

$ratio_default = array(
    'img16x9' => 'far=C',
    'img4x3' => 'zc=C',
    'img1x1' => 'zc=C',
    'img2x3' => 'zc=C',
);

$presets = Entity::getData('Imagepresets');

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
$_images = json_decode($modxobject[Entity::get('TV_GOODS_IMAGES')][1], true);
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
    [
        //'id' => $id,
        'imgsrc' => $img ?? null,
        'options' => $preset,
        'force' => !empty($force) ? 1 : 0
    ]
);
