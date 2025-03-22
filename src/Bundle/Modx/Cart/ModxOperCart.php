<?php

namespace Comba\Bundle\Modx\Cart;

use Comba\Bundle\CombaHelper\CombaHelper;
use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxOper;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Bundle\Modx\ModxProduct;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxSeller;
use Comba\Core\Entity;


//require_once MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php';

class ModxOperCart extends ModxOper
{

    public function addPath(): ModxOperCart
    {
        return $this->addPathLoader(dirname(__FILE__) . '/templates');
    }

    public function setAction(): string
    {
        return 'cart';
    }

    public function render()
    {

        $ch = new CombaHelper($this->getModx());
        $ch->setTemplatesPath(str_replace(getenv('DOCUMENT_ROOT'), '', dirname(__FILE__)) . '/templates/');

        $Cart = new ModxCart($this->getModx());
        if (!empty($this->getOptions('id'))) {
            $Cart->setID($this->getOptions('id'));
        }
        $doc = $Cart->get();

        $docTpl = $this->getOptions('docTpl');
        $docEmptyTpl = $this->getOptions('docEmptyTpl');
        $currency = $this->getModx()->getPlaceholder('currency');

        if (empty($doc) && !$ch->isBot()) {
            $ch->log('Cart ID empty', LOG_NOTICE)->log('tpl ' . $docTpl, LOG_NOTICE)->log('IP ' . $ch->getIpAddr(), LOG_NOTICE);
            $ch->log(htmlspecialchars(filter_var($this->getAgent(), FILTER_SANITIZE_ENCODED)), LOG_NOTICE);
        }

        $goods_count = 0;
        $this->getModx()->tpl = \DLTemplate::getInstance($this->getModx());

        if (!empty($doc)) {// && $doc['doc_status'] != 4) {

            if (!empty($doc['specs'])) {

                $product = new ModxProduct($this->getModx());

                $doc['doc_sum'] = 0;
                foreach ($doc['specs'] as &$row) {
                    $row['currency'] = $currency;
                    $row['lguid'] = md5($row['docspec_product_sku']);

                    if (Entity::SELLER_SHOW) {
                        $_seller = (new ModxSeller())->setUID($row['docspec_seller'])->get();
                        $row['seller_label'] = $_seller['label'] ?? '';
                    }

                    // prepare images ratio and Available status
                    $docid = intval($row['docspec_product_code']);

                    if (!empty($docid) && !empty($gp = $product->obtainFromModxObject($this->getModx()->getDocumentObject('id', $docid), true)->get())) {

                        foreach ($gp as $item) {
                            $img16x9 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['goods_image_ratio']['img16x9']);
                            $img4x3 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['goods_image_ratio']['img4x3']);
                            $img1x1 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['goods_image_ratio']['img1x1']);
                            $img2x3 = str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['goods_image_ratio']['img2x3']);

                            $row['docspec_image_ratios'] = json_encode(
                                array(
                                    'img16x9' => $img16x9,
                                    'img4x3' => $img4x3,
                                    'img1x1' => $img1x1,
                                    'img2x3' => $img2x3
                                )
                            );
                            if ($row['docspec_product_sku'] == $item['goods_code']) {
                                if (empty($item['goods_avail'])) $row['docspec_avail'] = 0;
                                // $row['docspec_avail'] = empty($item->goods_avail) ? 0 : 1;
                            }
                        }

                    }

                    if (!empty($row['ref_available'])) {
                        if (!empty($row['docspec_price']) && !empty($row['docspec_amount'])) {
                            $val_price = $row['docspec_price'];
                            $val_amount = intval($row['docspec_amount']);

                            $row['docspec_price'] = number_format($val_price, 2, '.', '');
                            $row['docspec_amount'] = $val_amount;
                            $doc['doc_sum'] += $val_amount * $val_price;

                            $goods_count += $val_amount;
                        }
                    }
                    $row['docspec_measure'] = 'шт';
                }
            }
        }

        if ($goods_count == 0) {
            $doc['doc_sum'] = 0;
            $docTpl = $docEmptyTpl;
        }

        if (!is_numeric($doc['doc_sum'])) {
            $doc['doc_sum'] = 0;
        }
        $doc['doc_sum'] = number_format($doc['doc_sum'], 2, '.', '');
        $doc['goods_count'] = $goods_count;
        $doc['currency'] = $currency;

        if (Entity::ORDER_SEPARATE_BY_SELLERS) {
            $doc['show_separate'] = true;
        }

        $this->initLang();

        $marketplace = (new ModxMarketplace())->get();
        $_t = $this->getParser()
            ->getEngine()
            ->createTemplate($this->getModx()->tpl->parseChunk($ch->getChunk($docTpl), $doc, true));

        $payment = json_decode(
            (new ModxOptions($this->getModx()))
                ->setCachable()
                ->request('PaymentList', ['Seller' => $marketplace['uid']]),
            true);

        $delivery = json_decode(
            (new ModxOptions($this->getModx()))
                ->setCachable()
                ->request('DeliveryList', ['Seller' => $marketplace['uid']]),
            true);

        $_recaptcha = Entity::get3thAuth('reCaptcha', 'marketplace');

        return $this->getModx()
            ->tpl
            ->parseChunk('@CODE:' . $_t->render(
                    [
                        'doc' => $doc,
                        'marketplace' => [
                            'links' => $marketplace['links'] ?? null,
                            'contact' => $marketplace['contact'] ?? null,
                            'regime' => $marketplace['regime'] ?? null,
                        ],
                        'reCaptchaKey' => !empty($_recaptcha['key']) ?? null,
                        'payment' => !empty($payment['Document']) ? $payment['Document'] : null,
                        'delivery' => !empty($delivery['Document']) ? $delivery['Document'] : null
                    ]
                ), $doc, true);
    }
}
