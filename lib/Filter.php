<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
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
                if($this->view->model){
                    if($this->view->model->addCondition($x,$field->get())); // take advantage of field normalization
                }elseif($this->view->dq){
                    if($this->view->dq->where($x,$field->get()));
                }
            }
		}
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
	function submitted(){
		if(parent::submitted()){
			if($this->isClicked('Clear')){
				$this->clearData();
			}
			$this->memorizeAll();
            $this->view->js()->reload()->execute();
		}
	}
}
