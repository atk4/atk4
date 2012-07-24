<?php
class SQL_Many extends AbstractModel {
    public $model_name=null;
    public $their_field=null;
    public $orig_conditions=null;
    public $our_field=null;
    public $auto_track_element=true;
    public $relation=null;
    function set($model,$their_field=null,$our_field=null,$relation=null){

        $this->model_name=is_string($model)?$model:get_class($model);
        $this->model_name=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$this->model_name);

        if($relation){
            $this->relation=$relation;
            $this->our_field=$our_field;
            $this->their_field=$their_field;
            return $this;
        }

        $this->their_field=$their_field?:$this->owner->table.'_id';

        $this->our_field=$our_field?:$this->owner->id_field;

        return $this;
    }
    function from($m){
        if($m===undefined)return $this->relation;
        $this->relation=$m;
        return $this;
    }
    function saveConditions(){
        $this->orig_conditions=$this->model->_dsql()->args['where'];
        return $this;
    }
    function restoreConditions(){
        if(!$this->model){
            $this->model=$this->add($this->model_name);
            $this->saveConditions();
        }
        $this->model->_dsql()->args['where']=$this->orig_conditions;
        return $this;
    }
    function refSQL(){
        $this->restoreConditions();
        return $this->model->addCondition($this->their_field,$this->owner->getElement($this->our_field));
    }
    function ref($mode=null){
        if(!$this->owner->loaded()){
            throw $this->exception('Model must be loaded before traversing reference');
        }

        if($mode=='model'){
            $m=$this->add($this->model_name);
            return $m->setMasterField($this->their_field,$this->owner->get($this->our_field));
        }

        $this->restoreConditions();

        $this->model->unload();
        return $this->model->setMasterField($this->their_field,$this->owner->get($this->our_field));
    }
}
