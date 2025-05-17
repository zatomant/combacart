<?php

return [

// Сесія
    'SESSION_NAME' => 'SSUSER_',

// рівень ведення журналу для плагінів та сніпетів
    'LOG_LEVEL' => LOG_ERR,

// рівень ведення журналу для адніністративної сторінки /comba
    'LOG_LEVEL_COMBA' => LOG_ERR,

// Термін актуальності кешованих даних клієнта за замовчуванням (30 днів у секундах)
    'CACHE_LIFETIME' => 60 * 60 * 24 * 30,

// База даних
    'DB_SERVER_NAME' => 'localhost',

// Мовний файл за замовчуванням
    'LANGUAGE' => 'uk',

// Сторінки

    'PAGE_CABINET' => 'cabinet',
    // кількість Замовлень на сторінці при пагінації
    'PAGE_CABINET_LENGTH' => 5,

    'PAGE_SINGIN' => 'singin',
    'PAGE_SINGUP' => 'singup',
    'PAGE_CHECKOUT' => 'checkout',

    'PAGE_TNX' => 'tnx',
    // термін актуальності даних на сторінці TNX, 5 хвилин
    'PAGE_TNX_TIMEOUT' => 300,

    'PAGE_TRACKING' => 't',
    'PAGE_PAYMENT' => 'p',
    'PAGE_PAYMENT_CALLBACK' => 'ps',

    'PAGE_COMBA' => 'comba',

// Налаштування Замовлень та Кошика
// Показувати назву Продавця на сторінках
    'SELLER_SHOW_LABEL' => true,

// Дозволити неавторизованим Покупцям доступ до Кабінету
    'CABINET_GUEST_MODE_ENABLED' => true,

// Розділити Товари у Кошику на окремі Замовлення якщо різні Продавці
    'ORDER_SEPARATE_BY_SELLERS' => true,

//  Для Замовленнь зі статусом Новий автоматично встановити підпис "Дозволити оплату"
    'ORDER_PAYMENT_FOR_NEW' => true,

// Перевіряти доступ Менеджера до Продавців
    'MANAGER_SELLER_CHECK' => true,

// Максимальна кількість одного товару у Кошику
    'GOODS_MAX_QUANTITY' => 99999,

// TV names
    'TV_GOODS_NAME' => 'goods_name',
    'TV_GOODS_CODE' => 'goods_code',
    'TV_GOODS_PRICE' => 'goods_price',
    'TV_GOODS_PRICE_OLD' => 'goods_price_old',
    'TV_GOODS_AVAIL' => 'goods_avail',
    'TV_GOODS_WEIGHT' => 'goods_weight',
    'TV_GOODS_ISONDEMAND' => 'goods_isondemand',
    'TV_GOODS_ISNEWPRODUCT' => 'goods_isnewproduct',
    'TV_GOODS_GOODS' => 'goods_goods',
    'TV_GOODS_IMAGES' => 'goods_images',
    'TV_GOODS_SELLER' => 'goods_seller',
    'TV_GOODS_INBALANCES' => 'goods_inbalances',

];
