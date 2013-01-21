<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class Filter extends Form {
    public $limiters=array();
    public $memorize=true;
    public $view;
    function init(){
        parent::init();

        // set default values on non-yet initialized fields
        $this->api->addHook('post-init',array($this,'postInit'));
    }
    function useWith($view){
        // Apply our condition on the view
        $this->view=$view;
        return $this;
    }
    /** Remembers values and uses them as condition */
    function postInit(){
        foreach($this->elements as $x=>$field){
            if($field instanceof Form_Field){

                $field->set($val=$this->recall($x));

                if($field->no_save)continue;
                if(!$field->get())continue;

                // also apply the condition
                if($this->view->model && $this->view->model->hasElement($x) ){
                    if($this->view->model->addCondition($x,$field->get())); // take advantage of field normalization
                }
            }
        }
        $this->hook('applyFilter',array($this->view->model));
    }
    function memorizeAll(){
        //by Camper: memorize() method doesn't memorize anything if value is null
        foreach($this->elements as $x=>$field){
            if($field instanceof Form_Field){
                if($this->isClicked('Clear')||is_null($this->get($x)))$this->forget($x);
                else $this->memorize($x,$this->get($x));
            }
        }
    }
    function addButtons(){
        $this->save=$this->addSubmit('Save');
        $this->reset=$this->addSubmit('Reset');
    }
    function submitted(){
        if(parent::submitted()){
            if(isset($this->reset) && $this->isClicked($this->reset)){
                $this->forget();
                $this->js(null,$this->view->js()->reload())->reload()->execute();
            }else{
                $this->memorizeAll();
            }
            $this->view->js()->reload()->execute();
        }
    }
}
