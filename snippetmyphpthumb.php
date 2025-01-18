<?php
/**
 * myphpthumb
 *
 * extend base on phpthumb snippet
 *
 * @category    snippet
 * @version     2.6
 * @package     evo
 * @internal    @modx_category Comba
 * @internal    @installset base
 * @lastupdate  22-02-2022
 */


/**
 * if $webp = 1 use webp for images
 * if $filemtime = 1 check filemtime in md5-cache file
 **/
require_once MODX_BASE_PATH .'/assets/snippets/phpthumb/phpthumb.class.php';
include_once MODX_BASE_PATH . '/vendor/autoload.php';

use claviska\SimpleImage;
use WebPConvert\WebPConvert;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

if (!empty($input) && strtolower(substr($input, -4)) == '.svg') {
    return $input;
}

$newFolderAccessMode = $modx->getConfig('new_folder_permissions');
$newFolderAccessMode = empty($new) ? 0755 : octdec($newFolderAccessMode);

$defaultCacheFolder = 'assets/cache/';
$cacheFolder = $cacheFolder ?? $defaultCacheFolder . 'images';
$phpThumbPath = $phpThumbPath ?? 'assets/snippets/phpthumb/';

/**
 * @see: https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
 */
$path = MODX_BASE_PATH . $cacheFolder;
if (!file_exists($path) && mkdir($path) && is_dir($path)) {
    chmod($path, $newFolderAccessMode);
}

if (!empty($input)) {
    $input = rawurldecode($input);
}

if (empty($input) || !file_exists(MODX_BASE_PATH . $input)) {
    $input = $noImage ?? $phpThumbPath . 'noimage.jpg';
}

/**
 * allow read in phpthumb cache folder
 */
if (!file_exists(MODX_BASE_PATH . $cacheFolder . '/.htaccess') &&
    $cacheFolder !== $defaultCacheFolder &&
    strpos($cacheFolder, $defaultCacheFolder) === 0
) {
    file_put_contents(MODX_BASE_PATH . $cacheFolder . '/.htaccess', "order deny,allow\nallow from all\n");
}

$path_parts = pathinfo($input);
$tmpImagesFolder = str_replace('assets/images', '', $path_parts['dirname']);
$tmpImagesFolder = explode('/', $tmpImagesFolder);
$ext = strtolower($path_parts['extension']);
$options = 'f=' . (in_array($ext, array('png', 'gif', 'jpeg')) ? $ext : 'jpg&q=85') . '&' .
    strtr($options, array(',' => '&', '_' => '=', '{' => '[', '}' => ']'));

$text = $params['text'] ?? null;
parse_str($options, $params);

$fmtime = '';
if (isset($filemtime)) {
    $fmtime = filemtime(MODX_BASE_PATH . $input);
}


/* mkdir for w&h options */
$pathopt = '';
if (!empty($params['w']) || !empty($params['h'])) {

    if (!empty($params['ratio']) && !empty($params['w'])) {
        if (is_numeric($params['ratio']) && is_numeric($params['w'])) $params['h'] = intval($params['w'] / $params['ratio']);
    }

    $pathopt = '/' . ($params['w'] ?? '') . 'x' . ($params['h'] ?? '');
    $pathopt .= !empty($params['ratio']) && !is_numeric($params['ratio']) ? '_' . $params['ratio'] : '';

    if ($params['w'] >= 500 || $params['h'] >= 500) $options .= 'wmt';
}

$path .= $pathopt;
$cacheFolder .= $pathopt;
if (!file_exists($path) && mkdir($path) && is_dir($path)) {
    chmod($path, $newFolderAccessMode);
}
/* end mkdir */

foreach ($tmpImagesFolder as $folder) {
    if (!empty($folder)) {
        $cacheFolder .= '/' . $folder;
        $path = MODX_BASE_PATH . $cacheFolder;
        if (!file_exists($path) && mkdir($path) && is_dir($path)) {
            chmod($path, $newFolderAccessMode);
        }
    }
}

$fNamePref = rtrim($cacheFolder, '/') . '/';
$fName = $path_parts['filename'];
$fNameSuf = '.' . $path_parts['extension'];//$params['f'];

/*
$fNameSuf = '-' .
    (isset($params['w']) ? $params['w'] : '') . 'x' . (isset($params['h']) ? $params['h'] : '') . '-' .
    substr(md5(serialize($params) . $fmtime), 0, 3) .
    '.' . $params['f'];
*/

$fNameSuf = str_replace("ad", "at", $fNameSuf);

$outputFilename = MODX_BASE_PATH . $fNamePref . $fName . $fNameSuf;
if (!empty($params['force'])) {
    if (file_exists($outputFilename)) unlink($outputFilename);
}

if (!file_exists($outputFilename)) {
    if (!class_exists('phpthumb')) {
        require_once MODX_BASE_PATH . $phpThumbPath . '/phpthumb.class.php';
    }
    $phpThumb = new phpthumb();
    $phpThumb->config_cache_directory = MODX_BASE_PATH . $defaultCacheFolder;
    $phpThumb->config_temp_directory = $defaultCacheFolder;
    $phpThumb->config_document_root = MODX_BASE_PATH;
    $phpThumb->setSourceFilename(MODX_BASE_PATH . $input);

    foreach ($params as $key => $value) {
        $phpThumb->setParameter($key, $value);
    }
    if ($phpThumb->GenerateThumbnail()) {
        $phpThumb->RenderToFile($outputFilename);

        // create watermark
        if (!empty($text)) {
            if (file_exists($outputFilename)) {

                $_text = key($text);
                $_text_param = array_values($text);

                if (!empty($_text) && !empty($_text_param) && file_exists($_text_param[0]['fontFile'])) {
                    $image = new SimpleImage();
                    $image
                        ->fromFile($outputFilename)
                        ->text($_text, $_text_param[0])
                        ->toFile($outputFilename);
                }
            }
        }

    } else {
        $modx->logEvent(0, 3, implode('<br/>', $phpThumb->debugmessages), 'phpthumb');
    }
}
if (isset($webp) && class_exists('\WebPConvert\WebPConvert')) {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mac OS') === false) {
        if (file_exists($outputFilename . '.webp')) {
            $fNameSuf .= '.webp';
        } else {
            WebPConvert::convert($outputFilename, $outputFilename . '.webp');
            $fNameSuf .= '.webp';
        }
    }
}
//$modx->logEvent(1, 1, $fNamePref . $fName . $fNameSuf, $fName);
return $fNamePref . $fName . $fNameSuf;
//return $fNamePref . rawurlencode($fName) . $fNameSuf;