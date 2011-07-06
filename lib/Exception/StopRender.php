<?php
class Exception_StopRender extends BaseException{
	public $result;
	function __construct($r){
		$this->result=$r;
	}
}
