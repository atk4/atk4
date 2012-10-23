<?php
class Controller_Data_Array extends Controller_Data {
    function setSource($model,$data=undefined){
        parent::setSource($model,$data);

        if(!$model->hasElement($model->id_field))$model->addField($model->id_field)->system(true);

        return $this;
    }


    function getBy($model,$field,$cond=undefined,$value=undefined){
        foreach($model->table as $row){
            if($row[$field]==$value){
                return $row;
            }
        }
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){
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
    function loadBy($model,$field,$cond=undefined,$value=undefined){
        $this->loadBy($model,$field,$value);
        if(!$model->loaded())throw $this->exception('Unable to load data')
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
    function load($model,$id=null){
        $this->tryLoad($model,$id);
        if(!$model->loaded())throw $this->exception('Unable to load data')
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
    function delete($model,$id=null){
        unset($model->table[$id?:$model->id]);
        return $this;
    }

    function deleteAll($model){
        $model->table=array();
        return $this;
    }
    function getRows($model){
        return $model->table;
    }
    function setOrder($model,$field,$desc=false){
        // TODO: sort array
    }
    function setLimit($model,$count,$offset=0){
        // TODO: splice
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
