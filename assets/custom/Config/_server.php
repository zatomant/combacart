<?php

/**
 * Налаштування підключення до Comba сервера
 *
 * Після реєстрації маркетплейсу на Comba сервері,
 * оновіть дані в цьому файлі:
 * - задайте режим роботи з сервером 'compatible' або 'cloud'
 * - SERVER_KEY на ключ якій отримали при реєстрації
 * - API_URL на шлях до api якій отримали при реєстрації
 *
 */

return [

    "SERVER" => [
        "mode" => "compatible"      // режим роботи з сервером - compatible або cloud
    ],
    'Provider' => [
        'Comba' => [
            'marketplace' => [
                'url' => 'API_URL',
                'key' => 'SERVER_KEY'
            ]
        ],
    ]
];
