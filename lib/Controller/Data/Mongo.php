<?php
class Controller_Data_Mongo extends Controller_Data {
    function setSource($model,$table){

        if(@!$this->api->mongoclient){
            $m=new MongoClient();


            $this->api->mongoclient=$m->ven;

        }

        parent::setSource($model,array(
            'db'=>$this->api->mongoclient->$table,
            'collection'=>$model->table
        ));

        //$model->data=$model->_table[$this->short_name]['db']->get($id);
    }

    /** Implemetns access to our private storage inside model */
    protected function _get($model,$key){
        return $model->_table[$this->short_name][$key];
    }
    protected function _set($model,$key,$val){
        $model->_table[$this->short_name][$key]=$val;
    }

    function save($model,$id=null){
        $db=$this->_get($model,'db')->save($model->data);
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function tryLoad($model,$id){
    }
    function load($model,$id=null){
        $this->tryLoadBy($model,$model->id_field,new MongoID($id));
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){

        if ($value===undefined) {
            $value=$cond;
            $cond='=';
        }

        $model->data=$this->_get($model,'db')->findOne(array($field=>$value));
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function tryLoadAny($model){
        $model->data=$this->_get($model,'db')->findOne();
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function loadBy($model,$field,$cond=undefined,$value=undefined){}
    function delete($model,$id=null){}
    function deleteAll($model){}
    function getRows($model){}
    function setOrder($model,$field,$desc=false){}
    function setLimit($model,$count,$offset=0){}
    function rewind($model){
        $c=$this->_get($model,'db')->find();
        $this->_set($model,'cur',$c);
        $model->data=$c->getNext();
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->data;
    }
    function next($model){
        $c=$this->_get($model,'cur');
        $model->data=$c->getNext();
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->data;
    }

    function addCondition($model,$field,$cond=undefined,$value=undefined){
    }
}
