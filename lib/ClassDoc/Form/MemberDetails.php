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
 * Created on 21.04.2006 by *Camper*
 */
class Form_MemberDetails extends Form{
	function init(){
		parent::init();
		$this->api->stickyGET('member_id');
		$this->api->stickyGET('id');
		$id=$_GET['member_id']==''?$_GET['id']:$_GET['member_id'];
		$member=$this->api->getItem($id);
		$this
			->addSeparator()

			->addComment("<div align=left><p>Filename: ".$member['file']."</p><p>Line: ".$member['line'].
				"</p><p><font color=red>Remember!</font> Only a description can be changed</p></div>")

			->addSeparator()
			->addField('line', 'name', 'Name')->setNoSave()
			->addField('dropdown', 'visibility', 'Visibility')->setValueList($this->getVList())->setNoSave()
			->addField('line', 'declaration', 'Declaration')->setNoSave()->setProperty('size', 80)
		;
		//adding a fields, assuming member type
		if($member['type']=='class'){
			$this
				->addField('line', 'extends', 'Extends')->setNoSave()
			;
		}
		$this
			->addField('text', 'description', 'Description')
				->setProperty('cols', 60)
				->setProperty('rows', 10)
			//->addComment("<div id=result>result=?</div>")
			
			->setSource('member')
			->addCondition('id', $id)
	
			->addSubmit('Save')//->submitForm($this)
		;
	}
	function getVList(){
		return array('public'=>'public','private'=>'private','protected'=>'protected');
	}
	function submitted(){
		if(!parent::submitted())return false;
		$this->update();
		$this->api->redirect('Projects_name_name');
	}
}
