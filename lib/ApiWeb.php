<?php
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
    public $skin = null;      // skin used to render everything
    public $page_title = null;
    public $apinfo=array();

    protected $page_base=null;
    protected $index_page='Index';	// TODO: protect this
    protected $sticky_get_arguments = array();	// TODO: protect this
    protected $ajax_class='Ajax';

    function __construct($realm=null,$skin='kt2'){
        $this->skin=$skin;
        parent::__construct($realm);
    }
    function initDefaults(){
    	parent::initDefaults();
    }
	function initLayout(){
		$this->addLayout('Content');
	}
	function getAjaxClass(){
		return $this->ajax_class;
	}
	function setAjaxClass($class){
		$this->ajax_class=$class;
		return $this;
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

        // checking session expire if needed
        if(isset($_POST['check_session'])||isset($_GET['check_session']))$this->checkSessionExpired();

        // find out which page is to display
        $this->calculatePageName();

        // send headers, no caching
        $this->sendHeaders();
        parent::init();

    }
    /**
     * This function is called on AJAX request.
     * @return corresponding
     */
    function checkSessionExpired(){
    	$result=(isset($_SESSION['o'])?0:1);
		header('Content-type: text/xml');
		echo "<session_check><expired>$result</expired></session_check>";
		exit;
    }
    function initializeSession(){
        // initialize session for this realm
        if($this->name && session_id()==""){
            // If name is given, initialize session. If not, initialize
            // later when loading config file.
            session_name($this->name);
            session_start();
        }
    }
    function stickyGET($name){
        $this->sticky_get_arguments[$name]=$_GET[$name];
    }
    function stickyForget($name){
		unset($this->sticky_get_arguments[$name]);
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
    function calculatePageName(){
        /**
         * Discover requested page name (class) from $_GET
         * $_GET should be properly initialized, including 'page' parameter,
         * before calling this method (e.g. with .htaccess)
         */
        if(!isset($_GET['page'])){
            $this->page_base=basename($_SERVER['REDIRECT_URL']);
            if(substr($_SERVER['REDIRECT_URL'],-1,1)=='/'){
                $this->page_base=$this->index_page;
            }
            list($page)=preg_split("/[.&]/",$this->page_base);
            if($page)$_GET['page']=$page;
            // why is that?!
            //if(!strpos($this->page_base,'.') && !strpos($this->page_base,'&')){
            //    $_GET['page']=$this->page_base;
            //}
        }

        if(!$this->page)$this->page = @$_GET['page'];
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
        if(!($this->template)){
            throw new BaseException("You should specify template for API object");
        }
        echo $this->template->render();
    }
    function execute(){
        $this->rendered['sub-elements']=array();

        $this->hook('pre-render');

        try {
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
                echo $e->result;
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

        if(method_exists($this,$pagefunc='page_'.$this->page)){
            $p=$this->add('Page',$this->page,'Content');
            $this->$pagefunc($p);
        }else{
            $this->add('page_'.$this->page,$this->page,'Content');
            //throw new BaseException("No such page: ".$this->page);
        }
    }
    function isClicked($button_name){
        /**
         * Will return true if button with this name was clicked
         */
        return isset($_POST[$button_name])||isset($_POST[$button_name.'_x']);
    }
    function isAjaxOutput(){
        return isset($_POST['ajax_submit']);
    }
    function redirect($page=null,$args=array()){
        /**
         * Redirect to specified page. $args are $_GET arguments.
         * Use this function instead of issuing header("Location") stuff
         */
        $this->api->not_html=true;
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
