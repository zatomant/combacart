<?php

date_default_timezone_set('Europe/Kyiv');

const COMBAMODX_NAME = 'CombaCart';
const COMBAMODX_TDS = 'FS';
const COMBAMODX_FILE_VER = '20';
const COMBAMODX_VERSION = '2.6.' . COMBAMODX_FILE_VER . ' ' . COMBAMODX_TDS;

// термін актуальності кешованих даних кліента за замовчуванням
const COMBAMODX_CACHE_LIFETIME = 2592000; // 60*60*24* 30 діб

define("COMBAMODX_PATH_ROOT", dirname(__FILE__, 3));
const COMBAMODX_PATH_SRC = DIRECTORY_SEPARATOR . 'src';
const COMBAMODX_PATH_ASSETS = DIRECTORY_SEPARATOR . 'assets';
const COMBAMODX_PATH_CUSTOM = COMBAMODX_PATH_ASSETS . DIRECTORY_SEPARATOR . 'custom';
const COMBAMODX_PATH_THEMES = COMBAMODX_PATH_SRC . DIRECTORY_SEPARATOR . 'Themes';
const COMBAMODX_PATH_TEMPLATES = COMBAMODX_PATH_THEMES . DIRECTORY_SEPARATOR . 'templates';

const COMBAMODX_SESSION_NAME = 'SSUSER_';

const DB_SERVER_NAME = 'localhost';

// хост та доменне і'мя сайту,  https://something.com.ua something.com.ua
define("COMBAMODX_SERVER_HOST", 'https://' . getenv('HTTP_HOST'));
define("COMBAMODX_SERVER_NAME", getenv('SERVER_NAME'));

// мовний файл за замовчуванням
const COMBAMODX_LANGUAGE = 'uk';

// сторінка авторизації веб користувача
const COMBAMODX_PAGE_LOGIN = 'login';

// сторінка адміністрування замовленнями (за відсутності реєстрації на Comba сервері)
// можна змінити за неохідності
// http(s)://ваш_сайт/comba
const COMBAMODX_PAGE_COMBA = 'comba';

// сторінка Оформлення замовлення
const COMBAMODX_PAGE_CHECKOUT = 'checkout';
// сторінка перенаправлення після оформлення замовлення
const COMBAMODX_PAGE_TNX = 'tnx';
// тривалість актуальності даних на сторінці Закінчення оформлення замовлення, сек
const COMBAMODX_PAGE_TNX_TIMEOUT = 259200; // 3 доби

// сторінка Відстеження замовлення
const COMBAMODX_PAGE_TRACKING = 't';
// сторінка Оплати замовлення
const COMBAMODX_PAGE_PAYMENT = 'p';
// сторінка callback Оплати замовлення (відповідь від платіжного сервісу)
const COMBAMODX_PAGE_PAYMENT_CALLBACK = 'ps';

// показувати Продавця на сторінках
const COMBAMODX_SELLER_SHOW = true;
// розділяти товари у Кошику по Продавцю на окремі Замовлення
const COMBAMODX_ORDER_SEPARATE_BY_SELLERS = true;

// максимальна кількість одного товару що додається у Кошик
const COMBAMODX_GOODS_MAX_QUANTITY = 99999;

// TV names
const GOODS_NAME = 'goods_name';
const GOODS_CODE = 'goods_code';
const GOODS_PRICE = 'goods_price';
const GOODS_PRICE_OLD = 'goods_price_old';
const GOODS_AVAIL = 'goods_avail';
const GOODS_WEIGHT = 'goods_weight';
const GOODS_ISONDEMAND = 'goods_isondemand';
const GOODS_ISNEWPRODUCT = 'goods_isnewproduct';
const GOODS_GOODS = 'goods_goods';
const GOODS_IMAGES = 'goods_images';
const GOODS_SELLER = 'goods_seller';
const GOODS_INBALANCES = 'goods_inbalances';


if (!function_exists('get3thAuth')) {
    /**
     * Отримання даних з конфігів
     *
     * @param $provider
     * @return array
     */
    function get3thAuth($provider): array
    {
        $custom = array();
        if (file_exists(COMBAMODX_PATH_ROOT . COMBAMODX_PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/config.php')) {
            // індивідуальні налаштування аутентифікації сервісів
            $custom = include COMBAMODX_PATH_ROOT . COMBAMODX_PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/config.php';
            if (!is_array($custom)) {
                $custom = array();
            }
        }

        /**
         * УВАГА!
         * Якщо ви здійснили реєстрацію магазину на Comba сервері та бажаете повноцінно працювати,
         * внесіть свої налаштування аутентифікації в файл /assets/custom/Config/config.php
         *
         * Та закоментуйте або поставте false для параметру COMBAMODX_BUILDIN_SERVER
         * і після цього налаштування локального серверу та локального Маркетплейсу перестануть використовуватись.
         */
        define("COMBAMODX_BUILDIN_SERVER", true);

        // налаштування Маркетплейсу "за замовчуванням"
        $default = include dirname(__FILE__) . '/marketplace.php';
        if (file_exists(COMBAMODX_PATH_ROOT . COMBAMODX_PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/marketplace.php')) {
            // індивідуальні налаштування Маркетплейсу зберігаються в /assets/custom/Config/marketplace.php
            $default = array_merge($default, include COMBAMODX_PATH_ROOT . COMBAMODX_PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/marketplace.php');
        }

        $auth = array_merge($default, $custom);

        return !empty($auth[$provider]) ? $auth[$provider] : array();
    }
}
