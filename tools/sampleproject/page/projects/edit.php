<?
class page_projects_edit extends Page {
	function init(){
		parent::init();
		$c=$this->add('Controller_User');
		$f=$this->add('MVCForm')
			->setController($c);


		if($f->hasElement('Save')){
			unset($f->elements['Save']);
		}

		// This is form's submit handler. Since your form is Ajax - you should
		// return use js()....->execute(); to return javascript instructions
		// to be performed
		if($f->isSubmitted()){
			$f->update();
			$f->js()
				->univ()
				->successMessage('Added new project')
				->closeDialog()
				->page('projects')
				->execute();
		}
	}
}
