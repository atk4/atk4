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
    function ref($load=true){
        $model=$this->model;
        if(is_string($model)){
            $model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$model);
            $model=$this->add($model);
        }
        if(!$load)return $model;
        if(!$this->get())throw $this->exception('Reference field has no value')
            ->addMoreInfo('model',$this->owner)
            ->addMoreInfo('field',$this->short_name)
            ->addMoreInfo('id',$this->owner->id)
            ;
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
    function calculateSubQuery($model,$select){
        if(is_string($this->model)){
            $this->model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$this->model);
            $this->model=$this->add($this->model);
        }

        if($this->display()){
            $title=$this->model->dsql()->del('fields');
            $this->model->getElement($this->display())->updateSelectQuery($title);
        }else{
            $title=$this->model->titleQuery();
        }
        $title->where(($this->relation?$this->relation->short_name.'.':'').$this->short_name,
            $title->getField($this->model->id_field));
        return $title;
    }
}
