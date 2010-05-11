<?php
class Filter extends Form {
	private $limiters=array();
	function init(){
		parent::init();
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
