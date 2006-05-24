<?php

class FormButtons extends Form{
	
	public $dq = null;
	public $next_button;

    function init() {
		parent::init();
		if($_GET['direction']){//isClicked('Next')||$this->isClicked('Previous')){
    		$this->owner->proceed($_GET['direction']=='next');
		}else{
			$this->owner->showPage();
		}
		if($this->owner->getCurrentIndex() != 0)$this->addButton('Previous')
			->redirect(null,array('direction'=>'prev'));
		if($this->owner->getCurrentIndex() != $this->owner->getPageCount() - 1){
			$ajax=$this->next_button = $this->addButton('Next');
			if(property_exists($this->owner->page, 'form')){
				$ajax->ajaxFunc("if(submitFormCmd('".$this->owner->page->form->name."', null)===true)" .
					"alert('No errors');".
					//"{document.location='".$this->api->getDestinationURL(null,array('direction'=>'next')).
					//"';}" .
					"");
			}
			else $ajax->redirect(null,array('direction'=>'next'));
		}
    }
}

class Wizard extends Page{

	/**
	 * This array contains wizard's pages in form of (index => 'page_template_name')
	 */
	private $pages = array();
	private $current_index = -1;
	public $page;
	private $buttons;

    function init() {
		parent::init();
		$this->current_index = $this->recall('current_page', 0);
		$this->api->addHook('pre-exec', array($this, 'display'));
    }
    function display(){
   		if($_GET['direction']){
    		$this->proceed($_GET['direction']=='next');
		}else{
			$this->showPage();
		}
    }
    function checkFirstRun(){
   		if($this->current_index == -1){
			$this->current_index = 0;
			$this->showPage();
		}
    }
    function defaultTemplate(){
        return array('wizard','wizard');
    }
	function getCurrentIndex(){
		return $this->current_index;
	}
	function getPageCount(){
		return sizeof($this->pages);
	}
	function getLastPage(){
		return $this->getPageCount() - 1;
	}
	function getPage($index){
		return $this->pages[$index];
	}
    function getNextPage(){
	    /**
    	 * Returns next page. Override this method in your descendants if needed
     	*/
		return $this->getCurrentIndex() == $this->getLastPage() ? 
			$this->getLastPage() : ($this->getCurrentIndex() + 1);
    }
	function getPrevPage(){
	    /**
    	 * Returns previous page. Override this method in your descendants if needed
     	*/
		return $this->getCurrentIndex() == 0 ? 0 : ($this->getCurrentIndex() - 1);
	}
	function addPage($title = "", $content = "Form", $template=array('form', 'form')){
		/**
		 * Adds page
	 	 */
		if($title == "")$title = "Step ".($this->getPageCount() + 1);
		$this->pages[] = array('title' => $title, 'template' => $template, 
			'class' => $content);
		return $this;
	}
	function showButtons(){
		//$this->buttons=$this->add('FormButtons', null, 'buttons');
		if($this->getCurrentIndex() != 0)$this->page->addButton('Previous')
			->redirect(null,array('direction'=>'prev'));
		if($this->getCurrentIndex() != $this->getPageCount() - 1)$this->page->addSubmit('Next');
	}
	function showPage(){
		/**
		 * Shows <b>current</b> page
		 */
		$class = $this->pages[$this->getCurrentIndex()]['class'];
		$this->page = $this->add($class, null, 'Content', 
			$this->pages[$this->getCurrentIndex()]['template']);
		//checking if form was successfully submitted and redirecting on success
		if($this->page->isSubmitted())$this->api->redirect(null,array('direction'=>'next'));
		$this->template->trySet('Title', $this->pages[$this->getCurrentIndex()]['title']);
		$this->memorize('current_page', $this->current_index);
		$this->showButtons();
	}
	function proceed($forward){
		/**
		 * Changes the current page index.
		 * @param bool forward
		 */
		if($forward)$this->current_index = $this->getNextPage();
		else $this->current_index = $this->getPrevPage();
		$this->showPage();
	}
}
?>
