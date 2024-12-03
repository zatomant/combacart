<?php

/**
 * Override config options
 * rename this file to config_custom.php for override main config
 */

$isProductDB = getenv('SERVER_ADDR') == '127.0.0.1';

return array(
    /**
     * За замовчуванням прямий зв'язок з базою не використовується.
     * заповнюйте це за необхідності та отримуйте дані через get3thAuth('db')
     */
    'db' => [
        'host' => $isProductDB ? DB_SERVER_NAME : 'localhost',
        'port' => '',
        'socket' => $isProductDB ? '' : '/var/lib/mysql/mysql.sock',
        'type' => 'mysql',
        'user' => 'user',
        'passwd' => 'passwd',
        'database' => 'database'
    ],

    /**
     * Розкоментуйте цю секцію для налаштування свого Маркетплейсу.
     * Маркетплейс може містить декілька Продавців.
     * Інформація по Продавцях підтягується з сеерверу автоматично.
     *
     * Замінить MARKETPLACE_UID на UID якій отримали після реєстрації магазину
     * 'Marketplace' => ['uid' => 'MARKETPLACE_UID'],
     *
     */

    /**
     * Розкоментуйте цю секцію, щоб налаштувати підключення до Comba серверу
     *
     * замінить SERVER_KEY на ключ якій отримали після реєстрації магазину
     * 'Comba' => [
     * 'key' => 'SERVER_KEY',
     * 'url' => 'https://comba_server_url/api/',
     * ],
     */

    /**
     * Розкоментйте цю секцію, щоб дозволити вхідні підключення до вашого сайту від Comba серверу
     *
     * замінить REQUEST_KEY на ключ якій отримали після реєстрації магазину
     * 'RequestApi' => array(
     *      'REQUEST_KEY' => [
     *          'ip' => '', // опціонально, заповніть це щоб обмежити підключення лише з однієї IP адреси
     *          'server' => 'server_hostname', // опціонально, заповніть це щоб обмежити підключення лише з цієї доменної адреси
     *      ],
     * )
     */

    /**
     * Ключ reCaptcha
     * знайдіть свій ключ на https://www.google.com/recaptcha/admin/site/
     */
    'reCaptcha' => [
        'key' => '',
        'secret' => '',
        'url' => 'https://www.google.com/recaptcha/api/siteverify',
    ],

    /**
     * Ключ до API Новапошта
     * знайдіть свій ключ в Персональному кабінеті Новапошта
     */
    'NovaPoshta' => [
        'key' => '',
        'phone' => ''
    ],

    /**
     * Ключ до API AlphaSMS
     * знайдіть свій ключ в Персональному кабінеті AlphaSMS
     */
    'SMSProvider' => [
        'login' => '+380',
        'pass' => '',
        'key' => '',
        'alias' => 'Назва'
    ],

    /**
     * Ключ до API Liqpay
     * знайдіть свій ключ в Персональному кабінеті Liqpay
     */
    'LiqPay' => [
        'class' => 'LiqPay',
        'public_key' => '',
        'private_key' => ''
    ],

);
