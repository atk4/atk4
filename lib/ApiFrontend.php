<?php
/**
 * Class represents a simple CMS API containing common entities:
 * - news
 * - static pages
 * - events
 * - jobs
 *
 * Created on 23.09.2008 by *Camper* (cmd@adevel.com)
 */
class ApiFrontend extends ApiWeb{
	protected $page_object=null;

	function init(){
		parent::init();
		$this->getLogger();
		$this->initializeTemplate();
		// base url is requred due to a Home/Events/Article.html links style
		$this->template->trySet('base_url',$this->getBaseURL());
	}
	function layout_Content(){
		try{
			// This function initializes content. Content is page-dependant
			if(method_exists($this,$pagefunc='page_'.$this->page)){
				$this->page_object=$this->add('Page',$this->page);
				$this->$pagefunc($this->page_object);
			}else{
				if(loadClass('page_'.$this->page))
					$this->page_object=$this->add('page_'.$this->page,$this->page,'Content');
				else{
					// page not found, trying to load static content
					if($this->template->findTemplate($static_page='page_'.strtolower($this->page)))
						$this->page_object=$this->add('Page',$this->page,'Content',array($static_page,'_top'));
					else{
						//header("HTTP/1.0 404 Not Found");
						$this->page_object=$this->add('Page',$this->page,'Content',array('page_404','_top'));
					}
				}
			}
		}catch(Exception $e){
			$this->processException($e);
		}
	}
	/**
	 * Called on unhandled exception to show user friendly message
	 */
	function processException($e){
		if (isset ($this->api->logger) && !is_null($this->api->logger))
			$this->api->logger->logException($e);
		// now showing this exception
		if ($this->isAjaxOutput()) {
			$this->add('Ajax')->displayAlert($this->formatAlert($e->getMessage()))->execute();
		}
		// rendering error page
		$t=$this->add('SMlite')->loadTemplate('page_error');
		$t->trySet('message',$e->getMessage());
		echo $t->render();
		exit;
	}
	function formatAlert($s) {
		$r = addslashes(strip_tags($s));
		$r = str_replace("\n", " | ", $r);
		return $r;
	}
	/**
	 * Adds a floating frame that will reload its content on show.
	 */
	function addFrame($object,$title,$page){
		$frame=$object->add('AjaxFrame',"ff_$page");
		$frame->setObject($page);
		$frame->frame($title,null,null,'width="700"');
		$frame->getFrame()->add('Button','btn_close')
			->setLabel('Close')->onClick()->setFrameVisibility($frame,false);
		return $frame;
	}
}
