<?php
/**
 * Simple class to create wizard-like interfaces
 * 
 * Created on 08.09.2006 by *Camper* (camper@adevel.com)
 */
class Wizard extends AbstractView{
	protected $pages=array();
	protected $buttons;
	public $form=null;
	public $finish=null;
	
	function init(){
		parent::init();
		$this->api->stickyGET('step');
	}
	protected function getPage(){
		/**
		 * Returns page depending on the step in the $_GET array
		 */
		$page=$_GET['step']?$this->pages[$_GET['step']]:$this->pages[0];
		return $page;
	}
	function showPage(){
		//$this->buttons=$this->add('Form', 'buttons', 'buttons');
		$page=$this->getPage();
		$this->add($page['class'], null, 'Content');
		$this->template->trySet('Title', $page['title']);
		//displaying buttons
		$current=array_search($page, $this->pages);
		if($current!==false&&$current>0){
			$this->addButton('Previous')->redirect($this->api->page, array('step'=>$current-1));
		}
		if($current!==false&&$current<sizeof($this->pages)-1){
			$next=$this->addButton('Next');
			//if there is a form on the page - redirection should be done by this form
			if($this->form)$next->submitForm($this->form);
			else $next->redirect($this->api->page, array('step'=>$current+1));
		}else{
			$this->finish=$this->addButton('Finish');
		}
	}
	function addPage($title, $classname="Page", $template=array('empty', 'Content')){
		$index=count($this->pages);
		$this->pages[$index]['title']=$title;
		$this->pages[$index]['class']=$classname;
		$this->pages[$index]['template']=$template;
		return $this;
	}
	function addButton($label, $name=null){
		$name=isset($name)?$name:$label;
		$this->buttons[$name] = $this->add('Form_Button',$name,'buttons')
            ->setLabel($label);

        return $this->buttons[$name]->onclick = $this->buttons[$name]->add('Ajax')
        	->useProgressIndicator($this->name.'_loading');
		
	}
	function defaultTemplate(){
		return array('wizard', '_top');
	}
}
