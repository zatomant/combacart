<?php
/**
 * OrderTracking
 *
 * function Read Tracking Status for orders
 *
 * @category    snippet
 * @version     2.5
 * @package     evo
 * @internal    @modx_category Comba
 * @author      zatomant
 * @lastupdate  22-02-2022
 */

/*
use:
[!OrderTracking!]
if set $e - it means snippet call from pluginCombaHelper PageNotFound and must set 'pagefull' external template

If this snippet has "Disable" check used OnPageNotFound in plugins CombaHelper for any not-found pages.
*/

use Comba\Bundle\Modx\Tracking\ModxOperTrackingExt;
use Comba\Core\Parser;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

require_once __DIR__ . '/autoload.php';

$action = new ModxOperTrackingExt(new Parser());
$action->setModx($modx);
$action->detectLanguage();
$action->setOptions('trk', $trk ?? null);
$action->setOptions('pagefull', isset($e));
return $action->render();
