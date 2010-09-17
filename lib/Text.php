<?php
class Text extends AbstractView {
	public $text='Your text goes here......';
	function set($text){
		$this->text=$text;
		return $this;
	}
	function render(){
		$this->output($this->text);
	}
	function setSource(){
		return call_user_func_array(array($this->owner,'setSource'),func_get_args());
	}
}
