<?php
class Controller_Data_Array extends AbstractController {

    function init(){
        parent::init();
        $this->owner->addField('id')->type('int')->system(true);
        $this->owner->addField('name');
        $this->owner->addMethod('getBy,loadBy',$this);
    }
    function getBy($model,$field,$value){
        foreach($model->table as $row){
            if($row[$field]==$value){
                return $row;
            }
        }
    }
    function loadBy($model,$field,$value){
        foreach($model->table as $row){
            if($row[$field]==$value){
                $model->data=$row;
                $model->dirty=array();
                $model->id=$row[$model->id_field];
                return $this;
            }
        }
        return $this;
    }
    function load($model,$id){
        if($model->id_field){
            return $this->loadBy($model,$model->id_field,$id);
        }
        $row=$model->table[$id];
        $model->data=$row;
        $model->dirty=array();
        $model->id=$id;
        return $this;
    }
    function rewind($model){
        reset($model->table);
        list($model->id,$model->data)=each($model->table);
        if(@$model->id_field)$model->id=$model->data[$model->id_field];
        return $model->data;
    }
    function next($model){
        list($model->id,$model->data)=each($model->table);
        if(@$model->id_field)$model->id=$model->data[$model->id_field];
        $model->set("id", $model->id); // romans, revise please - otherwise, array based source not working properly
        return $model;
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
