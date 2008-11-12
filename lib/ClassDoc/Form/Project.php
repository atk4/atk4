<?php
/*
 * Created on 13.04.2006 by *Camper*
 */
class Form_Project extends Form{
	function init(){
		parent::init();
		$this
			->addField('hidden', 'id')
			->addField('checkbox', 'desc_class_after', 'Class comments are after declaration')->setNoSave()
			->addField('checkbox', 'desc_method_after', 'Method comments are after declaration')->setNoSave()
			->addField('checkbox', 'parse_non_class', 'Parse declarations outside classes')->setNoSave()
			
			->addSubmit('Parse')
			
			->setSource('project')
			->addConditionFromGet('id')
		;
	}
	function submitted(){
		if(!parent::submitted())return false;
		if($this->isClicked("Parse"))$this->api->redirect('GetStructure', 
			array('desc_class_after'=>$this->get('desc_class_after'), 
			'desc_method_after'=>$this->get('desc_method_after'),
			'parse_non_class'=>$this->get('parse_non_class')));
	}
}