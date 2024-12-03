<?php

$settings['display'] = 'datatable';
$settings['fields'] = array(
    'goods_avail' => array(
        'caption' => 'Наявність',
        'type' => 'text',
        'width' => '25'
    ),
    'goods_price' => array(
        'caption' => 'Ціна',
        'type' => 'text',
        'width' => '60'
    ),
    'goods_price_old' => array(
        'caption' => 'Ціна стара',
        'type' => 'text',
        'width' => '60'
    ),
    'goods_code' => array(
        'caption' => 'Код товару',
        'type' => 'text',
        'width' => '80'
    ),
    'goods_name' => array(
        'caption' => 'Назва',
        'type' => 'text',
        'width' => '200'
    ),
    'thumb' => array(
        'caption' => 'Thumbnail',
        'type' => 'thumb',
        'thumbof' => 'image',
    ),
    'image' => array(
        'caption' => 'Фото',
        'type' => 'image',
        'width' => '200'
    ),
    'img16x9' => array(
        'caption' => 'Пропорція 16x9',
        'type' => 'crop',
        'cropof' => 'image'
    ),
    'img4x3' => array(
        'caption' => 'Пропорція 4x3',
        'type' => 'crop',
        'cropof' => 'image'
    ),
    'img1x1' => array(
        'caption' => 'Пропорція 1x1',
        'type' => 'crop',
        'cropof' => 'image'
    ),
    'img2x3' => array(
        'caption' => 'Пропорція 2x3',
        'type' => 'crop',
        'cropof' => 'image'
    ),
);
$settings['form'] = array(
    array(
        'caption' => 'Опис',
        'content' => array(
            'goods_avail' => array(),
            'goods_code' => array(),
            'goods_name' => array(),
            'goods_price' => array(),
            'goods_price_old' => array()
        )
    ),
    array(
        'caption' => 'Фото',
        'content' => array(
            'thumb' => array(),
            'image' => array(),
            'img16x9' => array(),
            'img4x3' => array(),
            'img1x1' => array(),
            'img2x3' => array()
        )
    ),
);

$settings['templates'] = array(
    'outerTpl' => '[+wrapper+]',
    'rowTpl' => '
[+img_bg+]
[+row.number+]
[+iteration+]
[+title:ucase+]
[+row.class+]
<br/>'
);

$settings['configuration'] = array(
    'pagination' => false,
    'displayLength' => 100,
    'display' => 100,
    'enablePaste' => false,
    'sorting' => false
);

