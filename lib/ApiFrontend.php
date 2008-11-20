<?php
/**
 * Class represents a simple CMS API containing common entities:
 * - news
 * - static pages
 * - events
 * - jobs
 * This API works not only with pages, but also with RSS channels
 * It is assumed that RSS channels are all under rss/ dir of project root,
 * similar to page/ dir for pages.
 *
 * Created on 23.09.2008 by *Camper* (cmd@adevel.com)
 */
class ApiFrontend extends ApiWeb{
	protected $page_object=null;
	protected $content_type='page';	// content type: rss/page/etc

	function init(){
		parent::init();
		$this->getLogger();
		$this->initializeTemplate();
		// base url is requred due to a Home/Events/Article.html links style
		$this->template->trySet('base_url',$this->getBaseURL());
	}
	function layout_Content(){
		// required class prefix depends on the content_type
		try{
			// This function initializes content. Content is page-dependant
			$class=$this->content_type.'_'.$this->page;
			if(method_exists($this,$class)){
				// for page we add Page class, for RSS - RSSchannel
				$this->page_object=$this->add($this->content_type=='page'?'Page':'RSSchannel',$this->page);
				$this->$class($this->page_object);
			}else{
				if(loadClass($class))
					$this->page_object=$this->add($class,$this->page,'Content');
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
	function calculatePageName(){
		$u=$this->getServerURL();
		// leading slash / should be removed
		if((isset($u[0])) and ($u[0]=='/'))$u=substr($u,1);
		if(stripos($u,'.xml')!==false)$this->content_type='rss';
		else $this->content_type='page';
		// renaming path to name
		$u=str_replace('/','_',$u);
		// removing extensions
		$u=str_ireplace('.xml','',$u);
		$u=str_ireplace('.html','',$u);
		// assigning default page
		if(!$u)$u=$this->index_page;
		$_GET['page']=$u;
		parent::calculatePageName();
	}
	function getBaseURL(){
		$r=parent::getBaseURL();
		// here we need additional path
		return $r.(isset($_SERVER['REDIRECT_URL_ROOT'])?$_SERVER['REDIRECT_URL_ROOT']:'');
	}
	function getServerURL(){
		$u=$_SERVER['REDIRECT_URL'];
		// removing server name and URL root from path
		// url_root value should be in $_SERVER, provided by .htaccess:
		// RewriteRule .* - [E=URL_ROOT:/]
		$url_root=$this->getUrlRoot()=='/'?'':$this->getUrlRoot();
		$u=str_ireplace($url_root,'',$u);
		return $u;
	}
	function getUrlRoot(){
		return isset($_SERVER['REDIRECT_URL_ROOT'])?$_SERVER['REDIRECT_URL_ROOT']:'/';
	}
	function getRSSURL($rss,$args=array()){
		$tmp=array();
		foreach($args as $arg=>$val){
			if(!isset($val) || $val===false)continue;
			if(is_array($val)||is_object($val))$val=serialize($val);
			$tmp[]="$arg=".urlencode($val);
		}
		return
			$rss.'.xml'.($tmp?'?'.join('&',$tmp):'');
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
