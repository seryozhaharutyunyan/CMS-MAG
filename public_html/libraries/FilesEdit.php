<?php

namespace libraries;

class FilesEdit
{
    protected $imgArr=[];
    protected $directory;
    protected $uniqueFile=true;
    public function addFile($directory=''): array
    {
        $directory=\trim($directory, ' /');
        $directory.='/';
        $directory && !\preg_match('/\/$/', $directory) && $directory.='/';
        $this->setDirectory($directory);
        foreach ($_FILES as $key=>$file){
            if(\is_array($file['name'])){
                $file_arr=[];
                foreach($file['name'] as $i=>$value){
                    if (!empty($file['name'][$i])){
                        $file_arr['name']=$file['name'][$i];
                        $file_arr['type']=$file['type'][$i];
                        $file_arr['tmp_name']=$file['tmp_name'][$i];
                        $file_arr['error']=$file['error'][$i];
                        $file_arr['size']=$file['size'][$i];
                        $res_name=$this->createFile($file_arr);
                        if($res_name){
                            $this->imgArr[$key][$i]=$directory.$res_name;
                        }
                    }
                }
            }else{
                if($file['name']){
                    $res_name=$this->createFile($file);
                    if($res_name){
                        $this->imgArr[$key]=$directory.$res_name;
                    }
                }
            }
        }
        return $this->getFiles();
    }
    protected function createFile($file){
        $fileNameArr=\explode('.',$file['name']);
        $ext=$fileNameArr[\count($fileNameArr)-1];
        unset($fileNameArr[\count($fileNameArr)-1]);
        $fileName=\implode('.', $fileNameArr);
        $fileName= (new TextModify())->translit($fileName);
        $fileName=$this->checkFile($fileName, $ext);
        $fileFullName=$this->directory.$fileName;
        if($this->uploadFile($file['tmp_name'], $fileFullName)){
            return $fileName;
        }
        return false;
    }
    protected function checkFile($fileName, $ext, $fileLastName=''): string
    {
        if(!file_exists($this->directory.$fileName.$fileLastName.'.'.$ext) || !$this->uniqueFile){
            return $fileName.$fileLastName.'.'.$ext;
        }
        return $this->checkFile($fileName,$ext, '_'.hash('crc32', \time().\mt_rand(0,100)));

    }
    public function  setUniqueFile($value){
        $this->uniqueFile=$value ? true : false;
    }
    public function setDirectory($directory){
        $this->directory=$_SERVER['DOCUMENT_ROOT'].\PATH.\UPLOAD_DIR.$directory;
        if(!\file_exists($this->directory)){
            \mkdir($this->directory, 0777, true);
        }
    }
    protected function uploadFile($tmpName, $dest): bool
    {
        if(\move_uploaded_file($tmpName,$dest)){
            return true;
        }
        return false;
    }
    public function getFiles(): array
    {
        return $this->imgArr;
    }
}