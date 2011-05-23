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
 * This is core API class for AModules3. It implements abstract base for API
 * class. ApiBase does not contain support for HTTP/PHP request handling,
 * creating pagest etc. Use this class directly only if you re-use form methods
 * from other libriaries and you have your own implementation of request
 * handling.
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 */

class ApiWeb extends ApiCLI {

	/**
	 * DB handle
	 */
	public $page=null;
	private $page_title = null;	// TODO remove this property
	public $apinfo=array();

	protected $page_base=null;
	public $index_page='index';
	protected $sticky_get_arguments = array();
	protected $ajax_class='Ajax';

	public $start_time=null;

	function __construct($realm=null,$skin='default'){
		$this->start_time=time()+microtime();

		$this->skin=$skin;
		try {
			parent::__construct($realm);
		}catch (Exception $e){

			// This exception is used to abort initialisation of the objects but when
			// normal rendering is still required
			if($e instanceof Exception_StopInit)return;

			$this->caughtException($e);
		}
	}
	function showExecutionTime(){
		$self=$this;
		$this->addHook('post-render-output',array($this,'_showExecutionTime'));
		$this->addHook('post-js-execute',array($this,'_showExecutionTimeJS'));
	}
	function _showExecutionTime(){
		echo 'Took '.(time()+microtime()-$this->start_time).'s';
	}
	function _showExecutionTimeJS(){
		echo "\n\n/* Took ".number_format(time()+microtime()-$this->start_time,5).'s */';
	}
	function initDefaults(){
		parent::initDefaults();
	}
	function initLayout(){
		$this->addLayout('Content');
        $this->upgradeChecker();
	}
    function upgradeChecker(){
        // Checks for ATK upgrades and shows current version
		if($this->template && $this->template->is_set('version')){
			$this->add('UpgradeChecker',null,'version');
		}
    }
	/////////////// C o r e   f u n c t i o n s ///////////////////
	function caughtException($e){
		$this->hook('caught-exception',array($e));
		echo "<font color=red>",$e,"</font>";
		echo "<p>Please use 'Logger' class for more sophisticated output<br>\$api-&gt;add('Logger');</p>";
		exit;
	}

	function outputWarning($msg,$shift=0){
		if($this->hook('output-warning',array($msg,$shift)))return true;
		echo "<font color=red>",$msg,"</font>";
	}
	function outputDebug($msg,$shift=0){
		if($this->hook('output-debug',array($msg,$shift)))return true;
		echo "<font color=red>",$msg,"</font><br>";
	}
	function outputInfo($msg,$shift=0){
		if($this->hook('output-info',array($msg,$shift)))return true;
		echo "<font color=red>",$msg,"</font>";
	}
	function init(){
		/**
		 * Redifine this function instead of default constructor. Do not forget
		 * to set $this->db to instance of DBlite.
		 */
		$this->initializeSession();

		// find out which page is to display
		//$this->calculatePageName();
		$this->add('PageManager');

		// send headers, no caching
		$this->sendHeaders();

		parent::init();

	}
	/**
	 * This function is called on AJAX request.
	 * @return corresponding
	 */
	function initializeSession(){
		// initialize session for this realm
		if($this->name && session_id()==""){
			// If name is given, initialize session. If not, initialize
			// later when loading config file.
			if(isset($_GET['SESSION_ID']))session_id($_GET['SESSION_ID']);
			session_name($this->name);
			session_start();
		}
	}
	function stickyGET($name){
		$this->sticky_get_arguments[$name]=@$_GET[$name];
	}
	function stickyForget($name){
		unset($this->sticky_get_arguments[$name]);
	}
	function getStickyArguments(){
		return $this->sticky_get_arguments;
	}

	function sendHeaders(){
		/**
		 * Send headers to browser
		 *
		 */

		header("Content-Type: text/html; charset=utf-8");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");               // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");                                     // HTTP/1.0
	}

	/////////////// This is what you should call //////////////////
	function main(){
		/**
		 * Call this function to do everything.
		 *
		 * This is main API function, which would do all initializing,
		 * input analysis, auth checking, the displaying
		 */

		try{
			$this->hook('pre-exec');

			if(isset($_GET['submit']) && $_POST){
				$this->downCall('submitted');
			}

			$this->hook('post-submit');

			$this->execute();
		}catch(Exception $e){
			$this->caughtException($e);
		}
	}

		/////////////// Application execution /////////////////////////
	function render(){
		if(isset($this->api->jquery) && $this->api->jquery)$this->api->jquery->getJS($this);

		if(!($this->template)){
			throw new BaseException("You should specify template for API object");
		}

		$this->hook('pre-render-output');
		echo $this->template->render();
		$this->hook('post-render-output');
	}
	function setTags($t){
		// absolute path to base location
		$t->trySet('atk_path',$q=
								$this->api->pathfinder->atk_location->getURL().'/');
		$t->trySet('base_path',$q=$this->api->pm->base_path);
		
		// We are using new capability of SMlite to process tags individually
		$t->eachTag('template',array($this,'_locateTemplate'));
		$t->eachTag('page',array($this,'_locatePage'));
	}
	function _locateTemplate($path){
		return $this->locateURL('template',$path);
	}
	function _locatePage($path){
		return $this->getDestinationURL($path);
	}
	function execute(){
		$this->rendered['sub-elements']=array();


		try {
			$this->hook('pre-render');
			$this->recursiveRender();
			if(isset($_GET['cut_object']))
				throw new BaseException("Unable to cut object with name='".$_GET['cut_object']."'. It wasn't initialized");
			if(isset($_GET['cut_region'])){
				if(!$this->cut_region_result)
					throw new BaseException("Unable to cut region with name='".$_GET['cut_region']."'");
				echo $this->cut_region_result;
				return;
			}
		}catch(Exception $e){
			if($e instanceof RenderObjectSuccess){
				$this->hook('cut-output');
				echo $e->result;
				$this->hook('post-render-output');
				return;
			}
			throw $e;

		}
	}

	function addLayout($name){
		if(method_exists($this,$lfunc='layout_'.$name)){
			if($this->template->is_set($name)){
				$this->$lfunc();
			}
		}
		return $this;
	}
	function layout_Content(){
		// This function initializes content. Content is page-dependant

		$page=str_replace('/','_',$this->page);

		if(method_exists($this,$pagefunc='page_'.$page)){
			$p=$this->add('Page',$this->page,'Content');
			$this->$pagefunc($p);
		}else{
			$this->api->locate('page',str_replace('_','/',$this->page).'.php');
			$this->add('page_'.$page,$page,'Content');
			//throw new BaseException("No such page: ".$this->page);
		}
	}
	function isAjaxOutput(){
		// TODO: rename into isJSOutput();
		return isset($_POST['ajax_submit']);
	}
	function redirect($page=null,$args=array()){
		/**
		 * Redirect to specified page. $args are $_GET arguments.
		 * Use this function instead of issuing header("Location") stuff
		 */
		header("Location: ".$this->getDestinationURL($page,$args));
		exit;
	}
	function setIndexPage($page){
		$this->index_page=$page;
		return $this;
	}
	function getIndexPage(){
		return $this->index_page;
	}
	function defaultTemplate(){
		return array('shared','_top');
	}
}

class RenderObjectSuccess extends Exception{
	public $result;
	function RenderObjectSuccess($r){
		$this->result=$r;
	}
}
