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
	function init(){
		parent::init();
		$this->js_widget=null;
		$this->api->addHook('post-init',array($this,'recallAll'));
	}
	function recallAll(){
		foreach(array_keys($this->elements) as $x){
			$o=$this->set($x, $this->recall($x));
		}
	}
	function memorizeAll(){
		//by Camper: memorize() method doesn't memorize anything if value is null
		foreach(array_keys($this->elements) as $x){
			if($this->isClicked('Clear')||is_null($this->get($x)))$this->forget($x);
			else $this->memorize($x,$this->get($x));
		}
	}
	function submitted(){
		if(parent::submitted()){
			if($this->isClicked('Clear')){
				$this->clearData();
			}
			$this->memorizeAll();
			return true;
		}
	}
	function useDQ($dq){
		$this->limiters[]=$dq;
		$this->api->addHook('post-submit',array($this,'applyHook'));
	}
	function applyHook(){
		foreach($this->limiters as $key=>$dq){
			$this->applyDQ($this->limiters[$key]);
		}
	}
	function applyDQ($dq){
		// Redefine this function to apply limits to $dq.
		foreach($this->elements as $key=>$field){
			if($field instanceof Form_Field && $field->get() && !$field->no_save)
				$dq->where($key,$field->get());
		}
	}
}
