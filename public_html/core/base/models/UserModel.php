<?php

namespace core\base\models;

use core\base\controllers\BaseMethods;
use core\base\controllers\Singleton;
use core\base\exceptions\AuthException;

class UserModel extends BaseModel
{
    use Singleton;
    use BaseMethods;
    private $cookieAdminName='identifier';
    private $cookieName='WQEngineCache';
    private $userData=[];
    private $error;
    private $userTable='visitors';
    private $adminTable='users';
    private $blockedTable='blocked_access';

    public function getAdminTable(): string
    {
        return $this->adminTable;
    }
    public function getUserTable(): string
    {
        return $this->userTable;
    }
    public function getBlockedTable(): string
    {
        return $this->blockedTable;
    }
    public function getLastError(){
        return $this->error;
    }

    /**
     * @throws \core\base\exceptions\DbException
     */
    public function setData(){
        $this->cookieName=$this->cookieAdminName;
        $this->userTable=$this->adminTable;
        if(!\in_array($this->userTable, $this->showTables())){
            $query='CREATE TABLE '. $this->userTable.'
            (
                id int auto_increment primary key,
                name varchar(255),
                surname varchar(255) null,
                age varchar(255),
                pone varchar(255),
                email varchar(255),
                password varchar(32),
                credentials text null,
                created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP  
            )
            charset=utf8
            ';
            if(!$this->query($query, 'u')){
                exit('Ошибка создания таблици '.$this->userTable);
            }
            $this->add($this->userTable, [
                'fields'=>['name'=>'admin', 'age'=>'05.10.1991', 'pone'=>'+79283895945',
                    'email'=>'admin@mail.ru', 'password'=>md5('071172')]
            ]);
        }
        if(!\in_array($this->blockedTable, $this->showTables())){
            $query='CREATE TABLE '. $this->blockedTable.'
            (
                id int auto_increment primary key,
                ip varchar(255) null,
                email varchar(255) null,
                trying tinyint(1) null,
                time datetime null
            )
            charset=utf8
            ';
            if(!$this->query($query, 'u')){
                exit('Ошибка создания таблици '.$this->userTable);
            }
        }

    }

    /**
     * @throws \core\base\exceptions\DbException
     */
    public function checkUser($id, $admin=false){
        $admin && $this->userTable!==$this->adminTable && $this->setData();
        $method='unPackage';
        if($id){
            $this->userData['id']=$id;
            $method='set';
        }
        try {
            $this->$method();
        }catch (AuthException $e){
            $this->error=$e->getMessage();
            !empty($e->getCode()) && $this->writeLog($this->error, 'log_user.txt');
            return false;
        }
        return $this->userData;
    }

    /**
     * @throws AuthException
     */
    private function set(): bool
    {
        $cookieString=$this->package();
        if($cookieString){
            \setcookie($this->cookieName, $cookieString, time()+60*60*24*365*10, \PATH);
            return true;
        }
        throw new AuthException('Ошибка формирования cookie', 1 );
    }

    /**
     * @throws AuthException
     */
    private function package(): string
    {
        if(isset($this->userData['id']) && !empty($this->userData['id'])){
            $data['id']=$this->userData['id'];
            $data['version']=\COOKIE_VERSION;
            $data['cookieTime']=date('Y-m-d H:i:s');
            return Crypt::instance()->encrypt(\json_encode($data));
        }
        throw new AuthException('Не корректный идентификатор пользователя-'.$this->userData['id'], 1);
    }

    /**
     * @throws AuthException
     * @throws \core\base\exceptions\DbException
     */
    private function unPackage(): bool
    {
        if(empty($_COOKIE[$this->cookieName])){
            throw new AuthException('Отсуствует cookie пользователя');
        }
        $data=\json_decode(Crypt::instance()->decrypt($_COOKIE[$this->cookieName]), true);
        if(empty($data['id']) || empty($data['version']) || empty($data['cookieTime'])){
            $this->logout();
            throw new AuthException('Некорректные данные в cookie пользователя', 1);
        }
        $this->validate($data);
        $this->userData=$this->get($this->userTable,[
            'where'=>['id'=>$data['id']]
        ]);
        if(!$this->userData){
            $this->logout();
            throw new AuthException('Пользователь не найден', 1);
        }
        $this->userData=$this->userData[0];
        return true;
    }

    /**
     * @throws AuthException
     * @throws \Exception
     */
    private function validate($data){
        if(!empty(\COOKIE_VERSION)){
            if($data['version']!==\COOKIE_VERSION){
                $this->logout();
                throw new AuthException('Некоректная версия cookie');
            }
        }
        if(!empty(\COOKIE_TIME)){
            if((new \DateTime())>(new \DateTime($data['cookieTime']))->modify(\COOKIE_TIME.' minutes')){
                throw new AuthException('Привишена бремя бездействи пользователя');
            }
        }
    }
    public function logout(){
        \setcookie($this->cookieName, '', 1 ,\PATH);
    }
}