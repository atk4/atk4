<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Field_Reference extends Field {
    public $model_name=null;
    public $display_field=null;
    public $dereferenced_field=null;
    public $table_alias=null;

    function setModel($model,$display_field=null){
        $this->model_name=is_string($model)?$model:get_class($model);
        $this->model_name=$this->api->normalizeClassName($this->model_name,'Model');
        
        if($display_field)$this->display_field=$display_field;

        if($display_field!==false){
            $this->owner->addExpression($this->getDereferenced())
                ->set(array($this,'calculateSubQuery'))->caption($this->caption());
        }

        $this->system(true);
        $this->editable(true);
        $this->visible(false);

        return $this;
    }
    function getModel(){
        if(!$this->model)$this->model=$this->add($this->model_name);
        if($this->display_field)$this->model->title_field=$this->display_field;
        if($this->table_alias)$this->model->table_alias=$this->table_alias;
        return $this->model;
    }
    function sortable($x=undefined){
        $f=$this->owner->hasElement($this->getDereferenced());
        if($f)$f->sortable($x);
        return parent::sortable($x);
    }
    function caption($x=undefined){
        $f=$this->owner->hasElement($this->getDereferenced());
        if($f)$f->caption($x);
        return parent::caption($x);
    }
    /**
     * ref() will traverse reference and will attempt to load related model's entry. If the entry will fail to load
     * it will return model which would not be loaded. This can be changed by specifying an argument:
     *
     * 'model' - simply create new model and return it without loading anything
     * false or 'ignore' - will not even try to load anything
     * null (default) - will tryLoad()
     * 'load' - will always load the model and if record is not present, will fail
     * 'create' - if record fails to load, will create new record, save, get ID and insert into $this
     * 'link' - if record fails to load, will return new record, with appropriate afterSave hander, which will
     *          update current model also and save it too.
     *
     */
    function ref($mode=null){
        if($mode=='model'){
            return $this->add($this->model_name);
        }

        $this->getModel()->unload();


        if($mode===false || $mode=='ignore'){
            return $this->model;
        }
        if($mode=='load'){
            return $this->model->load($this->get());
        }
        if($mode===null){
            if($this->get())$this->model->tryLoad($this->get());
            return $this->model;
        }
        if($mode=='create'){
            if($this->get())$this->model->tryLoad($this->get());
            if(!$this->model->loaded()){
                $this->model->save();
                $this->set($this->model->id);
                $this->owner->update();
                return $this->model;
            }
        }
        if($mode=='link'){
            $m=$this->add($this->model_name);
            if($this->get())$m->tryLoad($this->get());
            $t=$this;
            if(!$m->loaded()){
                $m->addHook('afterSave',function($m)use($t){
                        $t->set($m->id);
                        $t->owner->save();
                        });
            }
            return $m;
        }
    }
    function refSQL(){
        $q=$this->ref('model');
        $q->addCondition($q->id_field,$this);
        return $q;
    }
    function getDereferenced(){
        if($this->dereferenced_field)return $this->dereferenced_field;
        $f=preg_replace('/_id$/','',$this->short_name);
        if($f!=$this->short_name)return $f;

        $f=$this->_unique($this->owner->elements,$f);
        $this->dereferenced_field=$f;
        return $f;
    }
    function destroy(){
        if($e=$this->owner->hasElement($this->getDereferenced())){
            $e->destroy();
        }
        return parent::destroy();
    }
    function calculateSubQuery($model,$select){
        if(!$this->model)$this->getModel(); //$this->model=$this->add($this->model_name);

        if($this->display_field){
            $title=$this->model->dsql()->del('fields');
            $this->model->getElement($this->display_field)->updateSelectQuery($title);
        }else{
            $title=$this->model->titleQuery();
        }
        $title->where($this,$title->getField($this->model->id_field));
        return $title;
    }
}
