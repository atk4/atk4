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
 * Created on 14.04.2006 by *Camper*
 */
class ProjectStructure extends TreeView{
	private $project_id;
	
	function init(){
		parent::init();
		$this
			->display('link', 'name', '', true)
			
			->setSource('member m', 'id', 'parent_id')
		;
		if(isset($_GET['id']))$this->project_id=$_GET['id'];
		$this->memorize('project_id', $this->project_id);
		$this->dq->where('project_id = '.$this->recall('project_id'));
		$this->dq->where("type = 'class'");
		$this->dq->order('id');
		$this->dq->where('version_id = '.$this->api->getProjectVersion($this->recall('project_id')));
		$this->collapseAll();
	}
}
