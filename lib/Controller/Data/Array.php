<?php
class Controller_Data_Array extends AbstractController {

    function init(){
        parent::init();
        $this->owner->addField('id')->type('int')->system(true);
        $this->owner->addMethod('getBy,loadBy,tryLoadBy,tryLoad',$this);
    }
    function getBy($model,$field,$value){
        foreach($model->table as $row){
            if($row[$field]==$value){
                return $row;
            }
        }
    }
    function tryLoadBy($model,$field,$value){
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
    function loadBy($model,$field,$value){
        $this->loadBy($model,$field,$value);
        if(!$model->loaded())throw $this->exceptoin('Unable to load data')
            ->addMoreInfo('field',$field)->addMoreInfo('value',$value);
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
    function tryLoad($model,$id){
        if(@$model->id_field){
            return $this->tryLoadBy($model,$model->id_field,$id);
        }
        $row=$model->table[$id];
        $model->data=$row;
        $model->dirty=array();
        $model->id=$id;
        return $this;
    }
    function load($model,$id){
        $this->tryLoad($model,$id);
        if(!$model->loaded())throw $this->exceptoin('Unable to load data')
            ->addMoreInfo('id',$id);
        return $this;
    }
    function save($model){
        if(is_null($model->id)){
            end($model->table);
            list($model->id)=each($model->table);
            $model->id++;
            $model->table[$model->id]=$model->data;
        }else{
            $model->table[$model->id]=$model->data;
        }

        return $this;
    }
    function delete($model,$id){
        unset($model->table[$id]);
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
