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
class Form_Field_multiSelect extends Form_Field_dropdown {
	public $value_array=array();
	function init(){
		parent::init();
		$this->api->jquery->addPlugin('multiSelect')
			->activate('#'.$this->name,"oneOrMoreSelected: '*'");
	}
	function getInput($attr=array()){
		$attr['name']=$this->name.'[]';
		$i=parent::getInput($attr);
		return $i;
	}
	function validate(){
		$this->value_array=$this->value;
		if(is_array($this->value))$this->value=join(',',$this->value);
	}
	function getOption($value){
		return $this->getTag('option',array(
					'value'=>$value,
					'selected'=>($value == $this->value||in_array($value,$this->value_array))
					));
	}
}
