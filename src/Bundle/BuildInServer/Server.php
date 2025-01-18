<?php

namespace Comba\Bundle\BuildInServer;

use Comba\Bundle\Modx\ModxOptions;
use Comba\Core\Entity;
use function Comba\Functions\array_sort;
use function Comba\Functions\sanitizeID;
use function Comba\Functions\array_search_by_key;
use function Comba\Functions\filterArrayRecursive;
use function Comba\Functions\recursive_array_search_key_value;


class Server extends ModxOptions
{

    /**
     * Цей клас НЕ використовує базу даних
     * і інформація зберігається в файловому кеші згідно з налаштуваннями ServerCache->lifetime
     *
     * Методи класу дозволяють працювати CombaCart за відсутності реєстрації на Comba сервері
     *
     */

    public ServerCache $serverCache;
    private int $_paging_length = 50;

    public function __construct($modx = null)
    {
        parent::__construct($modx);
        $this->serverCache = new ServerCache();
    }

    public function setCacheLifeTime(int $value = 0): Server
    {
        $this->serverCache->setLifetime($value);
        return $this;
    }

    /**
     * Повертає UID Кошика за id сесії
     * @param string $session
     * @return string|null
     */
    public function documentgetcurrentid(string $session): ?string
    {
        $ret = null;
        $_doc = $this->doCache('DocumentGetCurrentId_' . $session);

        if (!empty($_d = $_doc->get())) {
            $_ret = json_decode($_d, true);

            $ret = $_ret['Document']['uid'];
            if (empty($this->documentread($ret))) {
                $ret = $this->documentcreate($session);
            }
        }

        return $ret;
    }

    /** Встановити назву кешу
     * @param string $method
     * @return ServerCache
     */
    public function doCache(string $method): ServerCache
    {
        return $this->serverCache->setFilename($method);
    }

    /**
     * Повертає весь вміст Документу (Кошик|Замовлення)
     * @param string $uid
     * @param string $type
     * @return array
     */
    public function documentread(string $uid, string $type = ''): array
    {
        $_d = $this->doCache('Document' . $type . '_' . $uid)
            ->get();
        return !empty($_d) ? json_decode($_d, true) : [];
    }

    /**
     * Створює новий Кошик та повертає його UID
     * @param string $ses
     * @param array|null $data
     * @param string $type
     * @return string
     */
    public function documentcreate(string $ses, array $data = null, string $type = ''): string
    {
        $ses = sanitizeID($ses);
        $uid = $this->createUniqueCode();

        if (!$data) {

            $_s = $this->doCache('DocumentGetCurrentId_' . $ses);
            $_s->set(json_encode(array(
                        "result" => "ok",
                        'Document' => ['uid' => $uid]
                    )
                )
            );

            $data = array(
                "result" => "ok",
                'Document' => [
                    "session" => $ses,
                    "doc_uid" => $uid,
                    "doc_date_create" => date("Y-m-d H:i:s:u"),
                    "doc_type" => "doc_cart",
                    "doc_status" => 1,
                    "doc_sum" => 0.0000,
                    'doc_marketplace' => array_search_by_key($this->marketplace(), 'uid')
                ]
            );
        } else {
            $uid = $ses;
        }

        $_doc = $this->doCache('Document' . $type . '_' . $uid);
        $_doc->set(json_encode($data));

        return $uid;
    }

    /**
     * Дані по Маркетплейсу
     * @param string|null $uid
     * @return array
     */
    public function marketplace(string $uid = null): array
    {
        $m = Entity::getData('Marketplace');
        return $uid ? array_search_by_key($m, 'uid') == $uid ? $m : [] : $m;
    }

    /**
     * Оновлення кількості у Кошику по UID специфікації
     * @param array $data
     * @return string|null
     */
    public function documentspecupdate(array $data): ?string
    {
        $d_uid = $data['Document']['uid'];
        $s_uid = sanitizeID($data['Document']['specid']);
        $amount = (int)$data['Document']['amount'];

        $_doc = $this->documentread($d_uid);

        if (empty(($_doc['Document']['doc_uid'])) || empty($s_uid)) {
            return null;
        }

        $_specs = $_doc['Document']['specs'] ?? [];
        if ($_specs) {
            foreach ($_specs as $k => $spec) {
                if ($spec['docspec_uid'] == $s_uid) {
                    $_specs[$k]['docspec_amount'] = $amount ?? 1;
                    $_specs[$k]['docspec_sum'] = $spec['docspec_price'] * $_specs[$k]['docspec_amount'];
                    break;
                }
            }
        }

        $_doc['Document']['specs'] = array_sort($_specs, 'docspec_seller');

        return $this->documentcreate($d_uid, $_doc);
    }

    /**
     * Додае нову позицію в специфікацію Кошика, або збільшує кількість наявної якщо вже присутня
     * @param array $data
     * @return string|null
     */
    public function documentspecinsert(array $data): ?string
    {
        $d_uid = $data['Document']['uid'];

        $_doc = $this->documentread($d_uid);
        if (empty(($_doc['Document']['doc_uid']))) {
            return null;
        }

        $amount = (int)$data['Document']['amount'];

        $bNewRow = true;
        $_specs = $_doc['Document']['specs'] ?? [];
        if ($_specs) {
            foreach ($_specs as $k => $spec) {
                if ($spec['docspec_product'] == $data['Product'][0]['goods_md5']) {
                    $_specs[$k]['docspec_amount'] = $spec['docspec_amount'] + $amount;
                    $_specs[$k]['docspec_sum'] = $spec['docspec_price'] * $_specs[$k]['docspec_amount'];
                    $bNewRow = false;
                    break;
                }
            }
        }
        if ($bNewRow) {
            $pr_sp = $this->producttospec($data['Product'][0]);
            $pr_sp['docspec_amount'] = $amount;
            $pr_sp['docspec_sum'] = $pr_sp['docspec_price'] * $pr_sp['docspec_amount'];
            $_specs[] = $pr_sp;
        }

        $_doc['Document']['specs'] = array_sort($_specs, 'docspec_seller');

        return $this->documentcreate($d_uid, $_doc);
    }

    /**
     * Перетворення Продукту в специфікацію Кошика
     * @param array $product
     * @return array
     */
    private function producttospec(array $product): array
    {
        return array(
            "docspec_uid" => $this->createUniqueCode(),
            "docspec_seller" => $product["goods_seller"],
            "docspec_product" => $product["goods_md5"],
            "docspec_product_code" => $product["contentid"],
            "docspec_product_weblink" => $product["goods_url"],
            "docspec_product_sku" => $product["goods_code"],
            "docspec_product_name" => $product["goods_name"],
            "docspec_product_photo" => $product["goods_image"],
            "docspec_price" => $product["goods_price"],
            "docspec_amount" => 0,
            "docspec_sum" => 0,
            "docspec_ondemand" => $product["goods_ondemand"],
            "docspec_enable" => "1",
            "docspec_comment" => null,

            "ipiw_date" => null,
            "ipiw_warehouse" => null,
            "ipiw_amount" => null,
            "ref_weight" => $product["goods_weight"],
            "ref_available" => "1",
            "ref_inbalances" => "1",
        );
    }

    /**
     * Видалення позиції в Кошику по UID специфікації
     * @param array $data
     * @return string|null
     */
    public function documentspecdelete(array $data): ?string
    {
        $d_uid = $data['Document']['uid'];
        $s_uid = sanitizeID($data['Document']['specid']);

        $_doc = $this->documentread($d_uid);

        if (empty(($_doc['Document']['doc_uid'])) || empty($s_uid)) {
            return null;
        }

        $_specs = $_doc['Document']['specs'] ?? [];
        if ($_specs) {
            foreach ($_specs as $k => $spec) {
                if ($spec['docspec_uid'] == $s_uid) {
                    unset($_specs[$k]);
                    break;
                }
            }
        }

        $_doc['Document']['specs'] = $_specs;

        return $this->documentcreate($d_uid, $_doc);
    }

    /**
     * Створюємо Замовлення покупця на базі Кошика і повертаємо перелік UID Замовлень
     * @param array $data
     * @return array|null
     */
    public function documentcheckout(array $data): ?array
    {

        $d_uid = $data['Document']['uid'];
        $_doc = $this->documentread($d_uid);

        if (empty(($_doc['Document']['doc_uid']))) {
            return null;
        }

        $_main = [
            'doc_client_name' => $data['Document']['doc_client_name'],
            'doc_client_email' => $data['Document']['doc_client_email'],
            'doc_client_phone' => $data['Document']['doc_client_phone'],
            'doc_client_comment' => $data['Document']['doc_client_comment'],
            'doc_client_address' => $data['Document']['doc_client_address'],
            'doc_delivery' => $data['Document']['doc_delivery'],
            'doc_delivery_client_name' => $data['Document']['doc_delivery_client_name'],
            'doc_delivery_client_phone' => $data['Document']['doc_delivery_client_phone'],
            'doc_payment' => $data['Document']['doc_payment'],
            'doc_client_dncall' => $data['Document']['doc_client_dncall'],
            'doc_client_usebonus' => $data['Document']['doc_client_usebonus'],
            'doc_status' => 4
        ];
        $_doc['Document'] = array_merge($_doc['Document'], $_main);

        // збереження Кошика з кінцевими змінами
        $this->documentcreate($d_uid, $_doc);

        // припускаємо що все перевірили, немає помилок
        // створюємо Замовлення

        $uids = $_specs = $_sums = [];

        // поділ по Продавцях та перерахунок суми (не враховуємо товари що мали статус "немає в наявності")
        foreach ($_doc['Document']['specs'] as $k => $spec) {
            $_specs[$spec['docspec_seller']][] = $spec;
            $_sums[$spec['docspec_seller']] = $spec['ref_available'] == 1 ? $spec['docspec_sum'] + $_sums[$spec['docspec_seller']] : 0;
        }

        foreach ($_specs as $k => $spec) {

            // отримувач оплати
            $payee = $this->sellers($k)['payee'] ? $this->sellers($k)['payee'][0]['uid'] : $this->marketplace()['payee'][0]['uid'];

            $uid_new = $this->createUniqueCode();
            $_doc['Document']['specs'] = $spec;
            $_doc['Document']['doc_uid'] = $uid_new;
            $_doc['Document']['doc_type'] = 'doc_request';
            $_doc['Document']['doc_date_create'] = date("Y-m-d H:i:s");
            $_doc['Document']['doc_number'] = $this->getNumberNew();
            $_doc['Document']['doc_status'] = 1;
            $_doc['Document']['doc_sum'] = $_sums[$k];
            $_doc['Document']['doc_paysum'] = $_doc['Document']['doc_sum'];
            $_doc['Document']['doc_seller'] = $k;
            $_doc['Document']['doc_payee'] = $payee;

            $signs = [];
            $_doc['Document']['signs'] = $signs;

            if (array_search_by_key($this->marketplace(), 'paall') == true && $_doc['Document']['doc_sum'] > 0) {
                $signs = [
                    'doc_sign_2' => "true",
                    'doc_sign_2_date' => date("Y-m-d H:i:s"),
                    'doc_sign_2_manager' => -1,
                    'doc_sign_2_manager_name' => 'Server (auto)',
                ];

                $signs = $this->fetchsigns($signs, $_doc['Document']['signs']);
            }

            $_doc['Document']['signs'] = $signs;
            $_se = end($signs);
            $_doc['Document']['doc_status'] = $_se['type'] ?? 1;

            $_uid = $this->documentcreate($uid_new, $_doc);
            $this->documentupdatesum($uid_new);

            // додаємо до переліку Замовлень лише ті де сума > 0
            if ($_sums[$k] > 0) {
                $uids[] = $_uid;
            }
        }

        // видаляємо UID Кошика з кешу серверу
        $this->serverCache->delete($_doc['Document']['session']);

        return $uids;
    }

    /**
     * Дані по Продавцях
     * @param string|null $uid
     * @param string|null $name
     * @return array
     */
    public function sellers(?string $uid, string $name = null): array
    {
        $sellers = Entity::getData('Sellers');
        return $name ? recursive_array_search_key_value($sellers, $uid, 'name', true) : ($uid ? recursive_array_search_key_value($sellers, $uid, 'uid', true) : []);
    }

    private function getNumberNew(string $name = 'NumeratorRequest'): int
    {
        $_doc = $this->documentread('0000', $name);
        $n = (int)$_doc['Document']['number'];
        $n = empty($n) ? 1 : $n + 1;
        $_doc['Document']['number'] = $n;
        $this->documentcreate('0000', $_doc, $name);
        return $n;
    }

    private function fetchsigns(array $source, array $signs = []): array
    {
        for ($i = 0; $i < 14; $i++) {
            if ($source['doc_sign_' . $i] && $source['doc_sign_' . $i] != 'false') {

                if (!empty(recursive_array_search_key_value($signs, $i, 'type', true))) {
                    foreach ($signs as $k => $v) {
                        if ($v['type'] == $i) {
                            unset($signs[$k]);

//                            $signs[$k] = [
//                                'type' => $i,
//                                'date' => $source['doc_sign_' . $i . '_date'],
//                                'manager' => $source['doc_sign_' . $i . '_manager'],
//                                'par_name_first' => $source['doc_sign_' . $i . '_manager_name']
//                            ];
                        }
                    }
                }

                $signs[] = [
                    'type' => $i,
                    'date' => $source['doc_sign_' . $i . '_date'],
                    'manager' => $source['doc_sign_' . $i . '_manager'],
                    'par_name_first' => $source['doc_sign_' . $i . '_manager_name']
                ];


            }
        }

        return $signs;
    }

    /**
     * Перерахунок сум в специфікації та оновлення загальної суми Кошика
     * @param string $uid
     * @param array|null $_doc
     * @return string|null
     */
    public function documentupdatesum(string $uid, array $_doc = null): ?string
    {
        $_doc = $_doc ?? $this->documentread($uid);
        if (empty(($_doc['Document']['doc_uid']))) {
            return null;
        }

        $doc_sum = 0.00;
        foreach ($_doc['Document']['specs'] as $k => $spec) {
            $_doc['Document']['specs'][$k]['docspec_sum'] = (double)$spec['docspec_price'] * (double)$spec['docspec_amount'];
            if ($spec['docspec_enable'] == 1) {
                $doc_sum = $doc_sum + $_doc['Document']['specs'][$k]['docspec_sum'];
            }
        }

        $_doc['Document']['doc_sum'] = $doc_sum;
        return $this->documentcreate($uid, $_doc);
    }

    /**
     * Підготовка даних для трекінгу та оплати Замовлення
     * @param string $uid
     * @return array
     */
    public function documenttracking(string $uid): array
    {
        $_doc = $this->documentread($uid);
        $_doc['Document']['payee'] = $this->payee($_doc['Document']['doc_payee']);
        if ($_doc['Document']['doc_delivery']) {
            $_doc['Document']['doc_delivery_title'] = recursive_array_search_key_value($this->delivery(), $_doc['Document']['doc_delivery'], 'name', false, 'label');
        }

        return filterArrayRecursive($_doc, null, ['specs', 'regime']);
    }

    /**
     * Отримувачі платежів
     * @param string|null $uid
     * @return array
     */
    public function payee(string $uid = null): array
    {
        $pe = Entity::getData('Payee');
        return $uid ? recursive_array_search_key_value($pe, $uid, 'uid', true) : $pe[0];
    }

    /**
     * Дані по варіантах відправлень
     * @return array
     */
    public function delivery(): array
    {
        return Entity::getData('Delivery');
    }

    public function documentList(array $params = null): array
    {
        $docs = array();
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $paging = ['total' => 0, 'from' => 0, 'to' => 0, 'length' => $this->_paging_length];

        $files = $this->serverCache->items('Document_*', true);
        if (!$files) return $docs;

        $n = 0;
        foreach ($files as $file) {
            $name = basename($file, $this->serverCache->getSuffix());

            $pattern = '/^([A-Za-z0-9_-]+)_/';
            if (preg_match($pattern, $name, $matches)) {
                $name = $matches[1];
            } else {
                continue;
            }

            $_doc = json_decode($this->doCache($name)->get(), true);
            if ($_doc['Document']['doc_type'] != 'doc_request') {
                continue;
            }
            if (!empty($this->getOptions('filter_sellers')) && !in_array($_doc['Document']['doc_seller'], $this->getOptions('filter_sellers'))) {
                continue;
            }

            $bAddThisFile = true;
            if (isset($params['search']) && strlen($params['search']) > 3) {
                if (
                    stripos($_doc['Document']['doc_uid'], $params["search"]) !== false ||
                    stripos($_doc['Document']['doc_number'], $params["search"]) !== false ||
                    mb_stripos($_doc['Document']['doc_client_name'], $params["search"]) !== false ||
                    stripos($_doc['Document']['doc_client_phone'], $params["search"]) !== false ||
                    stripos($_doc['Document']['doc_client_email'], $params["search"]) !== false
                ) {
                    $paging['total'] = $paging['total'] + 1;
                } else {
                    $bAddThisFile = false;
                }
            } else {
                $paging['total'] = $paging['total'] + 1;
            }

            if ($bAddThisFile) {
                $_doc['Document']['doc_delivery_title'] = recursive_array_search_key_value($this->delivery(), $_doc['Document']['doc_delivery'], 'name', false, 'label');

                $_doc['Document']['doc_payee_title'] = $this->payee($_doc['Document']['doc_payee'])['label'];
                $_doc['Document']['doc_seller_title'] = $this->sellers($_doc['Document']['doc_seller'])['label'];

                $docs[strtotime(substr($_doc['Document']['doc_date_create'], 0, 19)) . '-' . $_doc['Document']['doc_number']] = $_doc['Document'];
            }
            $n++;
        }

        $paging['from'] = ($page - 1) * $paging['length'] + 1;
        $paging['to'] = min($paging['from'] + $paging['length'] - 1, $paging['total']);
        if ($paging['from'] > $paging['total'] || $paging['from'] < 1) {
            $paging['from'] = ($paging['total'] - $paging['length'] > 0) ? $paging['total'] - $paging['length'] : $paging['total'];
            $paging['to'] = $paging['total'];
        }

        if ($page > 1) {
            $paging['prev'] = $page - 1;
        }
        if ($page < ceil($paging['total'] / $paging['length'])) {
            $paging['next'] = $page + 1;
        }

        krsort($docs);

        $docs = array_slice($docs, $paging['from'] - 1, $paging['length']);

        return [
            'docs' => $docs,
            'paging' => $paging
        ];
    }

    /** Перелік шаблонів
     * @param array|null $types
     * @return array
     */
    public function typeoftpl(array $types = null): array
    {
        $_a = Entity::getData('Typeoftpl');
        if (!empty($types)) {
            $__a = array();
            foreach ($types as $type) {
                foreach ($_a as $key => $array) {
                    foreach ($array as $item) {
                        if ($item['type'] === $type) {
                            $__a[] = $item;
                        }
                    }
                }
            }
            $_a = $__a;
        }
        return $_a;
    }

    /**
     * Дані по варіантах оплати
     * @return array
     */
    public function payment(): array
    {
        return Entity::getData('Payment');
    }

    /**
     * Оновлення інформації по Продуктах у Кошиках
     * @param array $data
     * @return bool
     */
    public function documentupdatecart(array $data): bool
    {
        $files = $this->serverCache->items('DocumentGetCurrentId_*', true);
        if ($files) {
            foreach ($files as $file) {
                $name = basename($file, $this->serverCache->getSuffix());

                $pattern = '/^([A-Za-z0-9_-]+)_/';

                if (preg_match($pattern, $name, $matches)) {
                    $name = $matches[1];
                } else {
                    continue;
                }

                $_f_doc = json_decode($this->doCache($name)->get(), true);
                if (!empty($uid = $_f_doc['Document']['uid'])) {
                    $_doc = $this->documentread($uid);
                    if ($_doc['Document']['doc_status'] != 1) {
                        continue;
                    }
                    $isUpdating = false;
                    foreach ($data['Product'] as $p) {
                        foreach ($_doc['Document']['specs'] as $k => $spec) {
                            if ($p['goods_md5'] == $spec['docspec_product']) {
                                $_doc['Document']['specs'][$k]['docspec_price'] = $p['goods_price'];
                                $_doc['Document']['specs'][$k]['ref_available'] = $p['goods_avail'];
                                $_doc['Document']['specs'][$k]['docspec_product_photo'] = $p['goods_image'];
                                $_doc['Document']['specs'][$k]['docspec_seller'] = $p['goods_seller'];
                                $_doc['Document']['specs'][$k]['docspec_product_sku'] = $p['goods_code'];
                                $_doc['Document']['specs'][$k]['docspec_product_name'] = $p['goods_name'];
                                $isUpdating = true;
                            }
                        }
                    }
                    if ($isUpdating) {
                        $this->documentupdatesum($uid, $_doc);
                        $this->invalidateCache($uid);
                    }
                }
            }
        }

        return true;
    }

    public function documentupdate(string $uid, array $source): ?string
    {

        if (empty($source)) {
            return null;
        }

        $data = filterArrayRecursive($source, [
            'doc_client_name', 'doc_client_phone', 'doc_client_dncall', 'doc_client_email',
            'doc_manager_comment',
            'doc_delivery_client_name', 'doc_delivery_client_phone',
            'doc_delivery',
            'doc_client_address',
            'doc_delivery_number',
            'doc_payee', 'doc_payment', 'doc_paysum',
        ]);
        if (empty($data)) {
            return null;
        }

        $_doc = $this->documentread($uid);
        if (empty(($_doc['Document']['doc_uid']))) {
            return null;
        }
        $_sellers_bind = $this->getOptions('filter_sellers');
        if (!empty($_sellers_bind) && !in_array($_doc['Document']['doc_seller'], $_sellers_bind)) {
            $this->setOptions('error_type', 'deny');
            return null;
        }

        // загальні поля
        foreach ($data as $k => $v) {
            $_doc['Document'][$k] = $v;
        }

        foreach ($source as $k => $v) {
            if (strpos($k, 'docspec_amount_') !== false) {
                $_spec = explode('docspec_amount__', $k);
                $_spec_uid = $_spec[1];
                foreach ($_doc['Document']['specs'] as $key => $item) {
                    if ($item['docspec_uid'] == $_spec_uid) {
                        $_doc['Document']['specs'][$key]['docspec_amount'] = $v;
                        $_doc['Document']['specs'][$key]['docspec_ondemand'] = $this->isChecked($source['docspec_ondemand__' . $_spec_uid]);
                        $_doc['Document']['specs'][$key]['docspec_enable'] = $this->isChecked($source['docspec_enable__' . $_spec_uid]);
                    }
                }
            }
        }

        // підписи
        $signs = $this->fetchsigns($source);
        if ($signs) {
            $signs = array_sort($signs, 'date');
        }
        $_doc['Document']['signs'] = $signs;
        $_se = end($signs);


        $_doc['Document']['doc_status'] = $_se['type'] ?? 1;

        //$res = $this->documentcreate($uid, $_doc);
        $res = $this->documentupdatesum($uid, $_doc);
        $this->invalidateCache($uid);
        return $res;
    }

    private function isChecked(?string $value): int
    {
        return ($value == 'true') ? 1 : 0;
    }

    public function documentupdatesigns(array $data): ?string
    {
        $this->setLogLevel(LOG_DEBUG);
        $uid = $data['Document']['uid'];
        $_doc = $this->documentread($uid);

        if (empty($_doc['Document']['doc_uid'])) {
            return null;
        }

        $_paymentinfo = [];
        if (!empty($data['Document']['payment_info']) && array_search_by_key($data['Document']['payment_info'], 'status') == 'success') {

            $_paymentinfo = [
                'doc_sign_13' => "true",
                'doc_sign_13_date' => date("Y-m-d H:i:s", floor((array_search_by_key($data['Document']['payment_info'], 'create_date') / 1000))),
                'doc_sign_13_manager' => -1,
                'doc_sign_13_manager_name' => array_search_by_key($data['Document']['payment_info'], 'manager'),
            ];

        }
        $this->log($_paymentinfo, LOG_DEBUG);
        $signs = $this->fetchsigns($_paymentinfo, $_doc['Document']['signs']);
        if ($signs) {
            $signs = array_sort($signs, 'date');
        }
        $_doc['Document']['signs'] = $signs;
        $_se = end($signs);

        $_doc['Document']['doc_status'] = $_se['type'] ?? 1;

        $_ret = $this->documentcreate($uid, $_doc);
        $this->invalidateCache($uid);
        return $_ret;
    }
}
