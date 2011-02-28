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
		return $this->api->db->getOne("select name from member where id = '".(int)$_GET['id']."'");
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
		$this->dq->where('parent_id',$_GET['id']);
		$this->dq->where("type <> 'class'");
		$this->dq->order('id');
		$this->expandAll();
	}
	function format_ajax($field){
		$this->current_row['caption'].=$this->api->getMemberLink($this->current_row[$field['name']],
			$this->current_row['id']);
	}
}
