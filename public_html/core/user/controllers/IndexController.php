<?php
namespace core\user\controllers;
use core\base\controllers\BaseController;

class IndexController extends BaseUser
{
    /**
     * @throws \core\base\exceptions\RouteException
     */
    protected function inputData(){
        parent::inputData();
    }

}