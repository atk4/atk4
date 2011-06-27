<?php
/* Implementation of plain HTML-only form. Does not support submission or anything */
class Form_Plain extends HtmlElement {
	function init(){
		parent::init();
		$this->setElement('form');
	}
	function addInput($type,$name,$value,$tag='Content'){
		$f=$this->add('HtmlElement',$name,$tag);
		$f->setElement('input');
		$f->setAttr('type',$type);
		$f->setAttr('value',$value);
		$f->set('');
		return $f;
	}
}
