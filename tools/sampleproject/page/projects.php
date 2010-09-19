<?
/*
   This is your custom page. Defining your own page classes have several benefits.

   One of them is being able to re-define some methods. Like in this case, defaultTemplate()
   is redefined

   */
class page_projects extends Page {
	function init(){
		parent::init();
		$this->add('View_Hint',null,'Hint')->set('This is a sample hint. It is a good practice<br/>'.
				'if you leave a couple of hints for your users');

		$g=$this->add('MVCGrid');
		$c=$g->add('Controller_User');
		$c->setActualFields(array('email','name','surname'));
		$g->setController('Controller_User');

		$g->addButton('Add')
			->js('click')->univ()->dialogURL('Add new project',$this->api->getDestinationURL('./edit'));


		$this->js(true)->atk4_loader(array('url'=>$this->getDestinationURL));
	}
	function defaultTemplate(){
		return array('page/projects','_top');
	}
}
?>
