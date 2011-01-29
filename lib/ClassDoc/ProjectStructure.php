<?php
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
