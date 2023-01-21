<?php

namespace core\base\models;

use core\base\exceptions\DbException;

abstract class BaseModelMethods
{
    protected $sql_func=['NOW()','RAND()'];
    protected $tableRows;
    protected $union=[];

    /**
     * @throws \core\base\exceptions\DbException
     */
    protected function createFields($set, $table='', $join=false): string
    {
        if(\array_key_exists('fields', $set) && $set['fields']===null){
            return '';
        }
        $concat_table='';
        $alias_table=$table;
        if(!isset($set['no_concat'])){
            $arr=$this->createTableAlias($table);
            $concat_table.=$arr['alias'].'.';
            $alias_table=$arr['alias'];
        }
        $fields='';
        $join_structure=false;
        if($join || isset($set['join_structure']) && $set['join_structure'] && $table) {
            $join_structure = true;
            $this->showColumns($table);
            if (isset($this->tableRows[$table]['multi_id_row'])) {
                $set['fields'] = [];
            }
        }
        if(!isset($set['fields']) || !is_array($set['fields'])|| empty($set['fields'])){
            if(!$join){
                $fields.=$concat_table.'*,';
            }else{
                foreach ($this->tableRows[$alias_table] as $key=>$item){
                    if($key!=='id_row' && $key!=='multi_id_row'){
                        $fields.=$concat_table.$key.' as TABLE_'.$alias_table.'_TABLE_'.$key.',';
                    }
                }
            }
        }else{
            $id_field=false;
            foreach ($set['fields'] as $field){
                if($join_structure && !$id_field && $this->tableRows[$alias_table]===$field){
                    $id_field=true;
                }
                if($field || $field===null){
                    if($field===null){
                        $fields.="NULL,";
                    }
                    if($join && $join_structure){
                        if(preg_match('/^(.+)?\s+as\s+(.+)/i', $field, $matches)){
                            $fields.=$concat_table.$matches[1].' as TABLE_'.$alias_table.'_TABLE_'.$matches[2].',';
                        }else{
                            $fields.=$concat_table.$field.' as TABLE_'.$alias_table.'_TABLE_'.$field.',';
                        }
                    }else{
                        $fields.=(!\preg_match('/(\([^()]*\))|(case\s+.+?\s+end)/i', $field) ? $concat_table : ''). $field.',';
                    }
                }
            }
            if(!$id_field && $join_structure){
                if($join){
                    $fields.=$concat_table.$this->tableRows[$alias_table]['id_row'].' as TABLE_'.$alias_table.'_TABLE_'.$this->tableRows[$alias_table]['id_row'].',';
                }else{
                    $fields.=$concat_table.$this->tableRows[$alias_table]['id_row'].',';
                }
            }
        }
        return $fields;
    }
    protected function createOrder($set, $table=''): string
    {
	    $table=($table && (!isset($set['no_concat']) || !$set['no_concat'])) ? $this->createTableAlias($table)['alias'].'.' : '';
        $order_by='';
        if(isset($set['order']) && $set['order']){
            $set['order']=(array)$set['order'];
            $set['order_direction'] = (isset($set['order_direction']) && $set['order_direction'])
                ? (array)$set['order_direction'] : ['ASC'];
            $i=0;
            $order_by="ORDER BY ";
            foreach($set['order'] as $order){
                if(!empty($set['order_direction'][$i])){
                    $order_direction=strtoupper($set['order_direction'][$i]);
                    $i++;
                }else{
                    $order_direction=strtoupper($set['order_direction'][$i-1]);
                }
                if(\in_array($order, $this->sql_func)){
                    $order_by.=$order.',';
                }elseif(\is_numeric($order)){
                    $order_by.=$order.' ' . $order_direction. ',';
                }else{
                    $order_by.=$table . $order.' ' . $order_direction. ',';
                }

            }
            $order_by=rtrim($order_by, ',');
        }
        return $order_by;
    }
    protected function createWhere($set, $table='', $instruction='WHERE'){
        $table=($table && (!isset($set['no_concat']) || !$set['no_concat'])) ? $this->createTableAlias($table)['alias'].'.' : '';
        $where='';
		if(isset($set['where']) && is_string($set['where'])){
			return $instruction.' '.trim($set['where']);
		}
        if(isset($set['where']) && is_array($set['where']) && !empty($set['where'])) {
            $set['operand']=(isset($set['operand']) && is_array($set['operand']) && !empty($set['operand'])) ? $set['operand'] : ['='];
            $set['condition']=(isset($set['condition']) && is_array($set['condition']) && !empty($set['condition'])) ? $set['condition'] : ['AND'];
            $where=$instruction;
            $i=0;
            $j=0;
            foreach($set['where'] as $key=>$value){
                $where.=' ';
                if(!empty($set['operand'][$i])){
                    $operand=$set['operand'][$i];
                    $i++;
                }else{
                    $operand=$set['operand'][$i-1];
                }
                if(!empty($set['condition'][$j])){
                    $condition=$set['condition'][$j];
                    $j++;
                }else{
                    $condition=$set['condition'][$j-1];
                }
                if($operand==='IN' || $operand==='NOT IN'){
                    if(is_string($value) && strpos($value, 'SELECT')===0){
                        $in_str= $value;
                    }else{
                        if(is_array($value)){
                            $temp_value=$value;
                        }else{
                            $temp_value=explode(',',$value);
                        }
                        $in_str='';
                        foreach ($temp_value as $v){
                            $in_str .= "'". addslashes(trim($v))."',";
                        }
                    }
                    $where.=$table.$key.' '.$operand.' ('.trim($in_str, ',').') '.$condition;
                }elseif(strpos($operand, 'LIKE')!==false){
                    $like_template =explode('%', $operand);
                    foreach($like_template as $lt_key=>$lt){
                        if(!$lt){
                            if(!$lt_key){
                                $value ='%'.$value;
                            }else{
                                $value.='%';
                            }
                        }
                    }
                    $where.=$table.$key.' LIKE '."'".addslashes($value)."' $condition";
                }else{
                    if(strpos($value, 'SELECT') === 0){
                        $where.=$table.$key.$operand."(".$value.") $condition";
                    }elseif($value===null || $value==='NULL') {
                        if($operand==='='){
                            $where.=$table.$key.' IS NULL '.$condition;
                        }else{
                            $where.=$table.$key.' IS NOT NULL '.$condition;
                        }
                    }else{
                        $where.=$table.$key.$operand."'".addslashes($value)."' $condition";
                    }
                }
            }
            $where=substr($where, 0, strrpos($where, $condition));
        }
        return $where;
    }

    /**
     * @throws DbException
     */
    protected function createJoin($set, $table, $new_where=false): array
    {
        $fields='';
        $join='';
        $where='';
        $tables='';
        if (isset($set['join']) && !empty($set['join'])){
            $join_table=$table;
            foreach($set['join'] as $key=>$value){
                if(is_int($key)) {
                    if (!isset($value['table'])) {
                        continue;
                    } else {
                        $key = $value['table'];
                    }
                }
                $concatTable=$this->createTableAlias($key)['alias'];
                if(!empty($join)){
                    $join.=' ';
                }
                if(isset($value['on']) && !empty($value['on'])){
                    $join_fields=[];
                    if(isset($value['on']['fields']) && \is_array($value['on']['fields']) && count($value['on']['fields'])===2){
                        $join_fields=$value['on']['fields'];
                    }elseif(count($value['on'])===2){
                        $join_fields=$value['on'];
                    }else{
                        continue;
                    }
                    if(!isset($value['type'])){
                        $join.=' LEFT JOIN ';
                    }else{
                        $join.=trim(strtoupper($value['type'])).' JOIN ';
                    }
                    $join.=$key.' ON ';
                    if(isset($value['on']['table']) && !empty($value['on']['table'])){
                        $join_temp_table=$value['on']['table'];
                    }else{
                        $join_temp_table= $join_table;
                    }
                    $join.=$this->createTableAlias($join_temp_table)['alias'];
                    $join.='.'.$join_fields[0].'='.$concatTable.'.'.$join_fields[1];
                    $join_table=$key;
                    $tables.=', '.trim($join_table) ;
                    if($new_where){
                        if($value['where']){
                            $new_where=false;
                        }
                        $group_condition='WHERE';
                    }else{
                        $group_condition=(isset($value['group_condition']) && !empty($value['group_condition'])) ? strtoupper($value['group_condition']) : 'AND';
                    }
                    if(!isset($set['join_structure'])){
                        $set['join_structure']=false;
                    }
                    $fields.=$this->createFields($value, $key, $set['join_structure']);
                    $where.=$this->createWhere($value, $key, $group_condition);
                }
            }
        }
        return compact('fields', 'join', 'where', 'tables');
    }
    protected function createInsert($fields, $files, $except): array
    {
        $insert_arr=[
            'fields'=>'(',
            'value'=>''
        ];
		$array_type=array_keys($fields)[0];
		if(is_int($array_type)){
			$check_fields=false;
			$count_fields=0;
			foreach($fields as $i=>$item){
				$insert_arr['value'].='(';
				if($count_fields===0){
					$count_fields=count($fields[$i]);
				}
				$j=0;
				foreach ($item as $row=>$field){
					if($except && in_array($row,$except)){
						continue;
					}
					if(!$check_fields){
						$insert_arr['fields'] .=$row.',';
					}
					if(in_array($field, $this->sql_func)){
						$insert_arr['value'].=$field.',';
					}elseif($field=='NULL' || $field===NULL){
						$insert_arr['value'].="NULL".',';
					}else{
						$insert_arr['value'].="'". addslashes($field)."',";
					}
					$j++;
					if($j=== $count_fields){
						break;
					}
				}
				if($j<$count_fields){
					for (;$j<$count_fields;$j++){
						$insert_arr['value'].= "NULL".',';
					}
				}
				$insert_arr['value']=\rtrim($insert_arr['value'], ',').'),';
				if(!$check_fields){
					$check_fields=true;
				}
			}
		}else{
			$insert_arr['value'].='(';
			if($fields){
				foreach ($fields as $row=>$field){

					if($except && in_array($row,$except)){
						continue;
					}
					$insert_arr['fields'].=$row.',';
					if(in_array($field, $this->sql_func)){
						$insert_arr['value'].=$field.',';
					}elseif($field=='NULL' || $field===NULL){
						$insert_arr['value'].="NULL".',';
					}else{
						$insert_arr['value'].="'". addslashes($field)."',";
					}
				}
			}
			if($files){
				foreach($files as $row=>$value){
					$insert_arr['fields'].=$row.',';
					if(is_array($value)){
						$insert_arr['value'].="'".addslashes(json_encode($value))."',";
					}else{
						$insert_arr['value'].="'".addslashes($value)."',";
					}
				}
			}
			$insert_arr['value']=rtrim($insert_arr['value'], ',').')';
		}
		$insert_arr['fields']=rtrim($insert_arr['fields'], ',').')';
	    $insert_arr['value']=rtrim($insert_arr['value'], ',');
        return $insert_arr;
    }

    /**
     * @throws \core\base\exceptions\DbException
     */
    final public function showColumns($table){
        if(!isset($this->tableRows[$table]) || !$this->tableRows[$table]){
            $checkTable=$this->createTableAlias($table);
            if(isset($this->tableRows[$checkTable['table']])){
                return $this->tableRows[$checkTable['alias']]=$this->tableRows[$checkTable['table']];
            }
            $query ="SHOW COLUMNS FROM {$checkTable['table']}";
            $res=$this->query($query);
            $this->tableRows[$checkTable['table']] =[];
            if(isset($res) && !empty($res)){
                foreach($res as $row){
                    $this->tableRows[$checkTable['table']][$row['Field']]=$row;
                    if($row['Key']==='PRI'){
                        if(!isset($this->tableRows[$checkTable['table']]['id_row'])){
                            $this->tableRows[$checkTable['table']]['id_row']=$row['Field'];
                        }else {
                            if (!isset($this->tableRows[$checkTable['table']]['multi_id_row'])) {
                                $this->tableRows[$checkTable['table']]['multi_id_row'][] = $this->tableRows[$checkTable['table']]['id_row'];
                            }
                            $this->tableRows[$checkTable['table']]['multi_id_row'][]=$row['Field'];
                        }
                    }
                }
            }
        }
        if(isset($checkTable) && $checkTable['table']!==$checkTable['alias']){
            return $this->tableRows[$checkTable['alias']]=$this->tableRows[$checkTable['table']];
        }
        return $this->tableRows[$table];
    }
    protected function createUpdate($fields, $files, $except): string
    {
        $update='';
        if($fields){
            foreach($fields as $row=>$value) {
                if ($except && in_array($row, $except)) {
                    continue;
                }
                $update .= $row . '=';
                if (in_array($value, $this->sql_func)) {
                    $update .= $value . ',';
                }elseif ($value===NULL || $value==='NULL'){
                    $update .= "NULL" . ',';
                }else{
                    $update.="'".addslashes($value)."',";
                }
            }
        }
        if($files){
            foreach($files as $row=>$file){
                $update.=$row.'=';
                if(is_array($file)){
                    $update.="'".addslashes(json_encode($file))."',";
                }else{
                    $update.="'".addslashes($file)."',";
                }
            }
        }
        return  rtrim($update, ',');
    }
    /**
     * @throws DbException
     */
    final public function showTables(): array
    {
        $query='SHOW TABLES';
        $tables=$this->query($query);
        $table_arr=[];
        if($tables){
            foreach ($tables as $table){
                $table_arr[]=\reset($table);
            }
        }
        return $table_arr;
    }

    /**
     * @param $res
     * @param $table
     * @return array
     */
    protected function joinStructure($res, $table): array
    {
        $join_arr=[];
        $id_row=$this->tableRows[$this->createTableAlias($table)['alias']]['id_row'];
        foreach ($res as $value){
            if(!empty($value)){
                if(!isset($join_arr[$value['id_row']])){
                    foreach ($value as $key=>$item){
                        if(\preg_match('/TABLE_(.+)_TABLE/u', $key, $matches)) {
                            $table_mane_normal = $matches[1];
                            if (!isset($this->tableRows[$table_mane_normal]['multi_id_row'])) {
                                $join_id_row = $value[$matches[0] . '_' . $this->tableRows[$table_mane_normal]['id_row']];
                            } else {
                                $join_id_row='';
                                foreach ($this->tableRows[$table_mane_normal]['multi_id_row'] as $multi) {
                                    $join_id_row .= $value[$matches[0] . '_' . $multi];
                                }
                            }
                            $row=\preg_replace('/TABLE_(.+)_TABLE_/u', '', $key);
                            if($join_id_row && !isset($join_arr[$value[$id_row]]['join'][$table_mane_normal][$join_id_row][$row])){
                                $join_arr[$value[$id_row]]['join'][$table_mane_normal][$join_id_row][$row]=$item;
                            }
                            continue;
                        }
                        $join_arr[$value[$id_row]][$key]=$item;
                    }
                }
            }
        }
        return $join_arr;
    }

    /**
     * @param $table
     * @return array
     */
    protected function createTableAlias($table): array
    {
        $arr=[];
        if(\preg_match('/\s+/iu', $table)){
            $table=\preg_replace('/\s(2,)/i', ' ', $table);
            $table_name=\explode(' ', $table);
            $arr['table']=\trim($table_name[0]);
            $arr['alias']=\trim($table_name[1]);
        }else{
            $arr['table']=$arr['alias']=$table;
        }
        return $arr;
    }
}