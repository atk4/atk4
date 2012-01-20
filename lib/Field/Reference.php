<?php
class Field_Reference extends Field {
    public $model;
    public $display_field=null;
    public $dereferenced_field=null;

    function setModel($model,$display_field=null){
        $this->model=$model;
        if($display_field)$this->display_field=$display_field;

        $this->owner->addExpression($this->getDereferenced())
            ->calculated(array($this,'calculateSubQuery'));

        return $this;
    }
    function getRef(){
        return $this->model->load($this->get());
    }
    function getDereferenced(){
        if($this->dereferenced_field)return $this->dereferenced_field;
        $f=preg_replace('/_id$/','',$this->short_name);
        if($f!=$this->short_name)return $f;

        $f=$this->_unique($this->owner->elements,$f);
        return $f;
    }
    function calculateSubQuery($select){
        if(is_string($this->model))$this->model=$this->add('Model_'.$this->model);

        $title=$this->model->titleQuery();
        $title->where($select->getField($this->short_name),$title->getField($this->model->id_field));
        return $title;

        /*
        $select->field($title,$this->getDereferenced());
        return $this;
         */
    }
}
