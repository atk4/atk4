<?php
class HelloWorld extends AbstractView {
	private $message;
	function init(){
		$this->message = 'Hello world';
	}
	function setMessage($msg){
		$this->message=$msg;
	}
	function render(){
		$this->output('<p>'.$this->message.'</p>');
	}
}
