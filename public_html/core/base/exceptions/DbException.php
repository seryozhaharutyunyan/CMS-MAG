<?php
namespace core\base\exceptions;
use core\base\controllers\BaseMethods;


class DbException extends \Exception
{
    use BaseMethods;
    protected $messages;
    public function __construct($message='', $code=0){
        parent::__construct($message, $code);
        $this->messages = include_once 'messages.php';
        $error = $this->getMessage() ?? $this->messages[$this->getCode()];
        $error.="\r\n".'File:'.$this->getFile()."\r\n".'In linee'.$this->getLine()."\r\n";
        #if(!empty($this->messages[$this->getCode()])){
        #$this->message=$this->messages[$this->getCode()];
        #}
        $this->writeLog($error, 'db_log.txt');
    }
}