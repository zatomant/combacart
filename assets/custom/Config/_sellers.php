<?php

/**
 * Індивідуальні налаштування Продавців
 *
 */

return [
    [
        '_add' => [ // додаємо нового Продавця
            'Sellers' => [
                [
                    'uid' => '00000000-0002-0000-0000-000000000003',
                    'label' => 'Продавчиня',
                    'site' => 'sellertest3com',
                    'email' => 'sales@sellertest3.com',
                    'emailinfo' => 'info@sellertest3.com',
                    'emailsupport' => 'support@sellertest3.com',
                    'icon' => '/assets/images/sellertest3.jpg',
                    'links' => [
                        [
                            'name' => 'instagram',
                            'label' => 'seller_test3',
                            'url' => 'https://instagram.com/seller_test3',
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
                            'number' => 'seller_test3'
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
                ]
            ]
        ],
    ]
];
