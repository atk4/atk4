<?php
class Controller_Data_ArrayAssoc extends Controller_Data {

    function load($model,$id=null){
        $model->data=$model->table[$id];
    }

    function save($model,$id=null){
        $model->table[$id]=$model->data;
    }

    function delete($model,$id=null){
        unset($model->table[$id]);
    }

    function tryLoad($model,$id){
        if(!isset($table[$id]))return;
        $this->load($model,$id);
    }

    function loadBy($model,$field,$cond,$value){
        $model->data=$this->getBy($model,$field,$cond,$value);
    }

    function tryLoadBy($model,$field,$cond,$value){
        $data=$this->getBy($model,$field,$cond,$value);
        if($data)$model->data=$data;
    }

    function deleteAll($model){
        $model->table=array();
    }
    function getRows($model){
        return $model->table;
    }
    function getBy($model,$field,$cond,$value){
        if($cond!='=')return $this->exception('Unsupported condition','NotImplemented');

        // load by id field
        if($field===$model->id_field)
            return $this->load($model,$value);

        // load by other field
        foreach($model->table as $key=>$data){
            if($data[$field]==$value){
                return $data;
            }
        }
        return null;
    }

    function setOrder($model,$field,$desc=false){
        return $this->exception('','NotSupported');
    }

    function setLimit($model,$count,$offset=0){
        return $this->exception('','NotSupported');
    }
}
