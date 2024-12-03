<?php
/**
 * GetImage
 *
 * Return resampling image
 *
 * @category    snippet
 * @version     2.5
 * @package     evo
 * @internal    @modx_category Comba
 * @internal    @installset base
 * @lastupdate  22-02-2022
 */

include_once MODX_BASE_PATH . 'assets/plugins/combacart/autoload.php';

/**
 * [[getImage]] without argument return cachabled image`s filename
 * arguments:
 * &oper=`src`- return original image`s filename
 * &webp=`1` use webp for images
 * &filemtime=`1` check filemtime in md5-cache file
 * f&orce=`1` to rewrite output image (delete & create new) in cache
 **/

return (new Comba\Bundle\Modx\ModxImage($modx))->getImage($modx->event->params);
