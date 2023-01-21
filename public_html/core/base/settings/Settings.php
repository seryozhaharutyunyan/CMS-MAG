<?php

namespace core\base\settings;
use core\base\controllers\Singleton;
class Settings
{
    use Singleton;
    private $routes=[
        'admin'=>[
            'alias'=>'admin',
            'path'=>'core/admin/controllers/',
            'hrURL'=>false,
            'routes'=>[

            ],
        ],
        'settings'=>[
            'path'=>'core/base/settings/'
        ],
        'plugins'=>[
            'path'=>'core/plugins/',
            'hrURL'=>false,
            'dir'=>false,
            'routes'=>[
            ],
        ],
        'user'=>[
            'path'=>'core/user/controllers/',
            'hrURL'=>true,
            'routes'=>[

            ],
        ],
        'default'=>[
            'controller'=>'IndexController',
            'inputMethod'=>'inputData',
            'outputMethod'=>'outputData'
        ]
    ];
    private $formTemplate=PATH.'core/admin/views/include/form_templates/';
    private $templateArr=[
        'text'=>['name', 'pone', 'email','alias','external_alias'],
        'textarea'=>['content', 'keywords'],
        'radio'=>['visible','top_menu'],
        'checkboxlist'=>['filters', 'goods'],
        'select'=>['menu_position', 'parent_id'],
        'img'=>['img'],
        'gallery_img'=>['gallery_img']
    ];
    private $fileTemplates=[
        'img','gallery_img'
    ];
    private $projectTables=[
		'category'=>[
			'name'=>'Категории',
			'img'=>''
			],
	    'products'=>[
			'name'=>'Тавари',
			'img'=>''
			],
        'goods'=>['name'=>'products'],
        'filters'=>['name'=>'filter'],
        'settings'=>['name'=>'Настройки системы'],
        'socials'=>[]
    ];
	private $translate=[
		'name' => ['Название', 'Не более 100 символов'],
        'keywords'=>['Ключевые слова', 'Не более 70 символов'],
        'content'=>['Содержание', 'Не более 500 символов'],
        'img'=>['Картинка'],
        'gallery_img'=>['Галерея'],
        'visible'=>[],
        'menu_position'=>[],
        'parent_id'=>[],
        'top_menu'=>[],
	];
    private $manyToMany=[
        'goods_filters'=>['goods','filters'],// 'type'=>'root'||'child'||'all'

    ];
	private $blockNeedle=[
		'vg-rows'=>['keywords'],
		'vg-img'=>['img'],
		'vg-content'=>['content']
	];
	private $rootItems=[
		'name'=>'Корневая',
		'tables'=>['category', 'articles', 'products', 'page', 'goods', 'filters','socials']
	];
    private $defaultTable='goods';
    private $expansion='core/admin/expansion/';
	private $radio=[
		'visible'=>['Нет','Да', 'default'=>'Да'],
        'top_menu'=>['Нет','Да', 'default'=>'Да'],
	];
    private $validation=[
        'name'=>['empty'=>true, 'trim'=>true, 'len'=>5],
        'email'=>['empty'=>true, 'trim'=>true],
        'password'=>['crypt'=>true, 'empty'=>true, 'trim'=>true, 'len'=>8],
        'price'=>['int'=>true],
        'keywords'=>[],
        'content'=>['trim'=>true, ],
        'description'=>['count'=>160, 'trim'=>true],
    ];
    private $messages='core/base/messages/';
    static public function get($property){
        return self::instance()->$property;
    }
    public function clueProperties($class): array
    {
        $bestProperties =[];
        foreach($this as $name=>$value){
            $property = $class::get($name);
            if(is_array($property) && is_array($value)){
                $bestProperties[$name]=$this->arrayMergenRecusive($this->$name, $property);
                continue;
            }
            if(!$property){
                $bestProperties[$name]=$this->$name;
            }
        }
        return $bestProperties;
    }
    public function arrayMergenRecusive(){
        $arrays=func_get_args();
        $best=array_shift($arrays);
        foreach($arrays as $array){
            foreach($array as $key=>$value){
                if(is_array($value) && is_array($best[$key])){
                    $best[$key]=$this->arrayMergenRecusive($best[$key], $value);
                }elseif(is_int($key)) {
                        if (!in_array($value, $best)) {
                            $best[] = $value;
                            continue;
                        }
                }else{
                    $best[$key] = $value;
                }
            }
            return $best;
        }
    }
}