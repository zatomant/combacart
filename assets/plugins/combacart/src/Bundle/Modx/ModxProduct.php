<?php

namespace Comba\Bundle\Modx;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use const GOODS_CODE;
use const GOODS_GOODS;
use const GOODS_IMAGES;
use const GOODS_ISONDEMAND;
use const GOODS_PRICE;
use const GOODS_PRICE_OLD;
use const GOODS_WEIGHT;
use const GOODS_AVAIL;

use modResource;

include_once(MODX_BASE_PATH . "assets/lib/MODxAPI/modResource.php");

class ModxProduct extends ModxOptions
{

    private array $_product;

    function countAvail($args = '1'): int
    {
        $i = 0;
        if (!empty($this->get())) {

            $ar = explode(',', $args);

            foreach ($this->get() as $item) {
                if (isset($item[GOODS_AVAIL])) {
                    if (in_array($item[GOODS_AVAIL], $ar)) $i++;
                }
            }
        }
        return $i;
    }

    public function get(): array
    {
        return $this->_product;
    }

    public function getPageInfo($data): array
    {
        if (empty($data->Document->contentid)) {
            return array();
        }

        $this->obtainFromModxObject($this->getModx()->getDocumentObject('id', $data->Document->contentid, 'all'))
            ->set($this->filter('goods_md5', md5($data->Document->article)));

        $goods = array();
        foreach ($this->get() as $v) {
            $goods = [
                'site_name' => $v['goods_name'],
                'site_article' => $v['goods_code'],
                'site_price' => $v['goods_price'],
                'site_avail' => $v['goods_avail']
            ];
        }
        $this->log(serialize($goods),LOG_DEBUG);
        return $goods;
    }

    public function set(array $product): ModxProduct
    {
        $this->_product = $product;
        return $this;
    }

    public function obtainFromModxObject(array $modxobject, bool $includeImageSPreset = false): ModxProduct
    {
        unset($this->_product);
        $ret = !empty($modxobject) && !empty($modxobject[GOODS_GOODS][1]) ? $modxobject[GOODS_GOODS][1] : null;

        $i = 0;
        $fields = json_decode($ret, true);
        if (!empty($fields['fieldValue'])) {
            foreach ($fields['fieldValue'] as $item) $i++;
        }

        $price = $modxobject[GOODS_PRICE][1] ?? null;
        $price_old = $modxobject[GOODS_PRICE_OLD][1] ?? null;

        // створюємо GoodsProduct якщо GOODS_GOODS не містить даних
        if (empty($ret) || strlen($ret) < 3 || $i == 0) {

            $image = array('image' => '');

            $_images = !empty($modxobject[GOODS_IMAGES][1]) ? json_decode($modxobject[GOODS_IMAGES][1], true) : array();
            if (!empty($_images['fieldValue'])) {
                foreach ($_images['fieldValue'] as $item) {
                    // враховуємо лише перше зображення
                    if (isset($item['image'])) {
                        $image['image'] = $item['image'];

                        $image['img16x9'] = $item['img16x9'] ?? null;
                        $image['img4x3'] = $item['img4x3'] ?? null;
                        $image['img1x1'] = $item['img1x1'] ?? null;
                        $image['img2x3'] = $item['img2x3'] ?? null;

                        break;
                    }
                }
            } elseif (!empty($modxobject[GOODS_IMAGES][1]) && is_string($modxobject[GOODS_IMAGES][1])) {
                $image['image'] = $modxobject[GOODS_IMAGES][1];
            }

            $avail = 0;
            if (!empty($modxobject[GOODS_AVAIL][1]) && $price > 0) $avail = 1;
            if (isset($modxobject[GOODS_ISONDEMAND][1]) && $modxobject[GOODS_ISONDEMAND][1] > 0 && $avail == 1) $avail = 3;

            $ondemand = $avail == 3 ? 1 : 0;

            // create string in json format
            $ret =
                '{
            "goods_avail":"' . $avail . '",
            "goods_price":"' . $price . '",
            "goods_price_old":"' . $price_old . '",
            "goods_code":"' . (!empty($modxobject[GOODS_CODE][1]) ? $modxobject[GOODS_CODE][1] : "") . '",
            "goods_name":"' . $this->trim($modxobject['pagetitle']) . '",
            "goods_name_long":"' . $this->trim($modxobject['longtitle']) . '",
            "goods_weight":"' . (!empty($modxobject[GOODS_WEIGHT][1]) ? $modxobject[GOODS_WEIGHT][1] : "") . '",
            "goods_image":"' . $image['image'] . '",
            "goods_url":"' . (!empty($modxobject['alias']) ? filter_var($modxobject['alias'], FILTER_SANITIZE_URL) : "id=" . $modxobject['id']) . '",
            "goods_desc":"' . $this->trim($modxobject['introtext']) . '",
            "goods_ondemand":"' . $ondemand . '",
            "goods_seller":"' . (!empty($modxobject[GOODS_SELLER][1]) ? $modxobject[GOODS_SELLER][1] : "") . '",
            "goods_inbalances":"' . (!empty($modxobject[GOODS_INBALANCES][1]) ? $modxobject[GOODS_INBALANCES][1] : "0") . '",
            "contentid":"' . $modxobject['id'] . '",
            "goods_md5":"' . md5($modxobject[GOODS_CODE][1]) . '"';

            if ($includeImageSPreset) {
                $ret .= ',
                "goods_image_ratio" : ' . json_encode(
                        [
                            "img16x9" => !empty($image['img16x9']) ? $image['img16x9'] : null,
                            "img4x3" => !empty($image['img4x3']) ? $image['img4x3'] : null,
                            "img1x1" => !empty($image['img1x1']) ? $image['img1x1'] : null,
                            "img2x3" => !empty($image['img2x3']) ? $image['img2x3'] : null
                        ]
                    );
            }

            $ret .= '
            }';

            // sanitize
            $ret = preg_replace('/[[:cntrl:]]/', '', $ret);
            $this->_product[] = json_decode($ret, true);
            // echo 'error: ', json_last_error_msg(), PHP_EOL, PHP_EOL;

        } else {

            foreach ($fields['fieldValue'] as $item) {

                if ($price > 0) {
                    if (empty($item[GOODS_PRICE]) || $item[GOODS_PRICE] <= 0) $item[GOODS_PRICE] = $price;
                } else $item[GOODS_PRICE] = 0;

                //if ($k->goods_avail == '' || $k->goods_avail == 0) $k->goods_price = 0;

                if (!empty($item['image'])) {
                    $item['goods_image'] = $item['image'];
                    unset($item['image']);

                    if ($includeImageSPreset) {
                        $item['goods_image_ratio'] = json_encode(
                            [
                                "img16x9" => !empty($item['img16x9']) ? $item['img16x9'] : null,
                                "img4x3" => !empty($item['img4x3']) ? $item['img4x3'] : null,
                                "img1x1" => !empty($item['img1x1']) ? $item['img1x1'] : null,
                                "img2x3" => !empty($item['img2x3']) ? $item['img2x3'] : null
                            ]
                        );
                    }

                }
                if (isset($modxobject[GOODS_ISONDEMAND][1]) && $modxobject[GOODS_ISONDEMAND][1] > 0) {
                    if ($item[GOODS_AVAIL] == 1 || $item[GOODS_AVAIL] == 3) {
                        $item[GOODS_AVAIL] = 3;
                    }
                }
                if (empty($modxobject[GOODS_AVAIL][1]) || empty($price)) {
                    $item[GOODS_AVAIL] = 0;
                }

                $ondemand = $item[GOODS_AVAIL] == 3 ? 1 : 0;

                $item['goods_code'] = $item[GOODS_CODE];
                $item['goods_price'] = $item[GOODS_PRICE];
                $item['goods_price_old'] = $item[GOODS_PRICE_OLD];
                $item['goods_desc'] = $this->trim($modxobject['introtext']);
                $item['goods_url'] = !empty($modxobject['alias']) ? filter_var($modxobject['alias'], FILTER_SANITIZE_URL) : "id=" . $modxobject['id'];
                $item['goods_weight'] = $item[GOODS_WEIGHT];
                $item['goods_name_short'] = $this->trim($item[GOODS_NAME]);
                $item['goods_name'] = $this->trim($modxobject['pagetitle']) . ' ' . $this->trim($item[GOODS_NAME]);
                $item['goods_name_long'] = $this->trim($modxobject['longtitle']) . ' ' . $this->trim($item['goods_name_short']);
                $item['goods_ondemand'] = $ondemand;
                $item['goods_seller'] = $this->trim($item[GOODS_SELLER]);
                $item['goods_inbalances'] = $item[GOODS_INBALANCES] ?? 0;
                $item['contentid'] = $modxobject['id'];
                $item['goods_md5'] = md5($item[GOODS_CODE]);

                $this->_product[] = $item;
            }
        }

        return $this->setProp('site', filter_var(COMBAMODX_SERVER_NAME, FILTER_SANITIZE_URL));
    }

    /**
     * sanitize string
     *
     */
    private function trim(?string $str): string
    {
        return $str ? htmlspecialchars(trim(str_replace(PHP_EOL, '', $str))) : '';
    }

    public function setProp(string $key, $value): ModxProduct
    {
        if (empty($this->get())) return $this;

        $_product = $this->get();
        foreach ($_product as $k => $item) {
            $_product[$k][$key] = $value;
        }
        return $this->set($_product);
    }

    public function filter(string $key, $value, array $product = null): array
    {
        $product = $product ?: $this->get();
        if (empty($key) || empty($value)) {
            return $product;
        }

        if (!is_array($value)) {
            $value = explode(';', $value);
        }

        $ar = array();
        foreach ($product as $item) {
            if (in_array($item[$key], $value)) {
                $ar[] = $item;
            }
        }
        return $ar;
    }

    public function setAvailable($data, int $avail = 1): ?string
    {
        $res = null;
        if (empty($data->Document->contentid)) {
            return $res;
        }

        $page = new modResource($this->getModx());
        $page->edit($data->Document->contentid);
        $sku = $page->get(GOODS_CODE);

        // Товар без списка варіантів
        if ($sku && $data->Document->article && $sku == $data->Document->article) {
            $page->set(GOODS_AVAIL, $avail);
            $page->save();
            $res = 'ok';
        } else {

            // Товар що має спискос варіантів комплектації чи властивостей
            $gg = $page->get(GOODS_GOODS);
            if (!empty($gg) && strpos($gg, $data->Document->article) > 1) {

                $_gg = json_decode($gg, true);
                foreach ($_gg['fieldValue'] as $k => $v) {

                    // видаляємо все з mtvRender, бо це хрень
                    $v = array_filter($v, function ($key) {
                        return strpos($key, 'mtvRender') !== 0;
                    }, ARRAY_FILTER_USE_KEY);

                    if ($v[GOODS_CODE] == $data->Document->article) {
                        $v[GOODS_AVAIL] = $avail > 0 ? "$avail" : "";
                    }
                    $_gg['fieldValue'][$k] = $v;
                }

                $page->set(GOODS_GOODS, json_encode($_gg));
                $page->save();
                $res = 'ok';
            }
        }
        return $res;
    }

    public function prepareImages($data): void
    {
        $page = new modResource($this->getModx());
        $page->edit($data->Document->contentid);

        // глобальні зображення товару
        $images = $page->get(GOODS_IMAGES);
        if (!empty($images['fieldValue'])) $this->createImages($images['fieldValue']);
        unset($images);

        // індивідуальні зображення товару
        $images = $page->get(GOODS_GOODS);
        if (!empty($images['fieldValue'])) $this->createImages($images['fieldValue']);
    }

    private function createImages(string $object): void
    {
        $items = !empty($object) ? json_decode($object, true) : array();

        // предналаштовані назви схем розмірів
        // налаштування схем зберігається в адмінці в дереві документів
        $prev = [
            'checkout-goods',
            'page-goods-top',
            'cart-goods',
            'catalog-goods',
            'goods-slider'
        ];

        if (!empty($items)) {
            foreach ($items as $item) {

                if (!empty($item['image'])) {

                    $this->log($item['image'],LOG_DEBUG);

                    // для кожного зображення може бути свій масив розмірів
                    $goods_image_ratios = json_encode(
                        array(
                            'img16x9' => str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['img16x9'] ?? ''),
                            'img4x3' => str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['img4x3'] ?? ''),
                            'img1x1' => str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['img1x1'] ?? ''),
                            'img2x3' => str_replace(array(':', 'x', 'y', 'width', 'height'), array('=', 'sx', 'sy', 'sw', 'sh'), $item['img2x3'] ?? '')
                        )
                    );

                    // створюємо зображення:
                    // передаємо в сніпет шлях до початкового файлу,
                    // назву схеми розмірів,
                    // параметри пропорцій розмірів зображення
                    // force = 1 : примусово видалити старі файли
                    $image = new ModxImage($this->getModx());
                    foreach ($prev as $opt) {
                        $image->getImage(array(
                                'src' => $item['image'],
                                'preset' => $opt,
                                'imgratio' => $goods_image_ratios,
                                'force' => 1
                            )
                        );
//                        $this->getModx()->runSnippet('GetImage',
//                            array(
//                                'img' => $item['image'],
//                                'options' => $opt,
//                                'imgratio' => $goods_image_ratios,
//                                'force' => 1
//                            )
//                        );
                    }
                }
            }
        }
    }

}
