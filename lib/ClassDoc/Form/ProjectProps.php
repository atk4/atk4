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
 * Created on 21.03.2006 by *Camper*
 */
class Form_ProjectProps extends Form{
	function init(){
		parent::init();
		$this
			->addField('dropdown', 'type', 'Type')->setValueList(array('library'=>'Library', 'project'=>'Application'))
			->addField('line', 'name', 'Project name')->validateNotNull()
			->addField('text', 'description', 'Description')
			->addField('line', 'local_path', 'Path')->setProperty('size', 60)
			
			->addSubmit('Save')
			
			->setSource('project')
			->addConditionFromGet('id')
		;
		unset($this->dq->args['fields']);
		$this->dq->field('id, name, type, description, local_path');
	}
	function submitted(){
		if(!parent::submitted())return true;
		if(!$this->update())throw new BaseException("Cannot update project table");
		/*if($this->data['id']!=''){
			//updating
			if(!$this->update())throw new BaseException("Cannot update project table");
		}else{
			$this->api->db->query("insert into project (type, name, description, local_path) " .
					"values (".($this->data['type']+1).", '{$this->data['name']}', " .
					"'{$this->data['description']}', '{$this->data['local_path']}')");
		}*/
		$this->api->redirect('Projects');
	}
}
?>
