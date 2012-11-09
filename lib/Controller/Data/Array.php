<?php
class Controller_Data_Array extends Controller_Data {
    function setSource($model,$data=undefined){
        parent::setSource($model,$data);

        if(!$model->hasElement($model->id_field))$model->addField($model->id_field)->system(true);

        return $this;
    }
    function getBy($model,$field,$cond=undefined,$value=undefined){
        $t =& $model->_table[$this->name];
        foreach($t as $row){
            if($row[$field]==$value){
                return $row;
            }
        }
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){
        $t =& $model->_table[$this->name];
        foreach($t as $row){
            if($row[$field]==$value){
                $model->data=$row;
                $model->dirty=array();
                $model->id=$row[$model->id_field];
                return $this;
            }
        }
        return $this;
    }
    function tryLoadAny($model){
        $t =& $model->_table[$this->name];
        reset($t);
        list($id,$row)=each($t);

        $model->data=$row;
        $model->dirty=array();
        $model->id=$model->id_field?$row[$model->id_field]:$id;

        return $this;
    }
    function loadBy($model,$field,$cond=undefined,$value=undefined){
        $t =& $model->_table[$this->name];
        $this->loadBy($model,$field,$value);
        if(!$model->loaded())throw $this->exception('Unable to load data')
            ->addMoreInfo('field',$field)->addMoreInfo('value',$value);
        foreach($t as $row){
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
        $t =& $model->_table[$this->name];
        $row=$t[$id];
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
    function save($model,$id=null){
        $t =& $model->_table[$this->name];
        if(is_null($model->id)){
            if(is_null($id)){
                end($t);
                list($id)=each($t);
                $id++;
            }
            $t[$id]=$model->data;
        }else{
            $id=uniqid();
            $t[$id]=$model->data;
        }
        return $id;
    }
    function delete($model,$id=null){
        $t =& $model->_table[$this->name];
        unset($t[$id?:$model->id]);
        return $this;
    }

    function deleteAll($model){
        $model->_table=array();
        $t =& $model->_table[$this->name];
        return $this;
    }
    function getRows($model){
        return $model->_table;
        $t =& $model->_table[$this->name];
    }
    function setOrder($model,$field,$desc=false){
        // TODO: sort array
    }
    function setLimit($model,$count,$offset=0){
        // TODO: splice
    }

    function rewind($model){
        reset($model->_table[$this->name]);

        list($model->id,$model->data)=each($model->_table[$this->name]);
        if(@$model->id_field)$model->id=$model->data[$model->id_field];
        return $model->data;
    }
    function next($model){
        list($model->id,$model->data)=each($model->_table[$this->name]);
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
