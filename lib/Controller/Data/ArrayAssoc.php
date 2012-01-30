<?php
class Controller_Data_ArrayAssoc extends Controller_Data_Array {
    function setSource($model,$data){
        $newdata=array();
        foreach($data as $id=>$name){
            $newdata[]=array('id'=>$id,'name'=>$name);
        }
        return parent::setSource($model,$newdata);
    }
    function load($model,$id){
        $model->unload();
        if(!isset($model->table[$id]))return $model;
        $model->set('id',$id);
        $model->set('name',$model->table[$id]);
        return $model;
    }
    function save($model,$id=null){
        if(is_null($id))$id=$model->id;
        $model->table[$id]=$model->get('name');
        return $model;
    }
}
