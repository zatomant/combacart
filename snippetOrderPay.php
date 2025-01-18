<?php
/**
 * OrderPaybyID
 *
 * function Read Payment page for orders
 *
 * @category    snippet
 * @version     2.6
 * @package     evo
 * @internal    @modx_category Comba
 * @author      zatomant
 * @lastupdate  22-02-2022
 */

/*
use:
[!OrderPaybyID!]
if set $e - it means snippet call from pluginCombaHelper PageNotFound and we set 'pagefull' external template

If this snippet have "Disable" check used OnPageNotFound in plugins CombaHelper for any not-found pages.
*/

use Comba\Bundle\Modx\Orderpay\ModxOperOrderPay;
use Comba\Core\Entity;
use Comba\Core\Parser;
use function Comba\Functions\safeHTML;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

require_once __DIR__ . '/autoload.php';

$action = new ModxOperOrderPay(new Parser());
$action->setModx($modx);
$action->detectLanguage();
$action->setOptions('pay', $pay ?? null);
$action->setOptions('pagefull', isset($e));
return $action->render();
