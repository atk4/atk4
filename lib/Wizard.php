<?php

class SomePage extends AbstractView{
/**
 * TODO I wonder if this class is needed...
 */
	
} 
class FormButtons extends Form{
	
	public $dq = null;

    function init() {
		parent::init();
		if($this->isClicked('Next')||$this->isClicked('Previous')){
    		$this->owner->proceed($this->isClicked('Next'));
		}else{
			$this->owner->showPage();
		}
		if($this->owner->getCurrentIndex() != 0)$this->addSubmit('Previous');
		if($this->owner->getCurrentIndex() != $this->owner->getPageCount() - 1){
			$next = $this->addSubmit('Next');
			$page = $this->owner->getPage($this->owner->getCurrentIndex());
			if($page['submit']!='')
				$next->setProperty('form_onsubmit', "document.".$page['submit'].".submit();");
		}
    }
    
}

class Wizard extends Page{

	/**
	 * This array contains wizard's pages in form of (index => 'page_template_name')
	 */
	private $pages = array();
	private $current_index = -1;
	private $page;

    function init() {
		parent::init();
		$this->current_index = $this->recall('current_page', 0);
		$this->api->addHook('pre-exec', array($this, 'showButtons'));
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
	function addPage($template, $title = "", $content = "", $submit = ""){
		/**
		 * Adds page
	 	*/
		if($title == "")$title = "Step ".($this->getPageCount() + 1);
		$this->pages[] = array('title' => $title, 'template' => $template, 
			'class' => $content, 'submit' => $submit);
		return $this;
	}
	function showButtons(){
		$this->add('FormButtons', null, 'buttons');
	}
	function showPage(){
	/**
	 * Shows <b>current</b> page
	 */
		$class = $this->pages[$this->getCurrentIndex()]['class']==''?'SomePage':
			$this->pages[$this->getCurrentIndex()]['class'];
		$this->page = $this->add($class, null, 'Content', 
			array($this->pages[$this->getCurrentIndex()]['template'], 'Content'));
		$this->template->set('Title', $this->pages[$this->getCurrentIndex()]['title']);
		$this->memorize('current_page', $this->current_index);
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
