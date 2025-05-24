<?php

/**
 * Налаштування пресетів для зображень (за замовчуванням)
 *
 * Увага! Не змінюйте цей файл.
 *
 * Для індивідуальних налаштувань використовуйте файли у /assets/custom/Config/
 *
 *
 * назва пресета
 * 'cart-goods' => [

 * назва пропорції, має збігатися з назвами пропорцій у файлі assets/tvs/multitv/configs/goods_images
 * якщо не збігається, то img1x1 в value не буде замінено на парамтери пропорції
 * 'ratio' => 'img1x1',

 * буде використана як частина шляху до вихідного файла зображення
 * 'name' => '1x1-checkout',
 *
 * параметри для обробника зображень,
 * 'value' => 'w=250,h=250,q=80,far=TL,iar=1,img1x1'
 *
 * де,
 * w, h ширина та довжина вихідного зображення
 * q якість стиснення
 * far=TL,iar=1 параметри в форматі обробника phpThumb (далі перетворимо їх під свій обробник)
 * img1x1 буде замінено на значення параметрів пропорцій ratio
 *
 * ]
 *
 */

return [

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
            'ratio' => 'img16x9',
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

        'image-max' => [
            'ratio' => 'image-max',
            'name' => 'image-max',
            'value' => 'w=800,q=80,fltr{}=watermark'
        ],
        'image-max-webp' => [
            'ratio' => 'image-max',
            'name' => 'image-max',
            'value' => 'w=800,q=80,webp=1,fltr{}=watermark'
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
    ]
];
