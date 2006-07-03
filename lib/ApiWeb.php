<?
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
        echo "<font color=red>",$msg,"</font>";
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
        if($this->name){
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

        if(isset($_GET['cut_object'])){
            $res=parent::downCall("object_render");
            if(!isset($res))throw new BaseException("Unable to cut object with name='".$_GET['cut_object']."'. It wasn't initialized");
        }elseif(isset($_GET['cut_region'])){
            parent::downCall("region_render");
        }else{
            parent::downCall("render");
        }
    }
}
