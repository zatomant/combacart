<?php

namespace Comba\Core;

date_default_timezone_set('Europe/Kyiv');

class Entity
{

    // Основні параметри
    public const NAME = 'CombaCart';
    public const TDS = 'FS';
    public const FILE_VER = '32';
    public const VERSION = '2.6.' . self::FILE_VER . ' ' . self::TDS;

    // Термін актуальності кешованих даних клієнта за замовчуванням (30 днів у секундах)
    public const CACHE_LIFETIME = 2592000; // 60*60*24*30

    // Шляхи
    public const PATH_ROOT = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
    public const PATH_SRC = DIRECTORY_SEPARATOR . 'src';
    public const PATH_ASSETS = DIRECTORY_SEPARATOR . 'assets';
    public const PATH_CUSTOM = self::PATH_ASSETS . DIRECTORY_SEPARATOR . 'custom';
    public const PATH_THEMES = self::PATH_SRC . DIRECTORY_SEPARATOR . 'Themes';
    public const PATH_TEMPLATES = self::PATH_THEMES . DIRECTORY_SEPARATOR . 'templates';

    // Сесія
    public const SESSION_NAME = 'SSUSER_';

    // База даних
    public const DB_SERVER_NAME = 'localhost';

    // Мовний файл за замовчуванням
    public const LANGUAGE = 'uk';

    // Сторінки
    public const PAGE_LOGIN = 'login';
    public const PAGE_COMBA = 'comba';
    public const PAGE_CHECKOUT = 'checkout';
    public const PAGE_TNX = 'tnx';
    public const PAGE_TNX_TIMEOUT = 259200; // 3 дні у секундах
    public const PAGE_TRACKING = 't';
    public const PAGE_PAYMENT = 'p';
    public const PAGE_PAYMENT_CALLBACK = 'ps';

    // Налаштування замовлень та кошика
    public const SELLER_SHOW = true; // Показувати Продавця на сторінках
    public const ORDER_SEPARATE_BY_SELLERS = true; // Розділяти товари у Кошику на окремі Замовлення
    public const MANAGER_SELLER_CHECK = true; // Перевіряти доступ Менеджера до Продавців
    public const GOODS_MAX_QUANTITY = 99999; // Максимальна кількість одного товару у Кошику

    // TV names
    public const TV_GOODS_NAME = 'goods_name';
    public const TV_GOODS_CODE = 'goods_code';
    public const TV_GOODS_PRICE = 'goods_price';
    public const TV_GOODS_PRICE_OLD = 'goods_price_old';
    public const TV_GOODS_AVAIL = 'goods_avail';
    public const TV_GOODS_WEIGHT = 'goods_weight';
    public const TV_GOODS_ISONDEMAND = 'goods_isondemand';
    public const TV_GOODS_ISNEWPRODUCT = 'goods_isnewproduct';
    public const TV_GOODS_GOODS = 'goods_goods';
    public const TV_GOODS_IMAGES = 'goods_images';
    public const TV_GOODS_SELLER = 'goods_seller';
    public const TV_GOODS_INBALANCES = 'goods_inbalances';

    private static array $_data;

    // Хост та доменне ім'я сайту
    public static function getServerHost(): string
    {
        return 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function getServerName(): string
    {
        return $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Отримання даних аутентифікації
     *
     * @param string $provider
     * @param string $seller UID
     * @return array
     */
    public static function get3thAuth(string $provider, string $seller): array
    {
        if (empty(self::$_data)) {
            self::loadData();
        }

        // Перевіряємо наявність даних для заданого продавця
        if (isset(self::$_data['Provider'][$provider][$seller])) {
            $sellerData = self::$_data['Provider'][$provider][$seller];

            // Перевірка, чи дані позначено як "не використовувати"
            if (!empty($sellerData['disabled'])) {
                return self::getData($provider, 'marketplace');
            }

            return $sellerData;
        }

        // Якщо продавець — 'marketplace', повертаємо порожній масив, щоб уникнути рекурсії
        if ($seller === 'marketplace') {
            return [];
        }

        // Повертаємо дані за замовчуванням для маркетплейсу
        return self::getData($provider, 'marketplace');
    }

    private static function loadData()
    {
        $default = [];
        if (file_exists(self::PATH_ROOT . self::PATH_SRC . '/Config/marketplace.php')) {
            // налаштування Маркетплейсу "за замовчуванням"
            $default = include self::PATH_ROOT . self::PATH_SRC . '/Config/marketplace.php';
        }

        // індивідуальні налаштування Маркетплейсу зберігаються в /assets/custom/Config/marketplace.php
        if (file_exists(self::PATH_ROOT . self::PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/marketplace.php')) {
            $custom = array_merge($default, include self::PATH_ROOT . self::PATH_CUSTOM . DIRECTORY_SEPARATOR . 'Config/marketplace.php');
        }

        self::$_data = array_merge($default, $custom ?? []);
    }

    /**
     * Отримання даних
     *
     * @param string $entity
     * @return array
     */
    public static function getData(string $entity): array
    {
        if (empty(self::$_data)) {
            self::loadData();
        }

        return !empty(self::$_data[$entity]) ? self::$_data[$entity] : array();
    }
}
