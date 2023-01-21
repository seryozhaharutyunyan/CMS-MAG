<?php

namespace core\base\controllers;

use DateTime;

trait BaseMethods
{
    protected $styles;
    protected $scripts;
    protected function init($admin=false)
    {
        if (!$admin) {
            if (!empty(USER_CSS_JS['styles'])) {
                foreach (USER_CSS_JS['styles'] as $item) {
                    $this->styles[] = (!\preg_match('/^\s*https?:\/\//i', $item )?PATH . TEMPLATE : ''). trim($item, '/');
                }
            }
            if (!empty(USER_CSS_JS['scripts'])) {
                foreach (USER_CSS_JS['scripts'] as $item) {
                    $this->scripts[] = (!\preg_match('/^\s*https?:\/\//i', $item )?PATH . TEMPLATE : ''). trim($item, '/');
                }
            }
        } else {
            if (!empty(ADMIN_CSS_JS['style'])) {
                foreach (ADMIN_CSS_JS['style'] as $item) {
                    $this->styles[] =(!\preg_match('/^\s*https?:\/\//i', $item )?PATH . \ADMIN_TEMPLATE : ''). trim($item, '/');
                }
            }
            if (!empty(ADMIN_CSS_JS['script'])) {
                foreach (ADMIN_CSS_JS['script'] as $item) {
                    $this->scripts[] = (!\preg_match('/^\s*https?:\/\//i', $item )?PATH . \ADMIN_TEMPLATE : ''). trim($item, '/');

                }
            }
        }
    }
    protected function clearStr($str){
        if(is_array($str)){
            foreach($str as $key => $value) {
                $str[$key] =$this->clearStr($value);
                return $str;
            }
        }else{
           return trim(strip_tags($str));
        }
    }

    protected function clearNum($num){
        if(!empty($num) && \preg_match('/\d/', $num)){
            $num=\preg_replace('/[^\d.]/', '', $num)*1;
        }else{
            $num=0;
        }
        return $num;
    }
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }
    protected function isAJAX(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
    }
    protected function redirect($http='', $code=''){
        if(!empty($code)){
            $codes=['301'=>'HTTP/1.1 301 Move Permanently'];
            if(!empty($codes[$code])){
                header($codes[$code]);
            }
        }
        if(!empty($http)){
            $redirect=$http;
        }else{
            $redirect= $_SERVER['HTTP_REFERER'] ?? PATH;
        }
        header("Location: $redirect");
        exit();
    }
    protected function getStyles(){
        if($this->styles){
            foreach ($this->styles as $style){
                echo '<link rel="stylesheet" href="'.$style.'">';
            }
        }
    }
    protected function getScripts(){
        if($this->scripts){
            foreach ($this->scripts as $script){
                echo '<script src="'.$script.'"></script>';
            }
        }
    }
    protected function writeLog($errors, $file='log.txt', $event='Fault'){
        $dateTime= new \DateTime();
        $str=$event.': '.$dateTime->format('d.m.Y H:i:s').'---'.$errors."\r\n";
        file_put_contents('log/'.$file, $str, FILE_APPEND);
    }
    protected function msMode(){
        if(!\MS_MODE){
            if(\preg_match('/msie|trident.+?rv\s*:/i', $_SERVER['HTTP_USER_AGENT'])){
                exit('Ваш браузер очень старый. Пажалушта обнавите или снемите её.');
            }
        }
    }
    protected function getController(){
        return $this->controller ?:
            $this->controller=\preg_split('/_?controller/', \strtolower(\preg_replace('/([^A-Z])([A-Z])/', '$1_$2',
                (new \ReflectionClass($this))->getShortName())), 0, \PREG_SPLIT_NO_EMPTY)[0];
    }
}