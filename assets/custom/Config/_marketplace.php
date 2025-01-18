<?php

/**
 * Індивідуальні налаштування локального маркетплейса
 *
 * перейменуйте цей файл в marketplace.php щоб система почала його використовувати
 *
 * Скопіюйте з основного файлу /src/Config/marketplace.php
 * групу налаштувань які хочете переназначити,
 * наприклад групу 'Marketplace'
 */

use Comba\Core\Entity;

$isProductDB = getenv('SERVER_ADDR') == '127.0.0.1';

return [
    //'BUILDIN_SERVER' => ['enabled' => false],

    'Marketplace' => [
        'uid' => '00000000-0001-0000-0000-000000000001', // НЕ ЗМІНЮЙТЕ цєй UID якщо працюєте лише з локальним сервером
        'label' => 'Мій перший маркетплейс',
        'site' => Entity::getServerHost(),
        'email' => 'sales@' . Entity::getServerName(),
        'emailinfo' => 'info@' . Entity::getServerName(),
        'emailsupport' => 'support@' . Entity::getServerName(),
        'icon' => '/assets/images/' . Entity::getServerName() . '.jpg',

        // paall = true
        // Дозволити оплату для всіх замовленнь зі статусом Новий:
        // якщо в Замовлені всі товари в наявності - автоматично проставити статус Дозволено оплату
        // і показувати посилання на сторінку оплати одразу після оформлення замовлення
        'paall' => true,

        'contact' => [
            ['type' => 'viber', 'label' => 'viber', 'number' => '380123456789', 'i1nvisible' => true],
            ['type' => 'telegram', 'label' => '@marketplace_test', 'number' => 'marketplace_test', 'url' => 'tg://resolve?domain=marketplace_test'],
            ['type' => 'email', 'label' => 'sales@' . Entity::getServerName(), 'number' => 'sales@' . Entity::getServerName(), 'i1nvisible' => true],
        ],
        'sellers' => [
            ['uid' => '00000000-0002-0000-0000-000000000001'],
            ['uid' => '00000000-0002-0000-0000-000000000002'],
            ['uid' => '00000000-0002-0000-0000-000000000003']
        ],
        'payee' => [
            ['uid' => '00000000-0003-0000-0000-000000000002']
        ]
    ],
    'Payee' => [
        // наприклад цей ФОП може приймати два типу платежів
        [
            'uid' => '00000000-0003-0000-0000-000000000001',
            'value' => 'ФОПтест',
            'label' => 'Тест',
            'okpo' => '123456789',
            'pt' => [
                [
                    'type' => 'pt_cashless', // безготівкові платежі через IBAN
                    'label' => 'ФОП Тест',
                    'account' => 'UA81305299000002600000000001',
                    'okpo' => '112233445566',
                    'bank_label' => 'ПРИВАТБАНК',
                    'bank_name' => 'privatbank'
                ],
                [
                    'type' => 'pt_online',      // онлайн оплата
                    'label' => 'Liqpay',        // назва сервісу
                    'provider' => 'LiqPay',     // ідентифікатор провайдера
                ],
                [
                    'type' => 'pt_online',      // ще одна онлайн оплата
                    'label' => 'Monobank',      // на цей раз через monobank
                    'provider' => 'Monobank',
                ]
                // дві онлайн оплати це для прикладу.
                // можна користуватись двома сервісами онлайн оплат, але це вже такє.
            ]
        ],
        // а цей ФОП приймає платежі лише через IBAN
        [
            'uid' => '00000000-0003-0000-0000-000000000002',
            'value' => 'ФОПтест2',
            'label' => 'Тест2',
            'okpo' => '001122334455',
            'pt' => [
                [
                    'type' => 'pt_cashless',
                    'label' => 'ФОП Тест2',
                    'account' => 'UA81305299000002600000000002',
                    'okpo' => '778899445566',
                    'bank_label' => 'ПРИВАТБАНК',
                    'bank_name' => 'privatbank'
                ],
            ]
        ]
    ],

    // формат структури
    // Provider
    //
    // 'назва_провайдера' => [     // назва провайдера може бути будь-яка, але унікальна
    //     'ідентифікатор' => [    // де ідентифікатор це UID Продавця або слово 'marketplace' було зробити доступним для всіх Продавців
    //         'disabled' => true, // якщо забороняємо використовувати ці дані
    //        // дані
    //     ]
    // ],

    // наприклад,
    // 'Monobank' => [
    //    'marketplace' => [        // для всього сайту
    //        'class' => 'Monobank',
    //        'token' => '12345'
    //    ],
    //    'UID-Продавця' => [       // використовується лише для Продавця з ідентифікатором 'UID-Продавця'
    //        'class' => 'Monobank',
    //        'token' => '67890'
    //    ],
    //    'UID-Продавця2' => [       // використовується лише для Продавця з ідентифікатором 'UID-Продавця2'
    //        'disabled' => true,    // але так як тут true буде використано дані з 'marketplace'
    //        'class' => 'Monobank',
    //        'token' => '666666'
    //    ],
    // ],


    'Provider' => [
        /**
         * За замовчуванням прямий зв'язок з базою не використовується.
         * заповнюйте це за необхідності та отримуйте дані через Entity::get3thAuth('db','marketplace')
         */
        'db' => [
            'marketplace' => [
                'host' => $isProductDB ? Entity::DB_SERVER_NAME : 'localhost1',
                'port' => '',
                'socket' => $isProductDB ? '' : '/var/lib/mysql/mysql.sock',
                'type' => 'mysql',
                'user' => 'user',
                'passwd' => 'passwd',
                'database' => 'database'
            ]
        ],

        /**
         * Розкоментуйте цю секцію лише у разі якщо працюєте з Comba сервером.
         * Замінить MARKETPLACE_UID на UID якій отримали після реєстрації магазину на Comba сервері
         */
        //        'Marketplace' => [
        //            'marketplace' => [
        //                'uid' => 'MARKETPLACE_UID'
        //            ]
        //        ],

        /**
         * Розкоментуйте цю секцію, щоб налаштувати підключення до Comba серверу
         * Після цього інформація по Продавцях, видах оплат та інша системні дані будуть завантажуватись з віддаленого сеерверу за потреби автоматично.
         * Змініть SERVER_KEY на ключ якій отримали після реєстрації магазину
         */
        //        'Comba' => [
        //            'marketplace' => [
        //                'key' => 'SERVER_KEY',
        //                'url' => 'https://comba_server_url/api/',
        //            ]
        //        ],

        /**
         * Розкоментйте цю секцію, щоб дозволити вхідні підключення до вашого сайту від Comba серверу
         * замінить REQUEST_KEY на ключ якій отримали після реєстрації магазину
         */
        //        'RequestApi' => [
        //            'marketplace' => [
        //                'REQUEST_KEY' => [
        //                    'ip' => '', // опціонально, заповніть це щоб обмежити підключення лише з однієї IP адреси
        //                    'server' => 'server_hostname', // опціонально, заповніть це щоб обмежити підключення лише з цієї доменної адреси
        //                ],
        //            ]
        //        ],

        /**
         * провайдер reCaptcha
         * знайдіть свій ключ на https://www.google.com/recaptcha/admin/site/
         */
        'reCaptcha' => [
            'marketplace' => [
                'key' => '',
                'secret' => '',
                'url' => 'https://www.google.com/recaptcha/api/siteverify',
            ]
        ],

        /**
         * провайдер Новапошта
         * знайдіть свій ключ в Персональному кабінеті Новапошта
         */
        'NovaPoshta' => [
            'marketplace' => [
                'key' => '',
                'phone' => ''
            ]
        ],

        /**
         * провайдер AlphaSMS
         * знайдіть свій ключ в Персональному кабінеті AlphaSMS
         */
        'AlphaSMS' => [
            'marketplace' => [
                'login' => '+380',
                'pass' => '',
                'key' => '',
                'alias' => 'Назва'
            ]
        ],

        /**
         * провайдер Liqpay
         * знайдіть свій ключ в Персональному кабінеті Liqpay
         */
        'LiqPay' => [
            'marketplace' => [
                'class' => 'LiqPay',
                'public_key' => '',
                'private_key' => ''
            ]
        ],

        /**
         * провайдер Monobank
         * знайдіть свій токен в Персональному додатку monobank або на https://api.monobank.ua
         */
        'Monobank' => [
            'marketplace' => [
                'class' => 'Monobank',
                'token' => '' // впишіть свій token
            ]
        ],
    ]
];