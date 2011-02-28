<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
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
