<?php
/**
 * Created by PhpStorm.
 * User: xyliv
 * Date: 2016/7/8
 * Time: 16:34
 */
namespace Db;

class ReturnStr{

    private $code=0;
    private $msg ="";

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * @param string $msg
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    /**
     * @return null|string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param null|string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
    private $data="";

    public function __construct($code=0,$msg="success",$data=null)
    {
        $this->code=$code;
        $this->msg=$msg;
        $this->data=$data;
    }

    public function toString(){
        $ret = array("code"=>$this->code,"msg"=>$this->msg,"data"=>$this->data);
        return json_encode($ret);
    }


}