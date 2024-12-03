<?php

$settings['display'] = 'datatable';
$settings['fields'] = array(
    'thumb' => array(
        'caption' => 'Thumbnail',
        'type' => 'thumb',
        'thumbof' => 'image',
    ),
    'image' => array(
        'caption' => 'Зображення',
        'type' => 'image'
    ),
    'img16x9' =>array (
        'caption' => 'Пропорція 16x9',
        'type' => 'crop',
        'cropof' => 'image'
    ),
    'img4x3' =>array (
        'caption' => 'Пропорція 4x3',
        'type' => 'crop',
        'cropof' => 'image'
    ),
    'img1x1' =>array (
        'caption' => 'Пропорція 1x1',
        'type' => 'crop',
        'cropof' => 'image'
    ),
    'img2x3' =>array (
        'caption' => 'Пропорція 2x3',
        'type' => 'crop',
        'cropof' => 'image'
    ),
    'id' => array(
        'caption' => 'ID',
        'type' => 'text',
        'default' => 'img{i}'
    ),
    'legend' => array(
        'caption' => 'Опис',
        'type' => 'text'
    ),
);

$settings['form'] = array(
    array(
        'caption' => 'Основні',
        'content' => array(
            'thumb' => array(),
            'image' => array(),
            'img16x9' => array(),
            'img4x3' => array(),
            'img1x1' => array(),
            'img2x3' => array()
        )
    ),
    array(
        'caption' => 'Дані',
        'content' => array(
            'id' => array(),
            'legend' => array(),
        )
    )
);
/*
$settings['buttons'] = array(
    'actionButtons' => array(
        'position' => "topright",
            "buttons" => array(
                "csvexport" => array (
                    "caption" => "CSV exportiern",
                    "icon"=> "table_save.png",
                    "processor"=> "csvexport",
                    "form"=> array(
                            "caption" => "CSV exportiern",
                            "content"=> array()
                    )
                )
            )
    )
);
*/
$settings['columns'] = array(
    array(
        'caption' => 'Зображення',
        'fieldname' => 'image',
        'width' => '150',
        'render' => '<img src="/[+image:replace=`assets/,assets/.thumbs/`+]" height="150">',
    ),
    array(
        'caption' => 'Ід',
        'fieldname' => 'id',
        'width' => '100',
    ),
    array(
        'caption' => 'Опис та додаткові дані',
        'fieldname' => 'legend',
        'render' => '[+legend:is=``:then=``:else=`[+legend+]<br>`+]<span class="text-secondary">[+img16x9:is=``:then=``:else=`<i class="fa fa-picture-o"></i>16x9`+] [+img4x3:is=``:then=``:else=`<i class="fa fa-picture-o"></i>4x3`+] [+img1x1:is=``:then=``:else=`<i class="fa fa-picture-o"></i>1x1`+] [+img2x3:is=``:then=``:else=`<i class="fa fa-file-image-o"></i> 2x3`+]</span>'
    )
);

$settings['configuration'] = array(
    'pagination' => false,
    'displayLength' => 100,
    'display' => 'All',
    'enablePaste' => false,
    'sorting'   => false
);

$settings['templates'] = array(
    'outerTpl' => '<div class="images">[+wrapper+]</div>',
    'rowTpl' => '<div class="image"><img src="[+image+]" alt="[+legend+]" />'
);
