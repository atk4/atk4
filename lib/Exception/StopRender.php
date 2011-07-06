<?php
class Exception_StopRender extends Exception{
	public $result;
	function __construct($r){
		$this->result=$r;
	}
}
