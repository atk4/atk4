<?php
class View_Box extends View {
	public $class=null;
	function set($text){
		$this->template->set('Content',$text);
		return $this;
	}
	function render(){
		$this->template->trySet('class',$this->class);
		return parent::render();
	}
	function defaultTemplate(){
		return array('view/box','_top');
	}
}
