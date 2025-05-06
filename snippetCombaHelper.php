<?php
/**
 * CombaHelper
 *
 * function Read
 *
 * @category    snippet
 * @version     2.6
 * @package     evo
 * @internal    @modx_category Comba
 * @author      zatomant
 * @lastupdate  22-02-2022
 */

use Comba\Bundle\CombaHelper\CombaHelper;
use Comba\Bundle\Modx\Cabinet\ModxOperCabinet;
use Comba\Bundle\Modx\Cart\ModxOperCart;
use Comba\Bundle\Modx\Cart\ModxOperCartTnx;
use Comba\Core\Entity;
use Comba\Core\Parser;
use function Comba\Functions\array_search_by_key;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

require_once __DIR__ . '/autoload.php';

if (empty($docTpl)) {
    $docTpl = '@FILE:/chunk_cart';
}
if (empty($docEmptyTpl)) {
    $docEmptyTpl = '@FILE:/chunk_cart_empty';
}

$out = '';
$ch = null;

if (in_array($action, ['readrequest', '_read', 'read'])) {
    $ch = new CombaHelper(null, $modx);
    $ch->setLogLevel(Entity::get('LOG_LEVEL'));
}

if ($action == 'readrequest') {
    $action = '_read';
    $ch->setOptions(
        [
            'pageid' => $ch->getCheckoutTnx(),
            'tnx' => true
        ]
    );
}

if ($action == 'read') {
    $action = '_read';
    $ch->setOptions('readOnly', true);
}

if ($action == '_read') {

    $parser = new Parser();
    $parser->addGlobal('page_checkout', Entity::get('PAGE_CHECKOUT'));
    $parser->addGlobal('page_checkout_tnx', Entity::get('PAGE_TNX'));

    $action = new ModxOperCart(null, $parser);
    $action->setLogLevel(Entity::get('LOG_LEVEL'));

    if (!empty($ch->getOptions('tnx'))) {
        $action = new ModxOperCartTnx(null, $parser);
        $action->setLogLevel(Entity::get('LOG_LEVEL'));
    }

    $action->setModx($modx);
    $parser->addGlobal('language', $action->detectLanguage());

    if (!empty($ch->getOptions('pageid'))) {
        $action->setOptions(
            [
                'id' => $ch->getOptions('pageid'),
                'readOnly' => true
            ]
        );
    }

    $action->setOptions(
        [
            'docTpl' => $docTpl ?? null,
            'docEmptyTpl' => $docEmptyTpl ?? null,
            'pagefull' => $pagefull
        ]
    );
    $out = $action->render();
}

return $out;
