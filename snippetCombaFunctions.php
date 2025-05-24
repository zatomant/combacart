<?php

/**
 * CombaFunction
 *
 * function for prepare read page
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
use Comba\Bundle\Modx\ModxImage;
use Comba\Bundle\Modx\ModxProduct;
use Comba\Bundle\Modx\ModxSeller;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxUser;
use Comba\Bundle\Modx\Payment\ModxOperPayment;
use Comba\Bundle\Modx\Tracking\ModxOperTrackingExt;
use Comba\Core\Entity;
use function Comba\Functions\safeHTML;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
require_once __DIR__ . '/autoload.php';

if (empty($fnct)) {
    return;
}

$out = null;

if (preg_match('/\bgoodslguid\b/', $fnct)) {
    $out = ModxProduct::makeFakeUID($string);
}

if (preg_match('/\bbuttonbuy\b/', $fnct)) {
    if (empty($avail) || $avail < 1 || empty($price)) {
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

if (preg_match('/\bis\b/', $fnct)) {
    $values = explode(';', $cond);
    if (count($values) === count(array_filter($values, fn($value) => $value !== '' && $value > 0))) {
        $out = $then;
    }
}

if (preg_match('/\bgetSellers\b/', $fnct)) {
    $_out = '';
    $sellers_list = (new ModxMarketplace())
        ->setLogLevel(Entity::get('LOG_LEVEL'))
        ->sellers();

    foreach ($sellers_list as $el) {
        $seller = (new ModxSeller())
            ->setLogLevel(Entity::get('LOG_LEVEL'))
            ->setUID($el['uid'])->get();

        if (!empty($seller['uid'])) {
            if (isset($asArray)) {
                $_out_ar[] = ['label' => $seller['label'], 'uid' => $seller['uid']];
            } else {
                $_out .= $seller['label'] . '==' . $seller['uid'] . '||';
            }
        }
    }
    $_out = !empty($_out) ? substr($_out, 0, -2) : '';
    $out .= $_out;
}

if (preg_match('/\bshowSeller\b/', $fnct) && Entity::get('SELLER_SHOW')) {
    $sellers_list = (new ModxMarketplace())
        ->setLogLevel(Entity::get('LOG_LEVEL'))
        ->sellers($seller);

    foreach ($sellers_list as $el) {
        $seller = (new ModxSeller())
            ->setLogLevel(Entity::get('LOG_LEVEL'))
            ->setUID($el['uid'])->get();

        if (!empty($seller['uid'])) {
            $out = '<span class="small">[(__seller)]: ' . $seller['label'] . '</span>';
            break;
        }
    }
    return $out;
}

if (preg_match('/\bGetImage\b/', $fnct)) {
    return (new ModxImage($modx))->getImage($modx->event->params);
}

if (preg_match('/\bOrderPay\b/', $fnct)) {

    if (empty($uid)) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $pagePayment = Entity::get('PAGE_PAYMENT');
        $uid = !empty($_GET[$pagePayment]) ? safeHTML($_GET[$pagePayment]) : parse_url(safeHTML($requestUri), PHP_URL_QUERY);
    }

    $action = new ModxOperPayment();
    $action->setLogLevel(Entity::get('LOG_LEVEL'))
        ->setModx($modx)
        ->detectLanguage();

    $out = $action->setOptions(
        [
            'uid' => $uid ?? null,
            'pagefull' => $pagefull ?? null
        ]
    )->render();
}

if (preg_match('/\bOrderTracking\b/', $fnct)) {

    if (empty($uid)) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $pageTracking = Entity::get('PAGE_TRACKING');
        $uid = !empty($_GET[$pageTracking]) ? safeHTML($_GET[$pageTracking]) : parse_url(safeHTML($requestUri), PHP_URL_QUERY);
    }

    $action = new ModxOperTrackingExt();
    $action->setLogLevel(Entity::get('LOG_LEVEL'))
        ->setModx($modx)
        ->detectLanguage();

    $out = $action->setOptions(
        [
            'trk' => $uid ?? null,
            'pagefull' => $pagefull ?? null
        ]
    )->render();
}

if (preg_match('/\bCheckoutTnx\b/', $fnct)) {

    $params = [
        'action' => 'readrequest',
        'docTpl' => '@FILE:/chunk_checkout_tnx',
        'docEmptyTpl' => '@FILE:/chunk_checkout_tnx_empty',
        'pagefull' => $pagefull ?? null
    ];
    echo $modx->runSnippet('CombaHelper', $params);

    $_log_level = Entity::get('LOG_LEVEL');
    $ch = new CombaHelper(null, $modx);
    $ch->setLogLevel($_log_level);
    if ($ch->getCheckoutTnx()) {
        // видаляємо з кешу документів старий uid
        $ch->invalidateCache(
            (new ModxUser(null, $modx))
                ->setLogLevel($_log_level)
                ->getSession()
        );
    }
}

if (preg_match('/\bregister\b/', $fnct)) {
    if (!empty($modx->userLoggedIn())) {
        $modx->sendRedirect('/' . Entity::get('PAGE_SINGUP'));
        return;
    }
}

if (preg_match('/\bcabinet\b/', $fnct)) {

    $user = $modx->userLoggedIn();
    if (empty($user) && Entity::get('CABINET_GUEST_MODE_ENABLED') == false) {
        $modx->sendRedirect('/' . Entity::get('PAGE_SINGIN'));
        return;
    }

    $cabinet = new ModxOperCabinet();
    $cabinet->setLogLevel(Entity::get('LOG_LEVEL'))
        ->setModx($modx)
        ->setOptions([
            'page' => $page ?? null
        ])
        ->detectLanguage();
    $out = $cabinet->render();
}


return $out;
