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
/* This controller will simply output data as it's being passed through */
class Controller_Data_Dumper extends Controller_Data {
    public $log=array();
    public $sh=null;

    function log($model,$s){
        if(!$model)throw $this->exception('model empty');
        $this->log[]=$model->table.":".@$this->sh->short_name." :: ".$s;
    }
    function setPrimarySource($model,$controller,$data=undefined){
        $controller=$this->api->normalizeClassName($controller,'Data');
        $this->sh=$model->setController($controller);
        if($data)$this->sh->setSource($model,$data);
    }
    function __destruct(){
        if(!$this->log)return;
        echo "<pre>";
        foreach($this->log as $l)echo "$l\n";
    }
    function getLog(){
        $l=$this->log;
        $this->log=array();
        return $l;
    }
    function setSource($model,$data=undefined){
        $this->log($model,'setting source');
        if($this->sh)return $this->sh->setSource($model,$data);
        return $this;
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
        $this->log($model,"getBy $field $cond $value");
        if($this->sh)return $this->sh->getDy($model,$field,$cond,$value);
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){
        $this->log($model,"tryLoadBy $field $cond $value");
        if($this->sh)return $this->sh->tryLoadBy($model,$field,$cond,$value);
    }
    function tryLoadAny($model){
        $this->log($model,"tryLoadAny");
        if($this->sh)return $this->sh->tryLoadAny($model);
    }
    function loadBy($model,$field,$cond=undefined,$value=undefined){
        $this->log($model,"loadBy $field $cond $value");
        if($this->sh)return $this->sh->loadBy($model,$filed,$cond,$value);
    }
    function tryLoad($model,$id){
        $this->log($model,"tryLoad $id");
        if($this->sh)return $this->sh->tryLoad($model,$id);
    }
    function load($model,$id=null){
        $this->log($model,"load $id");
        if($this->sh)return $this->sh->load($model,$id);
    }
    function save($model,$id=null){
        $this->log($model,"save $id");
        if($this->sh)return $this->sh->save($model,$id);
    }
    function delete($model,$id=null){
        $this->log($model,"delete $id");
        if($this->sh)return $this->sh->delete($model,$id);
    }
    function deleteAll($model){
        $this->log($model,"deleteAll $id");
        if($this->sh)return $this->sh->deleteAll($model,$id);
    }
    function getRows($model){
        $this->log($model,"getRows");
        if($this->sh)return $this->sh->getRows($model);
    }
    function setOrder($model,$field,$desc=false){
        $this->log($model,"setOrder $field $desc");
        if($this->sh)return $this->sh->getRows($model,$field,$desc);
    }
    function setLimit($model,$count,$offset=0){
        $this->log($model,"setLimit $count $offset");
        if($this->sh)return $this->sh->getRows($model,$count,$offset);
    }

    function rewind($model){
        $this->log($model,"rewind");
        if($this->sh)return $this->sh->rewind($model);
    }
    function next($model){
        $this->log($model,"next");
        if($this->sh)return $this->sh->next($model);
    }
    function __call($method,$arg){
        $this->log($arg[0],"$method");
        if($this->sh)return call_user_func_array(array($this->sh,$method),$arg);
    }
}
