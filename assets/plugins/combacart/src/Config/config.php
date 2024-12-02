<?php

date_default_timezone_set('Europe/Kyiv');

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
// тривалість актуальності даних на сторінці закінчення оформлення замовлення, сек
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
     * Basic config options
     * Override if exists config_private.php
     *
     * @param $provider
     * @return array
     */
    function get3thAuth($provider): array
    {
        $custom = array();
        if (file_exists(COMBAMODX_PATH_ROOT . COMBAMODX_PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/config_custom.php')) {
            $custom = include COMBAMODX_PATH_ROOT . COMBAMODX_PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/config_custom.php';
            if (!is_array($custom)) {
                $custom = array();
            }
        }

        // закоментуйте або поставте false для цього параметру якщо працюєте з повноцінним Comba сервером
        define("COMBAMODX_BUILDIN_SERVER", true);

        $default = array(
            /**
             * От саме тут налаштування Маркетплейсу для локального серверу
             **/
            'Marketplace' => ['uid' => '00000000-0001-0000-0000-000000000001'],
            /**
             * Ці дані використовуються лише для роботи з вбудованим локальним сервером!
             * Методи та властивості серверу \Comba\BuildInServer\Server можете змінювати за потреби, а саме:
             * marketplace()
             * sellers()
             * payee()
             **/


            /**
             * УВАГА!
             * Якщо ви здійснили реєстрацію магазину на Comba сервері
             * та бажаете повноцінно працювати, внесіть свої налаштування в
             * файл /assets/custom/Config/config_custom.php
             * Після цього налаштування для локального серверу та локального Маркетплейсу не використовуються.
             */
        );

        $auth = array_merge($default, $custom);

        return !empty($auth[$provider]) ? $auth[$provider] : array();
    }
}
