<?php
/*
 * Created on 20.04.2006 by *Camper*
 */
class page_Projects_name_name extends Page{
	function init(){
		parent::init();
		$this->template->trySet('ClassName', $this->api->getMemberLink($this->getClassName(), $_GET['id']));
		$this->add('ClassStructure', null, 'ClassStructure');
		$this->frame('MemberDetails', $this->api->getMemberName($_GET['id']))
			->add('Form_MemberDetails', null, 'content');
	}
	function getClassName(){
		return $this->api->db->getOne("select name from member where id = ".$_GET['id']);
	}
	function defaultTemplate(){
		return array('classdetails', '_top');
	}
}
class ClassStructure extends TreeView{
	function init(){
		parent::init();
		$this
			->display('text', 'type')
			->display('ajax', 'name', ' ')
			
			->setSource('member m', 'id', 'parent_id', $_GET['id'])
			->hideButtons()
		;
		$this->dq->where('parent_id = '.$_GET['id']);
		$this->dq->where("type <> 'class'");
		$this->dq->order('id');
		$this->expandAll();
	}
	function format_ajax($field){
		$this->current_row['caption'].=$this->api->getMemberLink($this->current_row[$field['name']],
			$this->current_row['id']);
	}
}
