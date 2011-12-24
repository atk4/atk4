<?php
class Controller_Data_Array extends AbstractController {

    function rewind(&$data, &$id){
        $id=null;
        return reset($data);
    }
    function next(&$data, &$id){
        return $id=next($data);
    }

    function init(){
        parent::init();
        $this->owner->addField('id')->type('int')->system(true);
        $this->owner->addField('name');
    }
    function setSource($model,$data){
        $model->table=$data;
        return $this;
    }
    function setAssoc($data){
        $this->array_data=array();
        foreach($data as $id=>$name){
            $this->array_data[]=array('id'=>$id,'name'=>$name);
        }
        return $this;
    }
    function getActualFields(){
        return array();
    }
}
