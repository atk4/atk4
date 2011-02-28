<?php
class page_projects_edit extends Page {
	function init(){
		parent::init();
		$f=$this->add('Form');
		$f->addField('line','title');
		$f->addField('text','descr','Description');

		$f->setSource('project');
		$f->setConditionFromGET('id');

		// be sure to add restrcitions, such as make sure record belongs to current user
		// $f->dq->where('user_id',$this->api->auth->get('id'));

		if($_GET['id']){
			$f->addSubmit();
		}

		// This is form's submit handler. Since your form is Ajax - you should
		// return use js()....->execute(); to return javascript instructions
		// to be performed
		if($f->isSubmitted()){
			$f->update();
			$f->js()
				->univ()
				->successMessage($_GET['id']?'Saved changes':'Added new project')
				->closeDialog()
				->page('projects')
				->execute();
		}
	}
}
