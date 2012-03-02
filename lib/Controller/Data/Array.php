<?php
class Controller_Data_Array extends AbstractController {

    function rewind($model){
        reset($model->table);
        list($model->id,$model->data)=each($model->table);
        return $model->data;
    }
    function next($model){
        list($model->id,$model->data)=each($model->table);
        $model->set("id", $model->id); // romans, revise please - otherwise, array based source not working properly
        return $model;
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
