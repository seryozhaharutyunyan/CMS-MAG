<?php

namespace core\user\controllers;

use core\base\controllers\BaseController;
use core\user\model\Model;

abstract class BaseUser extends BaseController
{
    protected $model;
    protected $table;
    protected $set;
    protected $menu;

    protected $socials;

    /**
     * @throws \core\base\exceptions\DbException
     */
    protected function inputData(){
        $this->init();
        !$this->model && $this->model=Model::instance();
        $this->set=$this->model->get('settings', [
            'order'=>['id'],
            'limit'=>1
        ]);
        $this->set && $this->set=$this->set[0];
        $this->menu['catalog']=$this->model->get('category', [
            //'where'=>['visible'=>1],
            //'order'=>['menu_position']
        ]);
        $this->menu['information']=$this->model->get('information', [
            //'where'=>['visible'=>1, 'top_menu'=>1],
            //'order'=>['menu_position']
        ]);
        $this->socials=$this->model->get('socials', [
            'where'=>['visible'=>1],
            'order'=>['menu_position']

        ]);
    }

    /**
     * @throws \core\base\exceptions\RouteException
     */
    protected function outputData(){
        if(!$this->content){
            $args=func_get_arg(0);
            $vars=$args ? $args : [];
            //if(!$this->template){
            //$this->template=ADMIN_TEMPLATE.'show';
            //}
            $this->content=$this->render($this->template,$vars);
        }
        $this->header=$this->render(TEMPLATE.'include/header', $vars);
        $this->footer=$this->render(TEMPLATE.'include/footer', $vars);
        return $this->render(TEMPLATE.'layout/default');
    }
    protected function img($img='', $teg=false){
        if(!$img && \is_dir($_SERVER['DOCUMENT_ROOT'].\PATH.\UPLOAD_DIR.\DEFAULT_IMG)){
            $dir=\scandir($_SERVER['DOCUMENT_ROOT'].\PATH.\UPLOAD_DIR.\DEFAULT_IMG);
            $imgArr=\preg_grep('/'.$this->getController().'\./i', $dir) ?: \preg_grep('/default\./i', $dir);
            $imgArr && $img=\DEFAULT_IMG.'/'.\array_shift($imgArr);
        }
        if($img){
            $path=\PATH.\UPLOAD_DIR.$img;
            if(!$teg){
                return $path;
            }
            echo '<img src="'.$path.'" alt="image" title="image">';
        }
    }
    protected function alias($alias='', $queryString=''){
        $str='';
        $aliasStr='';
        if($queryString){
            if(\is_array($queryString)){
                foreach ($queryString as $key=>$item){
                    $str.=(!$str? '?': '&');
                    if(\is_array($item)){
                        $key.=[];
                        foreach ($item as $value){
                            $str.=$key.'='.$value;
                        }
                    }else{
                        $str.=$key.'='.$item;
                    }
                }
            }else{
                if(\strpos($queryString, '?')===false){
                    $str='?'.$str;
                }
                $str.=$queryString;
            }
        }
        if(\is_array($alias)){
            foreach ($alias as $key=>$item){
                if(!\is_numeric($key) && !empty($item)){
                    $aliasStr.=$key.'/'.$item.'/';
                }elseif ($item){
                    $aliasStr.=$item.'/';
                }
            }
            $alias=trim($aliasStr,'/');
        }
        if(!$alias || $alias==='/'){
            return \PATH.$str;
        }
        if(\preg_match('/^\s*https?:\/\//i', $alias)){
            return $alias.$str;
        }
        return \preg_replace('/\/{2,}/', '/', \PATH.$alias.\END_SLASH.$str);

    }
}