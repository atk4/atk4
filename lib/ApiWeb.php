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
    protected $page_base=null;

    public $index_page='Index';

    public $sticky_get_arguments = array();

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
        $this->calculatePageName();

        // send headers, no caching
        $this->sendHeaders();
        parent::init();

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
         */
        if(!isset($_GET['page'])){
            $this->page_base=basename($_SERVER['REDIRECT_URL']);
            if(substr($_SERVER['REDIRECT_URL'],-1,1)=='/'){
                $this->page_base=$this->index_page;
            }
            if(!strpos($this->page_base,'.') && !strpos($this->page_base,'&')){
                $_GET['page']=$this->page_base;
            }
        }

        if(!$this->page)$this->page = @$_GET['page'];
    }


    function getDestinationURL($page=null,$args=array()){
        $tmp=array();
        if(!$page)$page='index';
        foreach($args as $arg=>$val){
            if(!isset($val) || $val===false)continue;
            if(is_array($val)||is_object($val))$val=serialize($val);
            $tmp[]="$arg=".urlencode($val);
        }
        return 
            $this->getConfig('url_prefix','').
            $page.
            $this->getConfig('url_postfix','').
            ($tmp?'?'.join('&',$tmp):'');
        /*
        if($this->getConfig('url_prefix',false)){
            return $this->getConfig('url_prefix','').$page.($tmp?"&".join('&',$tmp):'');
        }else return $page.'.php'.($tmp?"?".join('&',$tmp):'');
        */
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
        /*
        if(isset($_GET['cut_object'])){
            $res=parent::downCall("object_render");
            if(!isset($res))throw new BaseException("Unable to cut object with name='".$_GET['cut_object']."'. It wasn't initialized");
        }elseif(isset($_GET['cut_region'])){
            parent::downCall("region_render");
        }else{
            parent::downCall("render");
        }
        */
    }
}

class RenderObjectSuccess extends Exception{
    public $result;
    function RenderObjectSuccess($r){
        $this->result=$r;
    }
}
