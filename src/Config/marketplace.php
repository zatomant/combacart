<?php

/**
 * Налаштування маркетплейсу (за замовчуванням)
 *
 * Увага! Не змінюйте цей файл, цей файл оновлюється автоматично кожнен реліз.
 *
 *
 * Для індивідуальних налаштувань використовуйте файли у /assets/custom/Config/
 *
 * Для роботи з віддаленим сервером Comba:
 *    - Внесіть налаштування в /assets/custom/Config/server.php
 *    - Встановіть в ньому SERVER mode = 'compatible' або 'cloud'
 *    - Усі локальні налаштування вимикаються при використанні віддаленого сервера.
 */

return [
    // Режим роботи серверу standalone, compatible, cloud
    'SERVER' => [
        'mode' => 'standalone'
    ],

    'Marketplace' => [
        'uid' => '00000000-0001-0000-0000-000000000001', // НЕ ЗМІНЮЙТЕ цєй UID якщо працюєте лише з локальним сервером
        'label' => 'Тестовий маркетплейс',
        'site' => '@_get(SERVER_HOST)',
        'email' => 'sales@' . '@_get(SERVER_NAME)',
        'emailinfo' => 'info@' . '@_get(SERVER_NAME)',
        'emailsupport' => 'support@' . '@_get(SERVER_NAME)',
        'icon' => '/assets/images/' . '@_get(SERVER_NAME)' . '.jpg',

        'contact' => [
            [
                'type' => 'viber',
                'label' => 'viber',
                'number' => '380123456789',
                'i1nvisible' => true
            ],
            [
                'type' => 'telegram',
                'label' => '@marketplace_test',
                'number' => 'marketplace_test',
                'url' => 'tg://resolve?domain=marketplace_test'
            ],
            [
                'type' => 'email',
                'label' => 'sales@' . '@_get(SERVER_NAME)',
                'number' => 'sales@' . '@_get(SERVER_NAME)',
                'i1nvisible' => true
            ],
        ],
        'sellers' => [
            // перший UID в списку буде вважатись продавцем за замовчуванням
            ['uid' => '00000000-0002-0000-0000-000000000000'],
            ['uid' => '00000000-0002-0000-0000-000000000001'],
            ['uid' => '00000000-0002-0000-0000-000000000002']
        ],
        'payee' => [
            // Отримувач коштів за замовчуванням
            ['uid' => '00000000-0003-0000-0000-000000000000']
        ]
    ],

    'Sellers' => [
        [
            'uid' => '00000000-0002-0000-0000-000000000000',
            'label' => 'Тестовий маркетплейс',
            'site' => '@_get(SERVER_HOST)',
            'email' => 'sales@@_get(SERVER_HOST)',
            'emailinfo' => 'info@@_get(SERVER_HOST)',
            'emailsupport' => 'support@@_get(SERVER_HOST)',
            'icon' => '/assets/images/' . '@_get(SERVER_NAME)' . '.jpg',
            'contact' => [
                [
                    'type' => 'phone',
                    'label' => '0800 111 22 33 Гаряча лінія підтримки',
                    'number' => '+08001112233'
                ],
                [
                    'type' => 'telegram',
                    'label' => '@seller_test',
                    'number' => 'seller_test'
                ],
                [
                    'type' => 'address',
                    'label' => 'Степана Бандери проспект,
                     Київ'
                ],
            ],
            'regime' => [
                'days1' => 'Щодня: з 09-00 по 21-00',
            ],
            'payee' => [
                ['uid' => '00000000-0003-0000-0000-000000000000'],
            ]
        ],
        [
            'uid' => '00000000-0002-0000-0000-000000000001',
            'label' => 'Селер',
            'site' => 'sellertest.com',
            'email' => 'sales@sellertest.com',
            'emailinfo' => 'info@sellertest.com',
            'emailsupport' => 'support@sellertest.com',
            'icon' => '/assets/images/sellertest.jpg',
            'contact' => [
                [
                    'type' => 'phone',
                    'label' => '(012) 345-67-89 Інтернет-замовлення',
                    'number' => '+380123456789'
                ],
                [
                    'type' => 'viber',
                    'label' => 'viber',
                    'number' => '380123456789',
                    'invisible' => true
                ],
                [
                    'type' => 'telegram',
                    'label' => '@seller_test',
                    'number' => 'seller_test'
                ],
                [
                    'type' => 'address',
                    'label' => 'Степана Бандери проспект,
                     Київ'
                ],
                [
                    'type' => 'links',
                    'name' => 'instagram',
                    'label' => 'seller_test',
                    'url' => 'https://instagram.com/seller_test',
                    'icon' => '/assets/images/instagram.png'
                ],
            ],
            'regime' => [
                'days1' => 'Понеділок - П`ятниця: з 10-00 по 19-00',
                'days2' => 'Субота - Неділя: з 10-00 по 18-00'
            ],
            'payee' => [
                ['uid' => '00000000-0003-0000-0000-000000000001'],
                ['uid' => '00000000-0003-0000-0000-000000000002']
            ]
        ],
        [
            'uid' => '00000000-0002-0000-0000-000000000002',
            'label' => 'Продавець',
            'site' => 'sellertest2.com',
            'email' => 'sales@sellertest2.com',
            'emailinfo' => 'info@sellertest2.com',
            'emailsupport' => 'support@sellertest2.com',
            'icon' => '/assets/images/sellertest2.jpg',
            'links' => [
                [
                    'name' => 'instagram',
                    'label' => 'seller_test2',
                    'url' => 'https://instagram.com/seller_test2',
                    'icon' => '/assets/images/instagram.png'
                ],
            ],
            'contact' => [
                [
                    'type' => 'phone',
                    'label' => '(098) 765-43-21 Інтернет-замовлення',
                    'number' => '+380987654321'
                ],
                [
                    'type' => 'viber',
                    'label' => 'viber',
                    'number' => '380987654321',
                    'invisible' => true
                ],
                [
                    'type' => 'telegram',
                    'label' => '@seller_test2',
                    'number' => 'seller_test2'
                ],
                [
                    'type' => 'address',
                    'label' => 'Романа Шухевича проспект, Київ'
                ],
            ],
            'regime' => [
                'days1' => 'Понеділок - П`ятниця: з 09-00 по 20-00',
                'days2' => 'Субота - Неділя: з 09-00 по 20-00'
            ],
        ],
    ],

    'Payee' => [
        [
            'uid' => '00000000-0003-0000-0000-000000000000',
            'value' => 'Тестовий маркетплейс ООО',
            'label' => 'Тест ООО',
            'okpo' => '987654321',
            'pt' => [
                [
                    'type' => 'pt_cashless',    // безготівкові платежі через IBAN
                    'label' => 'Тест ООО',
                    'account' => 'UA81305299000002600000000000',
                    'okpo' => '987654321',
                    'bank_label' => 'ПРИВАТБАНК',
                    'bank_name' => 'privatbank'
                ],
            ]
        ],
        // наприклад цей ФОП може приймати два вида платежів
        [
            'uid' => '00000000-0003-0000-0000-000000000001',
            'value' => 'ФОПтест',
            'label' => 'Тест',
            'okpo' => '123456789',
            'pt' => [
                [
                    'type' => 'pt_cashless',    // безготівкові платежі через IBAN
                    'label' => 'ФОП Тест',
                    'account' => 'UA81305299000002600000000001',
                    'okpo' => '112233445566',
                    'bank_label' => 'ПРИВАТБАНК',
                    'bank_name' => 'privatbank'
                ],
                [
                    'type' => 'pt_online',      // Передплата онлайн
                    'label' => 'Liqpay',        // назва сервісу
                    'provider' => 'LiqPay',     // ідентифікатор провайдера
                ]
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

    // Типи списків
    'Payment' => [
        [
            'name' => 'pt_online',
            'label' => 'Передплата онлайн'
        ],
        [
            'name' => 'pt_cod',
            'label' => 'Оплата при отримані'
        ],
        [
            'name' => 'pt_cashless',
            'label' => 'Безготівковий банківський платіж'
        ],
    ],

    'Delivery' => [
        [
            'name' => 'dt_pickup',
            'label' => 'Самовивіз'
        ],
        [
            'name' => 'dt_ukrposhta',
            'label' => 'Укрпошта Стандарт',
            'tariffs' => 'https://www.ukrposhta.ua/ua/taryfy-ukrposhta-standart'
        ],
        [
            'name' => 'dt_ukrposhta_express',
            'label' => 'Укрпошта Експрес',
            'tariffs' => 'https://www.ukrposhta.ua/ua/taryfy-ukrposhta-ekspres'
        ],
        [
            'name' => 'dt_ukrposhta_int',
            'label' => 'Укрпошта Міжнародна',
            'tariffs' => 'https://www.ukrposhta.ua/ua/taryfy-mizhnarodni-vidpravlennia-posylky'
        ],
        [
            'name' => 'dt_novaposhta',
            'label' => 'Нова Пошта',
            'tariffs' => 'https://novaposhta.ua/privatnim_klientam/ceny_i_tarify'
        ],
        [
            'name' => 'dt_novaposhta_postomat',
            'label' => 'Нова Пошта поштомат',
            'tariffs' => 'https://novaposhta.ua/poshtomat/#tariffs'
        ],
        [
            'name' => 'dt_novaposhta_global',
            'label' => 'Нова Пошта Глобал',
            'tariffs' => 'https://novaposhta.ua/delivery_to_europe/'
        ],
        [
            'name' => 'dt_meest',
            'label' => 'Meest',
            'tariffs' => 'https://meestposhta.com.ua/tariffs'
        ]
    ],

    // Шаблони
    'Typeoftpl' => [
        'Для пошти' => [
            [
                'type' => 'email',
                'name' => '1',
                'file' => 'etpl_1',
                'label' => 'Декларація',
                'label2' => 'Номер декларації по замовленю №{{ doc.doc_number }}'
            ],
            [
                'type' => 'email',
                'name' => '555',
                'file' => 'etpl_55_5',
                'label' => 'Оплата',
                'label2' => 'Реквізити до оплати замовлення №{{ doc.doc_number }}'
            ],
            [
                'type' => 'email',
                'name' => '34',
                'file' => 'etpl_34',
                'label' => 'Лист замовлення (кліенту)',
                'label2' => 'Замовлення №{{ doc.doc_number }}'
            ],
            [
                'type' => 'email',
                'name' => '35',
                'file' => 'etpl_35',
                'label' => 'Лист замовлення (менеджеру)',
                'label2' => 'Нове замовлення №{{ doc.doc_number }}'
            ],
        ],
        'Для месенджерів' => [
            [
                'type' => 'viber',
                'name' => '577',
                'file' => 'etpl_57_7',
                'label' => 'Оплата',
                'label2' => '{{ doc.doc_delivery_client_phone }}'
            ],
            [
                'type' => 'viber',
                'name' => '46',
                'file' => 'etpl_46',
                'label' => 'Декларація',
                'label2' => '{{ doc.doc_delivery_client_phone }}'
            ],
        ],
        'Для смс' => [
            [
                'type' => 'sms',
                'name' => '571',
                'file' => 'stpl_571',
                'label' => 'Оплата через посилання'
            ],
        ],
        'Друковані форми' => [
            [
                'type' => 'print',
                'name' => 'printorderspecslider',
                'file' => '10',
                'label' => 'Специфікація для телефона'
            ],
            [
                'type' => 'print',
                'name' => 'printorderspec3',
                'file' => '3',
                'label' => 'Специфікація'
            ],
            [
                'type' => 'print',
                'name' => 'printordercheque',
                'file' => '7',
                'label' => 'Видатковий чек'
            ],
        ]
    ],

    // Предналаштовані типи розмірів і пропорцій зображень
    'Imagepresets' => [
        'cart-goods' => [
            'ratio' => 'img1x1',
            'name' => '1x1-checkout',
            'value' => 'w=250,h=250,q=80,far=TL,iar=1,img1x1'
        ],
        'catalog-goods' => [
            'ratio' => 'img4x3',
            'name' => '4x3',
            'value' => 'w=450,h=340,q=80,img4x3'
        ],
        'catalog-news' => [
            'ratio' => '4x3',
            'name' => '4x3',
            'value' => 'w=400,h=300,q=80,img4x3'
        ],
        'catalog-reviews' => [
            'ratio' => '16x9',
            'name' => '16x9',
            'value' => 'w=450,h=253,q=80,img16x9'
        ],
        'catalog-themes' => [
            'ratio' => 'img4x3',
            'name' => '4x3',
            'value' => 'w=450,h=340,q=80,img4x3'
        ],
        'checkout-goods' => [
            'ratio' => 'img1x1',
            'name' => '1x1-checkout',
            'value' => 'w=250,h=250,q=80,far=TL,iar=1,img1x1'
        ],
        'image-max' => [
            'ratio' => 'image-max',
            'name' => 'image-max',
            'value' => 'w=800,q=80,fltr{}=watermark'
        ],
        'goods-slider' => [
            'ratio' => 'img1x1',
            'name' => '1x1-indicators',
            'value' => 'w=100,h=100,q=80,far=TL,iar=1,img1x1'
        ],
        'page-companions' => [
            'ratio' => 'img4x3',
            'name' => '4x3',
            'value' => 'w=450,h=340,q=80,img4x3'
        ],
        'page-goods' => [
            'ratio' => 'img4x3',
            'name' => '4x3',
            'value' => 'w=450,h=340,q=80,img4x3'
        ],
        'page-goods-2' => [
            'ratio' => 'img2x3',
            'name' => '2x3',
            'value' => 'w=340,h=450,q=80,img2x3'
        ],
        'page-goods-3' => [
            'ratio' => '16x9',
            'name' => '16x9',
            'value' => 'w=450,h=253,q=80,img16x9'
        ],
        'page-goods-top' => [
            'ratio' => 'img1x1',
            'name' => '1x1-page',
            'value' => 'w=550,h=550,q=80,img1x1'
        ],
        'page-images' => [
            'ratio' => 'img4x3',
            'name' => '4x3-page-images',
            'value' => 'w=200,h=150,q=80,img4x3'
        ],
        'goods-tnx' => [
            'ratio' => 'img1x1',
            'name' => '1x1-tnx',
            'value' => 'w=55,h=55,q=80,far=TL,iar=1,img1x1'
        ],
    ]
];
