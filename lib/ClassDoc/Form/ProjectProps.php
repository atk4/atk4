<?php
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
