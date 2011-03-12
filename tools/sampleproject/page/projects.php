<?php
/*
   This is your custom page. Defining your own page classes have several benefits.

   One of them is being able to re-define some methods. Like in this case, defaultTemplate()
   is redefined

   */
class page_projects extends Page {
	function init(){
		parent::init();

		if(!$this->api->db){
			$this->add('View_Hint',null,'Hint')
				->set('For this page you will need to configure MySQL database access');
			return;
		}

		$this->add('View_Hint',null,'Hint')->set('This is a sample hint. It is a good practice<br/>'.
				'if you leave a couple of hints for your users');

		$g=$this->add('Grid');
		$g->setSource('project');
		$g->addColumn('text','title');
		$g->addColumn('shorttext','descr');
		$g->addColumn('expander_widget','edit');	
		$g->addColumn('confirm','delete');

		// be sure to add conditions here
		// $g->dq->where('status','active');


		if($_GET['delete']){
			$g->dq->where('id',$_GET['delete'])->do_delete();
			// This shows how can you use 2 js chains together.
			$g->js(null,$g->js()->univ()->successMessage('Record deleted'))->reload()->execute();
		}


		$g->addButton('Add')
			->js('click')->univ()->dialogURL('Add New Project',$this->api->getDestinationURL('./edit'));
	}
	function defaultTemplate(){
		return array('page/projects','_top');
	}
}
?>
