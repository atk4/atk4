<?php
/* 
Reference implementation for data controller

Data controllers are used by "Model" class to access their data source.
Additionally Model_Table can use data controllers for caching their data

*/

abstract class Controller_Data extends AbstractController {

    function setSource($model,$data=undefined){
        if($data===undefined)return $this;
        $model->table=$data;
        return $this;
    }

    /* Normally model will call our methods. If the controller is used as a secondary cache, then we need to place hooks
     * instead */
    function addHooks($priority){

        $m=$this->owner;
        if($m->controller===$this)throw $this->exception('Cannot be data source and cache simultaniously');

        $m->addHook('beforeLoad',array($this,'tryLoad'),array(),$priority);
        $m->addHook('afterSave',array($this,'save'),array(),-$priority);
        $m->addHook('afterDelete',array($this,'delete'),array(),-$priority);
        $m->addHook('unCache',$this);

        return $this;
    }

    function isCache($model){
        return $model->controller != $this;
    }

    /* Remove record from the cache */
    function unCache($model){
        $this->delete($model);
    }

	abstract function load($model,$id=null);
	abstract function save($model);
	abstract function delete($model,$id=null);

	abstract function tryLoad($model,$id);
    abstract function loadBy($model,$field,$cond,$value);
    abstract function tryLoadBy($model,$field,$cond,$value);

    abstract function deleteAll($model);
    abstract function getRows($model);
    abstract function getBy($model,$field,$cond,$value);

    /* must implement in underlying layer */
    abstract function setOrder($model,$field,$desc=false);

    /* must implement in underlying layer */
    abstract function setLimit($model,$count,$offset=0);

	abstract function rewind($model);
	abstract function next($model);
}

