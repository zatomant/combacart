<?php

namespace Comba\Bundle\Modx;

use Comba\Core\Entity;

class ModxProduct extends ModxOptions
{

    private array $_product;

    function countAvail($args = '1'): int
    {
        $i = 0;
        if (!empty($this->get())) {

            $ar = explode(',', $args);

            foreach ($this->get() as $item) {
                if (isset($item[Entity::get('TV_GOODS_AVAIL')])) {
                    if (in_array($item[Entity::get('TV_GOODS_AVAIL')], $ar)) {
                        $i++;
                    }
                }
            }
        }
        return $i;
    }

    public function get(): array
    {
        return $this->_product;
    }

    public function getPageInfo(array $data): array
    {
        if (empty($data['Document']['contentid'])) {
            return [];
        }

        $this->obtainFromModxObject($this->getModx()->getDocumentObject('id', $data['Document']['contentid'], 'all'))
            ->set(
                $this->filter('goods_md5', self::makeFakeUID([$data['Document']['contentid'], $data['Document']['article']]))
            );

        $goods = [];
        foreach ($this->get() as $v) {
            $goods = [
                'site_name' => $v['goods_name'],
                'site_article' => $v['goods_code'],
                'site_price' => $v['goods_price'],
                'site_avail' => $v['goods_avail']
            ];
        }
        $this->log('DEBUG', 'getPageInfo ' . serialize($goods));
        return $goods;
    }

    public function set(array $product): self
    {
        $this->_product = $product;
        return $this;
    }

    public function obtainFromModxObject(array $modxobject, bool $includeImageSPreset = false): self
    {
        unset($this->_product);
        $ret = !empty($modxobject) && !empty($modxobject[Entity::get('TV_GOODS_GOODS')][1]) ? $modxobject[Entity::get('TV_GOODS_GOODS')][1] : null;

        $fields = json_decode($ret, true);
        $i = !empty($fields['fieldValue']) ? count($fields['fieldValue']) : 0;

        $price = $modxobject[Entity::get('TV_GOODS_PRICE')][1] ?? null;
        $price_old = $modxobject[Entity::get('TV_GOODS_PRICE_OLD')][1] ?? null;

        // створюємо GoodsProduct якщо Entity::get('TV_GOODS_GOODS') не містить даних
        if (empty($ret) || strlen($ret) < 3 || $i == 0) {

            $image = ['image' => ''];

            $_images = !empty($modxobject[Entity::get('TV_GOODS_IMAGES')][1]) ? json_decode($modxobject[Entity::get('TV_GOODS_IMAGES')][1], true) : [];
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
            } elseif (!empty($modxobject[Entity::get('TV_GOODS_IMAGES')][1]) && is_string($modxobject[Entity::get('TV_GOODS_IMAGES')][1])) {
                $image['image'] = $modxobject[Entity::get('TV_GOODS_IMAGES')][1];
            }

            $avail = 0;
            if (!empty($modxobject[Entity::get('TV_GOODS_AVAIL')][1]) && $price > 0) {
                $avail = 1;
            }
            if (isset($modxobject[Entity::get('TV_GOODS_ISONDEMAND')][1]) && $modxobject[Entity::get('TV_GOODS_ISONDEMAND')][1] > 0 && $avail == 1) {
                $avail = 3;
            }

            $ondemand = $avail == 3 ? 1 : 0;

            $productData = [
                'goods_avail' => $avail,
                'goods_price' => $price,
                'goods_price_old' => $price_old,
                'goods_code' => !empty($modxobject[Entity::get('TV_GOODS_CODE')][1]) ? $modxobject[Entity::get('TV_GOODS_CODE')][1] : "",
                'goods_name' => $this->trim($modxobject['pagetitle']),
                'goods_name_long' => $this->trim($modxobject['longtitle']),
                'goods_weight' => !empty($modxobject[Entity::get('TV_GOODS_WEIGHT')][1]) ? $modxobject[Entity::get('TV_GOODS_WEIGHT')][1] : "",
                'goods_image' => $image['image'],
                'goods_url' => !empty($modxobject['alias']) ? filter_var($modxobject['alias'], FILTER_SANITIZE_URL) : "id=" . $modxobject['id'],
                'goods_desc' => $this->trim($modxobject['introtext']),
                'goods_ondemand' => $ondemand,
                'goods_seller' => !empty($modxobject[Entity::get('TV_GOODS_SELLER')][1]) ? $modxobject[Entity::get('TV_GOODS_SELLER')][1] : "",
                'goods_inbalances' => !empty($modxobject[Entity::get('TV_GOODS_INBALANCES')][1]) ? $modxobject[Entity::get('TV_GOODS_INBALANCES')][1] : "0",
                'contentid' => $modxobject['id'],
                'goods_md5' => self::makeFakeUID([$modxobject['id'], (!empty($modxobject[Entity::get('TV_GOODS_CODE')][1]) ? $modxobject[Entity::get('TV_GOODS_CODE')][1] : '')])
            ];

            if ($includeImageSPreset) {
                $productData['goods_image_ratio'] = [
                    'img16x9' => !empty($image['img16x9']) ? $image['img16x9'] : null,
                    'img4x3' => !empty($image['img4x3']) ? $image['img4x3'] : null,
                    'img1x1' => !empty($image['img1x1']) ? $image['img1x1'] : null,
                    'img2x3' => !empty($image['img2x3']) ? $image['img2x3'] : null
                ];
            }

            $this->_product[] = $productData;

        } else {

            foreach ($fields['fieldValue'] as $item) {

                if ($price > 0) {
                    if (empty($item[Entity::get('TV_GOODS_PRICE')]) || $item[Entity::get('TV_GOODS_PRICE')] <= 0) {
                        $item[Entity::get('TV_GOODS_PRICE')] = $price;
                    }
                } else {
                    $item[Entity::get('TV_GOODS_PRICE')] = 0;
                }

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
                if (isset($modxobject[Entity::get('TV_GOODS_ISONDEMAND')][1]) && $modxobject[Entity::get('TV_GOODS_ISONDEMAND')][1] > 0) {
                    if ($item[Entity::get('TV_GOODS_AVAIL')] == 1 || $item[Entity::get('TV_GOODS_AVAIL')] == 3) {
                        $item[Entity::get('TV_GOODS_AVAIL')] = 3;
                    }
                }
                if (empty($modxobject[Entity::get('TV_GOODS_AVAIL')][1]) || empty($price)) {
                    $item[Entity::get('TV_GOODS_AVAIL')] = 0;
                }

                $ondemand = $item[Entity::get('TV_GOODS_AVAIL')] == 3 ? 1 : 0;

                $item['goods_code'] = $item[Entity::get('TV_GOODS_CODE')];
                $item['goods_price'] = $item[Entity::get('TV_GOODS_PRICE')];
                $item['goods_price_old'] = $item[Entity::get('TV_GOODS_PRICE_OLD')];
                $item['goods_desc'] = $this->trim($modxobject['introtext']);
                $item['goods_url'] = !empty($modxobject['alias']) ? filter_var($modxobject['alias'], FILTER_SANITIZE_URL) : "id=" . $modxobject['id'];
                $item['goods_weight'] = $item[Entity::get('TV_GOODS_WEIGHT')];
                $item['goods_name_short'] = $this->trim($item[Entity::get('TV_GOODS_NAME')]);
                $item['goods_name'] = $this->trim($modxobject['pagetitle']) . ' ' . $this->trim($item[Entity::get('TV_GOODS_NAME')]);
                $item['goods_name_long'] = $this->trim($modxobject['longtitle']) . ' ' . $this->trim($item['goods_name_short']);
                $item['goods_ondemand'] = $ondemand;
                $item['goods_seller'] = $this->trim($item[Entity::get('TV_GOODS_SELLER')]);
                $item['goods_inbalances'] = $item[Entity::get('TV_GOODS_INBALANCES')] ?? 0;
                $item['contentid'] = $modxobject['id'];
                $item['goods_md5'] = self::makeFakeUID([$modxobject['id'], $item[Entity::get('TV_GOODS_CODE')]]);

                $this->_product[] = $item;
            }
        }

        return $this->setProp('site', filter_var(Entity::get('SERVER_NAME'), FILTER_SANITIZE_URL));
    }

    /**
     * sanitize string
     */
    private function trim(?string $str): string
    {
        return $str ? preg_replace('/[[:cntrl:]]/', '', htmlspecialchars(trim(str_replace(PHP_EOL, '', $str)))) : '';
    }

    /** Об'єднує елементи та створює псевдоUID
     * @param mixed $args
     * @return string
     */
    public static function makeFakeUID($args): string
    {
        if (is_string($args)) {
            return md5($args);
        }

        if (is_array($args)) {
            $str = !empty($args) ? implode('_', $args) : '';
            return md5($str);
        }

        return md5((string)$args);
    }

    public function setProp(string $key, $value): self
    {
        if (empty($this->get())) {
            return $this;
        }

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

        $ar = [];
        foreach ($product as $item) {
            if (in_array($item[$key], $value)) {
                $ar[] = $item;
            }
        }
        return $ar;
    }

    public function setAvailable(array $data, int $avail = 1): ?string
    {
        if (empty($data['Document']['contentid'])) {
            return null;
        }

        $res = null;
        $page = new ModxResource($this->getModx());
        $page->openForEdit($data['Document']['contentid']);
        $sku = $page->getTV(
            [
                'id' => $data['Document']['contentid'],
                'key' => Entity::get('TV_GOODS_CODE')
            ]
        );

        // Товар без опцій
        if ($sku && $data['Document']['article'] && $sku == $data['Document']['article']) {
            $page->set(
                [
                    'key' => Entity::get('TV_GOODS_AVAIL'),
                    'value' => $avail,
                    'id' => $data['Document']['contentid']
                ]
            );
            $page->save();
            $res = 'ok';
        } else {

            // Товар що має спискос варіантів комплектації чи властивостей
            $gg = $page->getTV(
                [
                    'id' => $data['Document']['contentid'],
                    'key' => Entity::get('TV_GOODS_GOODS')
                ]
            );
            if (!empty($gg) && strpos($gg, $data['Document']['article']) > 1) {

                $_gg = json_decode($gg, true);
                foreach ($_gg['fieldValue'] as $k => $v) {

                    // видаляємо все з mtvRender, бо це хрень
                    $v = array_filter($v, function ($key) {
                        return strpos($key, 'mtvRender') !== 0;
                    }, ARRAY_FILTER_USE_KEY);

                    if ($v[Entity::get('TV_GOODS_CODE')] == $data['Document']['article']) {
                        $v[Entity::get('TV_GOODS_AVAIL')] = $avail > 0 ? "$avail" : "";
                    }
                    $_gg['fieldValue'][$k] = $v;
                }

                $page->set(
                    [
                        'key' => Entity::get('TV_GOODS_GOODS'),
                        'value' => json_encode($_gg),
                        'id' => $data['Document']['contentid']
                    ]
                );
                $page->save();
                $res = 'ok';
            }
        }
        return $res;
    }

    public function prepareImages(array $data, bool $deleteOnly = false): void
    {
        $page = new ModxResource($this->getModx());
        $page->openForEdit($data['Document']['contentid']);

        // глобальні зображення товару
        $images = $page->getTV(
            [
                'id' => $data['Document']['contentid'],
                'key' => Entity::get('TV_GOODS_IMAGES')
            ]
        );

        if (empty($images)) {
            return;
        }

        if (preg_match('/\bfieldValue\b/', $images)) {
            // MultiTV-об’єкт
            $images = json_decode($images, true);
            $images = $images['fieldValue'] ?? [];
        } else {
            // Просто рядок
            $images = ['fields' => ['image' => $images]];
        }

        $images = json_encode($images);
        if ($deleteOnly) {
            $this->deleteImages($images);
        } else {
            $this->createImages($images);
        }
        unset($images);

        // індивідуальні зображення товару
        $images = $page->getTV(
            [
                'id' => $data['Document']['contentid'],
                'key' => Entity::get('TV_GOODS_GOODS')
            ]
        );
        if (preg_match('/\bfieldValue\b/', $images)) {
            $images = json_encode($images);
            if ($deleteOnly) {
                $this->deleteImages($images['fieldValue']);
            } else {
                $this->createImages($images['fieldValue']);
            }
        }
    }

    private function deleteImages(string $object): void
    {
        $items = !empty($object) ? json_decode($object, true) : [];
        if (empty($items)) {
            return;
        }

        $mi = new ModxImage($this->getModx());

        foreach ($items as $item) {
            if (empty($item['image'])) {
                continue;
            }
            $this->log('DEBUG', 'deleteImages ' . $item['image']);
            $mi->deleteCacheVariants($item['image']);
        }
    }

    private function createImages(string $object): void
    {
        $items = !empty($object) ? json_decode($object, true) : [];
        if (empty($items)) {
            return;
        }

        // дістємо предналаштовані назви схем розмірів
        $prev = array_keys(Entity::getData('Imagepresets'));
        $mi = new ModxImage($this->getModx());

        foreach ($items as $item) {

            if (empty($item['image'])) {
                continue;
            }

            $this->log('DEBUG', 'createImages ' . $item['image']);

            // для кожного зображення свій масив розмірів
            $goods_image_ratios = json_encode(
                [
                    'img16x9' => str_replace(['x:', 'y:', 'width:', 'height:', ':', ','], ['sx=', 'sy=', 'sw=', 'sh=', '=', '&'], $item['img16x9'] ?? ''),
                    'img4x3' => str_replace(['x:', 'y:', 'width:', 'height:', ':', ','], ['sx=', 'sy=', 'sw=', 'sh=', '=', '&'], $item['img4x3'] ?? ''),
                    'img1x1' => str_replace(['x:', 'y:', 'width:', 'height:', ':', ','], ['sx=', 'sy=', 'sw=', 'sh=', '=', '&'], $item['img1x1'] ?? ''),
                    'img2x3' => str_replace(['x:', 'y:', 'width:', 'height:', ':', ','], ['sx=', 'sy=', 'sw=', 'sh=', '=', '&'], $item['img2x3'] ?? '')
                ]
            );

            // створюємо зображення:
            // передаємо шлях до початкового файлу,
            // схему розмірів,
            // параметри пропорцій розмірів зображення
            // forced = 1 : примусово видалити старі файли
            foreach ($prev as $opt) {
                $mi->get(
                    [
                        'src' => $item['image'],
                        'preset' => $opt,
                        'imgratio' => $goods_image_ratios,
                        'flag' => 'forced'
                    ]
                );
            }
        }

    }
}
