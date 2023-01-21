<?php

namespace core\base\controllers;

class BaseRoute
{
    use Singleton, BaseMethods;

    /**
     * @throws \core\base\exceptions\RouteException
     */
    public static function routeDirection(){
        if(self::instance()->isAJAX()){
            exit((new BaseAjax())->route());
        }
        RouteController::instance()->route();
    }
}