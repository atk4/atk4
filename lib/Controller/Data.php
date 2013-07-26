<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/* 
Reference implementation for data controller

Data controllers are used by "Model" class to access their data source.
Additionally SQL_Model can use data controllers for caching their data

*/

abstract class Controller_Data extends AbstractController {

    public $auto_track_element=true;

    function setSource($model,$data=undefined){
        if($data===undefined)return $this;

        if(!$model->_table[$this->short_name]){
            $model->_table[$this->short_name]=array();
        }
        $model->_table[$this->short_name] = $data;
        return $this;
    }

    /* Normally model will call our methods. If the controller is used as a secondary cache, then we need to place hooks
     * instead */
    function addHooks($m,$priority){
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
    function cache_load($model,$id=null,$id2=null){
        if(is_object($id))$id=$id2;
        if($model->loaded())return; // other cache loaded us
        $this->tryLoad($model,$id);
    }

	abstract function load($model,$id);
	abstract function save($model);
	abstract function delete($model,$id);

	abstract function tryLoad($model,$id);
    abstract function loadBy($model,$field,$cond,$value);
    abstract function tryLoadBy($model,$field,$cond,$value);
    abstract function tryLoadAny($model);

    abstract function deleteAll($model);
    abstract function getRows($model);
    abstract function getBy($model,$field,$cond,$value);

    /** Create a new cursor and load model with the first entry */
    abstract function rewind($model);

    /** Provided that rewind was called before, load next data entry */
    abstract function next($model);

    /** Optional function your driver migt support */
    function addCondition($model,$field,$operator,$value){
        throw $this->exception('addCondition is not supported by this data driver');
    }

    /** must implement in underlying layer */
    function setOrder($model,$field,$desc=false){
        throw $this->exception('setOrder is not supported by this data driver');
    }

    /** must implement in underlying layer */
    function setLimit($model,$count,$offset=0){
        throw $this->exception('setLimit is not supported by this data driver');
    }

    /** returns count of records matching model criteria */
    function count($model, $alias = null){
        throw $this->exception('count is not supported by this data driver');
    }

}

