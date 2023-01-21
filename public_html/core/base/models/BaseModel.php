<?php

namespace core\base\models;
use core\base\exceptions\DbException;
abstract class BaseModel extends BaseModelMethods
{

    protected $db;

    /**
     * @throws DbException
     */
    protected function connect(){
        $this->db = new \mysqli(HOST, USER, PASSWORD, DB_NAME);
        if($this->db->connect_error){
            throw new DbException('Ошибка подключение к базе данных'.$this->db->connect_errno.' '.$this->db->connect_error);
        }
        $this->db->query("SET NAMES UTF8");
    }

    /**
     * @param $query
     * @param $crud r=SELECT c=INSERT u=UPDATE d=DELETE
     * @param bool $return_id
     * @return array|bool
     * @throws DbException
     */
    final public function query($query, $crud='r', bool $return_id=false){
        $result = $this->db->query($query);
        if($this->db->affected_rows===-1){
            throw new DbException('Ошибка SQL: '.$query.'---'.$this->db->errno.' '.$this->db->error);
        }
        switch($crud){
            case 'r':
                if($result->num_rows){
                    $res=[];
                    for($i=0;$i<$result->num_rows;$i++){
                        $res[]=$result->fetch_assoc();
                    }
                    return $res;
                }
                return false;
                break;
            case 'c':
                if($return_id){
                    return $this->db->insert_id;
                }
                return true;
                break;

            default:
                return true;
                break;
        }
    }

    /**
     * @throws DbException
     */
    final public function add($table, $set=[]){
        $set['fields']=(isset($set['fields']) && is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files']=(isset($set['files']) && is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
        if(!$set['files'] && !$set['fields']){
            return false;
        }
        $set['return_id']= isset($set['return_id']);
        $set['except']=(isset($set['except']) && is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;
        $insert_arr=$this->createInsert($set['fields'], $set['files'], $set['except']);
        $query="INSERT INTO $table {$insert_arr['fields']} VALUES {$insert_arr['value']}";
        return $this->query($query, 'c', $set['return_id']);
    }

    /**
     * @param $table db table name
     * @param array $set ['fields' =>['id','name'],
     * 'no_concat'=>false/true
     *'where' =>['id','name'],
     *'operand'=>['=','<>'],
     *'condition' =>['AND'],
     *'order' =>['fio', 'name'],
     *'order_direction' =>['ASC','DESC'],
     *'limit' =>'1']
     * 'join' =>[
     *[
     *'table' =>'category',
     *'fields' =>['id as j_id', 'name as j_name'],
     *'type'=>'left',
     *'where' =>['name'=>'ipad'],
     *'operand'=>['='],
     *'condition' =>['AND'],
     *'on'=>[
     *'table'=>'category',
     *]
     *'group_condition'=>'AND'
     *],
     *[
     *'table' =>'category',
     *'fields' =>['id as j_id', 'name as j_name'],
     *'type'=>'left',
     *'where' =>['name'=>'ipad'],
     *'operand'=>['='],
     *'condition' =>['AND'],
     *'on'=>[
     *'table'=>'category',
     *'fields' =>['id', 'parent_id']
     *]
     *]
     * @throws DbException
     */
    final public function get($table, array $set=[]){
        $fields = $this->createFields($set, $table);
        $where = $this->createWhere($set, $table);
        $order = $this->createOrder($set, $table);
        if(empty($where)){
            $new_where =true;
        }else{
            $new_where =false;
        }
        $join_arr=$this->createJoin($set, $table, $new_where);
        $join='';
        if(!empty($join_arr)){
            $fields.=$join_arr['fields'];
            $join=$join_arr['join'];
            $where.=$join_arr['where'];
        }
        $fields=rtrim($fields, ',');
        $limit = (!empty($set['limit'])) ? 'LIMIT ' . $set['limit'] : '';
        $query="SELECT $fields FROM $table $join $where $order $limit";
        if(!empty($set['return_query'])){
            return $query;
        }
        $res=$this->query($query);
        if(isset($set['join_structure']) && $set['join_structure'] && $res){
            $res=$this->joinStructure($res, $table);
        }
        return $res;
    }

    /**
     * @param $table db table name
     * @param array $set ['fields' =>['id'=>'1','name'=>'Hasmik'],
     *'where' =>['id','name'],
     *'operand'=>['=','<>'],
     *'condition' =>['AND'],
     *
     * @return array|bool
     * @throws DbException
     */
    final public function edit($table, array $set=[]){
        $set['fields']=(isset($set['fields']) && is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files']=(isset($set['files']) && is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
        if(!$set['files'] && !$set['fields']){
            return false;
        }
        $where='';
        $columns=$this->showColumns($table);
        if(!$columns){
            return false;
        }
        $set['except']=(isset($set['except']) && is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;
        if(!isset($set['all_rows'])){
            if(isset($set['where'])){
                $where=$this->createWhere($set);
            }else{
                if(isset($columns['id_row']) && !empty($columns['id_row']) && isset($set['fields'][$columns['id_row']]) && !empty($set['fields'][$columns['id_row']])){
                    $where="WHERE ".$columns['id_row'].'='.$set['fields'][$columns['id_row']];
                    unset($set['fields'][$columns['id_row']]);
                }
            }
        }
        if(isset($columns['id_row']) && !empty($columns['id_row']) && isset($fields['fields'][$columns['id_row']]) && !empty($fields['fields'][$columns['id_row']])){
            unset($fields['fields'][$columns['id_row']]);
        }
        $update = $this->createUpdate($set['fields'], $set['files'], $set['except']);
        $query = "UPDATE $table SET $update $where";
        return $this->query($query, 'c');
    }

    public function buildUnion($table, array $set)
    {
        if(\array_key_exists('fields', $set) && $set['fields']===null){
            return $this;
        }
        if(!array_key_exists('fields', $set) || empty($set['fields'])) {
            $set['fields'] = [];
            $columns = $this->showColumns($table);
            unset($columns['id_row'], $columns['multi_id_row']);
            foreach ($columns as $row => $item) {
                $set['fields'][] = $row;
            }
        }
            $this->union[$table]=$set;
            $this->union[$table]['return_query']=true;
            return $this;

    }

    /**
     * @throws DbException
     */
    public function getUnion($set=[]){
        if(!$this->union){
            return false;
        }
        $unionType=' UNION '.(!empty($set['type']) ? \strtoupper($set['type']).' ' : '');
        $maxCount=0;
        $maxTableCount='';
        foreach ($this->union as $key=>$item){
            $count=count($item['fields']);
            $joinFields='';
            if(!empty($item['join'])){
                foreach ($item['join'] as $table=>$data){
                    if(\array_key_exists('fields', $data) && !empty($data['fields'])){
                        $count+=\count($data['fields']);
                        $joinFields=$table;
                    }elseif (!\array_key_exists('fields', $data) || (empty($joinFields['data'] || $data['fields']===null))){
                        $columns = $this->showColumns($table);
                        unset($columns['id_row'], $columns['multi_id_row']);
                        $count+=\count($columns);
                        foreach ($columns as $field=>$value){
                            $this->union[$key]['join'][$table]['fields'][]=$field;
                        }
                        $joinFields=$table;
                    }
                }
            }else{
                $this->union[$key]['no_concat']=true;
            }
            if($count>$maxCount || ($count===$maxCount && $joinFields)){
                $maxCount=$count;
                $maxTableCount=$key;
            }
            $this->union[$key]['lastJoinTable']=$joinFields;
            $this->union[$key]['countFields']=$count;
        }
        $query='';
        if($maxCount && $maxTableCount){
            $query.='('.$this->get($maxTableCount, $this->union[$maxTableCount]).')';
            unset($this->union[$maxTableCount]);
        }
        foreach ($this->union as $key=>$item){
            for ($i=0; $i<$maxCount-$item['countFields']; $i++ ) {
                if (isset($item['countFields']) && $item['countFields'] < $maxCount) {
                    if ($item['lastJoinTable']) {
                        $item['join'][$item['lastJoinTable']]['fields'][] = null;
                    } else {
                        $item['fields'][] = null;
                    }
                }
            }
            $query && $query.=$unionType;
            $query.='('.$this->get($key, $item).')';
        }
        $order=$this->createOrder($set);
        $limit=!empty($set['limit']) ? ' LIMIT '. $set['limit'] : '';
        if(\method_exists($this, 'createPagination')){
            $this->createPagination($set, "($query)", $limit);
        }
        $query.=" $order $limit";
        $this->union=[];
        return $this->query(\trim($query));
    }
    /**
     * @param $table db table name
     * @param array $set ['fields' =>['id','name'],
     *'where' =>['id','name'],
     *'operand'=>['=','<>'],
     *'condition' =>['AND'],
     * 'join' =>[
     *[
     *'table' =>'category',
     *'fields' =>['id as j_id', 'name as j_name'],
     *'type'=>'left',
     *'where' =>['name'=>'ipad'],
     *'operand'=>['='],
     *'condition' =>['AND'],
     *'on'=>[
     *'table'=>'category',
     *]
     *'group_condition'=>'AND'
     *],
     *[
     *'table' =>'category',
     *'fields' =>['id as j_id', 'name as j_name'],
     *'type'=>'left',
     *'where' =>['name'=>'ipad'],
     *'operand'=>['='],
     *'condition' =>['AND'],
     *'on'=>[
     *'table'=>'category',
     *'fields' =>['id', 'parent_id']
     *]
     *]
     */
    /**
     * @throws DbException
     */
    public function delete($table, array $set=[]){
        $table=trim($table);
        $where=$this->createWhere($set, $table);
        $columns=$this->showColumns($table);
        if(!$columns){
            return false;
        }
        if(isset($set['fields']) && is_array($set['fields']) && !empty($set['fields'])){
            if(isset($columns['id_row']) && !empty($columns['id_row'])){
                $key=array_search($columns['id_row'], $set['fields']);
                if($key!==false){
                    unset($set['fields'][$key]);
                }
                $fields=[];
                foreach($set['fields'] as $value){
                    $fields[$value]=$columns[$value]['Default'];
                }
                $update=$this->createUpdate($fields, false, false);
                $query="UPDATE $table SET $update $where";
            }
        }else{
            $join_arr=$this->createJoin($set, $table);
            $join=$join_arr['join'];
            $join_table=$join_arr['tables'];
            $query="DELETE $table $join_table FROM $table $join $where";
        }

        return $this->query($query, 'u');
    }
}