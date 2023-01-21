<?php
    function print_arr($arr){
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }
if(!function_exists('mb_str_replace')){
    function mb_str_replace($search, $replace, $string)
    {
        $charset = mb_detect_encoding($string);
        $unicodeString = iconv($charset, "UTF-8", $string);
        return str_replace($search, $replace, $unicodeString);
    }
}