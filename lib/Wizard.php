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
/**
 * Simple class to create wizard-like interfaces
 *
 * Created on 08.09.2006 by *Camper* (camper@adevel.com)
 */
class Wizard extends AbstractView{
	protected $pages=array();
	protected $buttons;
	protected $current;
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
		$this->current=array_search($page, $this->pages);
		if($this->current!==false&&$this->current>0){
			$this->addPrevButton();
		}
		if($this->current!==false&&$this->current<sizeof($this->pages)-1){
			$this->addNextButton();
		}else{
			$this->addFinishButton();
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

		return $this->buttons[$name]->onclick = $this->buttons[$name]->ajax()
			->useProgressIndicator($this->name.'_loading');

	}
	function addNextButton(){
		/**
		 * Adds a 'Next' button to the page. You can override this method to perform some specific
		 * actions when user proceeds to Next page
		 */
		$next=$this->addButton('Next');
		//if there is a form on the page - redirection should be done by this form
		if($this->form)$next->submitForm($this->form);
		else $next->redirect($this->api->page, array('step'=>$this->current+1));
	}
	function addPrevButton(){
		/**
		 * Adds a 'Previous' button to the page. You can override this method to perform some specific
		 * actions when user proceeds to Previous step
		 */
		$this->addButton('Previous')->redirect($this->api->page, array('step'=>$this->current-1));
	}
	function addFinishButton(){
		/**
		 * Adds a 'Finish' button to the page. You can override this method to perform some specific
		 * actions when user finishes wizard.
		 * DO NOT FORGET to assign $this->finish!
		 */
		$this->finish=$this->addButton('Finish');
	}
	function defaultTemplate(){
		return array('wizard', '_top');
	}
}
