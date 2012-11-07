<?php
/* 
Reference implementation for data controller

Data controllers are used by "Model" class to access their data source.
Additionally Model_Table can use data controllers for caching their data

*/

abstract class Controller_Data extends AbstractController {

    function setSource($model,$data=undefined){
        if($data===undefined)return $this;

        $this->bindTable($model,$t);
        $t=$data;

        return $this;
    }


    /* Binds variable to resource which can be used for data storage. Normally 
        * $model->table stores it, but if you are using caches, then 
        * your model might have different table for a cache. Call this 
        * method to get a proper reference */
    function bindTable($model,&$t){
        if($this->isCache($model)){
            $t = &$model->cache_table[$this->name];
        }else{
            $t = &$model->table;
        }
    }

    /* Normally model will call our methods. If the controller is used as a secondary cache, then we need to place hooks
     * instead */
    function addHooks($priority){

        $m=$this->owner;
        if($m->controller===$this)throw $this->exception('Cannot be data source and cache simultaniously');

        $m->addHook('beforeLoad',array($this,'cache_load'),array(),$priority);
        $m->addHook('afterSave',array($this,'save'),array(),-$priority);
        $m->addHook('afterDelete',array($this,'delete'),array(),-$priority);

        return $this;
    }

    /* Cache Implementation */
    function isCache($model){
        return $model->controller != $this;
    }
    function cache_load($model,$id=null){
        if($model->loaded())return; // other cache loaded us
        $this->tryLoad($model,$id);
    }

	abstract function load($model,$id=null);
	abstract function save($model);
	abstract function delete($model,$id=null);

	abstract function tryLoad($model,$id);
    abstract function loadBy($model,$field,$cond,$value);
    abstract function tryLoadBy($model,$field,$cond,$value);
    abstract function tryLoadAny($model);

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

