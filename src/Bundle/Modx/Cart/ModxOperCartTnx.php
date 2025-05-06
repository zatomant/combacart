<?php

namespace Comba\Bundle\Modx\Cart;

use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxMarketplace;
use Comba\Bundle\Modx\ModxProduct;
use Comba\Bundle\Modx\ModxSeller;
use Comba\Core\Entity;
use function Comba\Functions\filterArrayRecursive;

class ModxOperCartTnx extends ModxOperCart
{
    public function render()
    {

        $pagefull = $this->getOptions('pagefull') ? 'pagefull_' : '';
        $docTpl = $this->getOptions('docTpl');
        $docEmptyTpl = $this->getOptions('docEmptyTpl');

        if (!empty($pagefull)) {
            $docTpl = (strpos($docTpl, '@FILE:/') === 0)
                ? '@FILE:/' . $pagefull . substr($docTpl, 7)
                : $docTpl;
            $docEmptyTpl = (strpos($docEmptyTpl, '@FILE:/') === 0)
                ? '@FILE:/' . $pagefull . substr($docEmptyTpl, 7)
                : $docTpl;
        }

        $currency = $this->getModx()->getPlaceholder('currency');

        $this->getModx()->tpl = \DLTemplate::getInstance($this->getModx());
        $this->initLang();

        $docs = [];

        $uids = $this->getOptions('id');
        if (!empty($uids)) {
            foreach ($uids as $uid) {

                $cart = new ModxCart($this);
                if (!empty($this->getOptions('id'))) {
                    $cart->setID($uid);
                }
                $doc = $cart->get();

                if (empty($doc) && !$this->isBot()) {
                    $this->log('ERROR', 'Cart ID empty', [
                            'tpl' => $docTpl,
                            'IP' => $this->getIpAddr(),
                            'agent' => htmlspecialchars(filter_var($this->getAgent(), FILTER_SANITIZE_ENCODED))
                        ]
                    );
                }

                $goods_count = 0;

                if (!empty($doc)) {

                    if (!empty($doc['specs'])) {

                        $product = new ModxProduct($this);

                        $doc['doc_sum'] = 0;
                        foreach ($doc['specs'] as &$row) {
                            $row['currency'] = $currency;

                            if (Entity::get('SELLER_SHOW_LABEL')) {
                                $_seller = (new ModxSeller($this))->setUID($row['docspec_seller'])->get();
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
                                        [
                                            'img16x9' => $img16x9,
                                            'img4x3' => $img4x3,
                                            'img1x1' => $img1x1,
                                            'img2x3' => $img2x3
                                        ]
                                    );
                                    if ($row['docspec_product_sku'] == $item['goods_code']) {
                                        if (empty($item['goods_avail'])) {
                                            $row['docspec_avail'] = 0;
                                        }
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

                                    $row['docspec_measure'] = 'шт';

                                    $goods_count += $val_amount;
                                }
                            }
                        }
                    }
                }

                if ($goods_count == 0) {
                    $doc['doc_sum'] = 0;
                    //$docTpl = $docEmptyTpl;
                }

                if (!is_numeric($doc['doc_sum'])) {
                    $doc['doc_sum'] = 0;
                }

                $doc['doc_sum'] = number_format($doc['doc_sum'], 2, '.', '');
                $doc['goods_count'] = $goods_count;
                $doc['currency'] = $currency;

                //$doc['goods_plurals'] = $this->getModx()->config['__goods_plurals'];

                $docs[] = $doc;
            }
        }
        if (empty($docs)) {
            $docTpl = $docEmptyTpl;
        }

        $marketplace = filterArrayRecursive((new ModxMarketplace($this))->get(), null, ['uid', 'sellers']);

        $_t = $this->getParser()
            ->getEngine()
            ->createTemplate($this->getChunk($docTpl));

        // виклик обробки шаблону для twig
        $_chunk = $_t->render(
            [
                'docs' => $docs,
                'marketplace' => $marketplace,
            ]
        );

        // виклик обробки шаблону для modx
        return
            $this->getModx()
                ->tpl
                ->parseChunk($_chunk, $docs, true);
    }
}
