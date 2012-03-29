<?php
class SQL_Many extends AbstractModel {
    public $dst_model=null;
    public $their_field=null;
    public $our_field=null;
    public $auto_track_element=true;
    function set($model,$their_field=null,$our_field=null){

        $this->their_field=$their_field?:$this->owner->table.'_id';

        $this->our_field=$our_field?:$this->owner->id_field;

        $this->dst_model=$model;

        return $this;
    }
    function refSQL(){
        $model=$this->dst_model;
        if(is_string($model)){
            $model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$model);
            $model=$this->add($model);
        }
        return $model->addCondition($this->their_field,$this->owner->_dsql()->getField($this->our_field));
    }
    function ref(){
        $model=$this->dst_model;
        if(is_string($model)){
            $model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$model);
            $model=$this->add($model);
        }

        if(!$this->owner->loaded()){
            throw $this->exception('Model must be loaded before traversing reference');
        }
        return $model->setMasterField($this->their_field,$this->owner->get($this->our_field));
    }
}
