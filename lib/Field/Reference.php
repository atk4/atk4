<?php
class Field_Reference extends Field {
    public $model;
    public $display_field=null;
    public $dereferenced_field=null;

    function setModel($model,$display_field=null){
        $this->model=$model;
        if($display_field)$this->display_field=$display_field;

        $this->owner->addExpression($this->getDereferenced())
            ->set(array($this,'calculateSubQuery'))->caption($this->caption());

        $this->visible(false);

        return $this;
    }
    function ref(){
        $model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$this->model);
        $model=$this->add($model);
        return $model->load($this->get());
    }
    function getDereferenced(){
        if($this->dereferenced_field)return $this->dereferenced_field;
        $f=preg_replace('/_id$/','',$this->short_name);
        if($f!=$this->short_name)return $f;

        $f=$this->_unique($this->owner->elements,$f);
        return $f;
    }
    function destroy(){
        $this->owner->getElement($this->getDereferenced())->destroy();
        return parent::destroy();
    }
    function calculateSubQuery($select){
        if(is_string($this->model)){
            $this->model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$this->model);
            $this->model=$this->add($this->model);
        }

        $title=$this->model->titleQuery();
        $title->where(($this->relation?$this->relation->short_name.'.':'').$this->short_name,
            $title->getField($this->model->id_field));
        return $title;
    }
}
