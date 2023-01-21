<?php

namespace core\base\settings;
use core\base\settings\Settings;
use core\base\controllers\Singleton;


trait BaseSettings
{
    use Singleton{
        instance as SingletonInstance;
    }
    private $bestSettings;
    static private function instance(){
        if(self::$_instance instanceof self){
            return self::$_instance;
        }
        self::SingletonInstance()->bestSettings=Settings::instance();
        $bestProperties=self::$_instance->bestSettings->clueProperties(get_class());
        self::$_instance->setProperty($bestProperties);
        return self::$_instance;
    }
    protected function setProperty($properties){
        if($properties){
            foreach($properties as $name=>$property){
                $this->$name=$property;
            }
        }
    }
    static public function get($property)
    {
        return self::instance()->$property;
    }
}