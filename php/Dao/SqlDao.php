<?php
/**
 * Created by PhpStorm.
 * User: 研
 * Date: 2016/5/26
 * Time: 15:11
 */
namespace Dao;

use Db\Model as Model;

class SqlDao{

    private $sql;
    private $str = "";

    function __construct()
    {
        $this->sql = new Model();
    }

    public function getById($table="",$id=""){
        $this->str = "Select * from `".$table."` WHERE `id`={$id} ";
        $list = $this->sql->query($this->str);
        if(!$list)
            return null;
        return $list[0];
    }

    public function select($table="",$where="",$limit="",$orderby=""){
        $this->str = "Select * from `".$table."` WHERE 1=1 and ".$where." ".($orderby==""?"ORDER BY `id` desc ":$orderby).$limit;
        $list = $this->sql->query($this->str);
        return  $list;
    }

    public function count($table="",$where=""){
        $this->str = "Select count(1) from `".$table."` WHERE 1=1 and ".$where;
        $count = $this->sql->getField($this->str);
        return $count;
    }

    public function update($table="",$where="",$model = array()){
        $set = "";
        foreach($model as $key=>$val){
            $set .="`".$key."`='".$val."' ,";
        }
        $set = substr($set,0,strlen($set)-1);
        $this->str = "UPDATE `".$table."` SET ".$set."WHERE 1=1 AND ".$where;
        $ret = $this->sql->execute($this->str);
        return $ret;
    }

    /**
     * @param string $table
     * @param array $model
     * @return int
     */
    public function insert($table="", $model = array()){
        $keys = "";
        $vals = "";
        foreach($model as $key=>$val){
            if ($key=='id'){
                continue;
            }
            $keys.="`".$key."`,";
            if($val===null){
                $vals.= "null,";
            }else{
                $vals.="'".$val."',";
            }

        }
        $keys = substr($keys,0,strlen($keys)-1);
        $vals = substr($vals,0,strlen($vals)-1);
        $this->str = "INSERT INTO `".$table."`(".$keys .")VALUES(".$vals.")";
        $ret = $this->sql->execute($this->str);
        return $this->sql->getLastInsID();
    }

    public function getQuery($str = ""){
        if($str==""){
            return null;
        }
        $this->str=$str;
        $ret = $this->sql->query($this->str);
        return $ret;
    }

    public function delete($table="",$id){
        $this->str="DELETE FROM `".$table."` WHERE `id`=".$id;
        $ret = $this->sql->execute($this->str);
        return $ret;
    }
}