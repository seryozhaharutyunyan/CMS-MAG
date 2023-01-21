<?php

namespace core\base\controllers;
use core\base\exceptions\RouteException;
use core\base\models\UserModel;
use core\base\settings\Settings;
use core\base\controllers\BaseMethods;
abstract class BaseController
{
    use BaseMethods;

    protected $data;
    protected $header;
    protected $content;
    protected $footer;
    protected $page;
    protected $errors;
    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;
    protected $template;
    protected $styles;
    protected $scripts;
    protected $userId;
    protected $ajaxData;

    /**
     * @throws RouteException
     */
    public function route()
    {
        $controller = str_replace('/', '\\', $this->controller);
        try {
            $object = new \ReflectionMethod($controller, 'request');
            $object->invoke(new $controller, [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod
            ]);
        } catch (\ReflectionException $e) {
            throw new RouteException($e->getMessage());
        }
    }

    public function request($args)
    {
        $this->parameters = $args['parameters'];
        $inputData = $args['inputMethod'];
        $outputData = $args['outputMethod'];
        $data = $this->$inputData();
        if (method_exists($this, $outputData)) {
            $page = $this->$outputData($data);
            if (!empty($page)) {
                $this->page = $page;
            }
        } elseif (!empty($data)) {
            $this->page = $data;
        }
        if (!empty($this->errors)) {
            $this->writeLog($this->errors);
        }
        $this->getPage();
    }

    /**
     * @throws RouteException
     */
    protected function render($path = '', $parameters = [])
    {
        $template = '';
        if ($parameters) {
            extract($parameters);
        }
        if (empty($path)) {
            $class = new \ReflectionClass($this);
            $space = str_replace('\\', '/', $class->getNamespaceName() . '\\');
            $routes = Settings::get('routes');
            if ($space === $routes['user']['path']) {
                $template = TEMPLATE;
            } else {
                $template = ADMIN_TEMPLATE;
            }

            $path = $template .$this->getController();
        }
        ob_start();
        if (!@include_once $path . '.php') {
            throw new RouteException('otsustviye ' . $path);
        }
        return ob_get_clean();
    }

    protected function getPage()
    {
        if (is_array($this->page)) {
            foreach ($this->page as $block) {
                echo $block;
            }
        } else {
            echo $this->page;
        }
        exit();
    }

    /**
     * @throws \core\base\exceptions\DbException
     */
    protected function checkAuth($type=false){
        if(!($this->userId=UserModel::instance()->checkUser(false, $type))){
            $type && $this->redirect(\PATH);
        }
        if(\property_exists($this, 'userModel')){
            $this->userModel=UserModel::instance();
        }
    }
}