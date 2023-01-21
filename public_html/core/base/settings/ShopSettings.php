<?php

namespace core\base\settings;
use core\base\settings\Settings;


class ShopSettings extends Settings
{
    use BaseSettings;
    private $routes=[
        'plugins'=>[
            'path'=>'core/plugins/',
            'hrURL'=>false,
            'dir'=>false,
            'routes'=>[
            ]
        ]
    ];

    private $templateArr=[
        'text'=>['price', 'short'],
        'textarea'=>['good_content']
    ];

}