<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * App_Web extends an APP of CommandLine applications with knowledge of HTML
 * templates, understanding of pages and routing.
 *
 * @link http://agiletoolkit.org/learn/understand/api
 * @link http://agiletoolkit.org/learn/template
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class App_Web extends App_CLI {

    /** Cleaned up name of the currently requested page */
    public $page=null;

    /* Root page where URL will send when ('/') is encountered @todo: make this work properly */
    public $index_page='index';

    /** recorded time when execution has started */
    public $start_time=null;

    /** Skin for web application templates */
    public $skin;

    /** Set a title of your application, will appear in <title> tag */
    public $title;


    // {{{ Start-up
    function __construct($realm=null,$skin='default'){
        $this->start_time=time()+microtime();

        $this->skin=$skin;
        try {

            parent::__construct($realm);
        }catch (Exception $e){

            // This exception is used to abort initialisation of the objects but when
            // normal rendering is still required
            if($e instanceof Exception_Stop)return;

            $this->caughtException($e);
        }
    }

    /**
     * Executed before init, this method will initialize PageManager and
     * pathfinder
     */
    function _beforeInit()
    {
        $this->pm=$this->add($this->pagemanager_class, $this->pagemanager_options);
        $this->pm->parseRequestedURL();
        parent::_beforeInit();
    }

    /** Redifine this function instead of default constructor */
    function init(){
        $this->getLogger();

        // Verify Licensing
        //$this->licenseCheck('atk4');

        // send headers, no caching
        $this->sendHeaders();

        $this->cleanMagicQuotes();

        parent::init();

        /** In addition to default initialization, set up logger and template */
        $this->initializeTemplate();


        if(get_class($this)=='App_Web'){
            $this->setConfig(array('url_postfix'=>'.php','url_prefix'=>''));
        }
    }
    /** Magic Quotes were a design error. Let's strip them if they are enabled */
    function cleanMagicQuotes(){
        if (!function_exists("stripslashes_array")){
            function stripslashes_array(&$array, $iterations=0) {
                if ($iterations < 3){
                    foreach ($array as $key => $value){
                        if (is_array($value)){
                            stripslashes_array($array[$key], $iterations + 1);
                        } else {
                            $array[$key] = stripslashes($array[$key]);
                        }
                    }
                }
            }
        }

        if (get_magic_quotes_gpc()){
            stripslashes_array($_GET);
            stripslashes_array($_POST);
            stripslashes_array($_COOKIE);
        }
    }
    /**
     * Sends default headers. Re-define to send your own headers
     *
     * @return [type] [description]
     */
    function sendHeaders(){
        header("Content-Type: text/html; charset=utf-8");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");               // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
        header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");                                     // HTTP/1.0
    }
    /**
     * Call this method if you want to see execution time on the bottom of your pages
     *
     * @return [type] [description]
     */
    function showExecutionTime(){
        $self=$this;
        $this->addHook('post-render-output',array($this,'_showExecutionTime'));
        $this->addHook('post-js-execute',array($this,'_showExecutionTimeJS'));
    }
    /** @ignore */
    function _showExecutionTime(){
        echo 'Took '.(time()+microtime()-$this->start_time).'s';
    }
    /** @ignore */
    function _showExecutionTimeJS(){
        echo "\n\n/* Took ".number_format(time()+microtime()-$this->start_time,5).'s */';
    }
    // }}}

    // {{{ Obsolete
    /** This method is called when exception was caught in the application */
    function caughtException($e){
        $this->hook('caught-exception',array($e));
        throw $e;
        echo "<font color=red>Problem with your request.</font>";
        echo "<p>Please use 'Logger' class for more sophisticated output<br>\$app-&gt;add('Logger');</p>";
        exit;
    }

    function outputWarning($msg,$shift=0){
        if($this->hook('output-warning',array($msg,$shift)))return true;
        echo "<font color=red>",$msg,"</font>";
    }
    function outputDebug($msg,$shift=0){
        if($this->hook('output-debug',array($msg,$shift)))return true;
        echo "<font color=blue>",$msg,"</font><br>";
    }

    // }}}

    // {{{ Sessions
    /** Initializes existing or new session */
    public $_is_session_initialized=false;
    function initializeSession($create=true){
        /* Attempts to re-initialize session. If session is not found,
           new one will be created, unless $create is set to false. Avoiding
           session creation and placing cookies is to enhance user privacy.
        Call to memorize() / recall() will automatically create session */



        if($this->_is_session_initialized || session_id())return;

        // Change settings if defined in settings file
        $params=session_get_cookie_params();

        $params['httponly']=true;   // true by default

        foreach($params as $key=>$default){
            $params[$key]=$this->app->getConfig('session/'.$key,$default);
        }

        if($create===false && !isset($_COOKIE[$this->name]))return;
        $this->_is_session_initialized=true;
        session_set_cookie_params(
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
        session_name($this->name);
        session_start();
    }
    /** Completely destroy existing session */
    function destroySession(){
        if($this->_is_session_initialized){
            $_SESSION=array();
            if(isset($_COOKIE[$this->name]))
               setcookie($this->name/*session_name()*/, '', time()-42000, '/');
            session_destroy();
            $this->_is_session_initialized=false;
        }
    }
    // }}}

    // {{{ Sticky GET Argument implementation. Register stickyGET to have it appended to all generated URLs
    public $sticky_get_arguments = array();
    /**
     * Make current get argument with specified name automatically appended to all generated URLs
     *
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    function stickyGet($name){
        $this->sticky_get_arguments[$name]=@$_GET[$name];
        return $_GET[$name];
    }
    /**
     * Remove sticky GET which was set by stickyGET
     *
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    function stickyForget($name){
        unset($this->sticky_get_arguments[$name]);
    }
    /** @ignore - used by URL class */
    function getStickyArguments(){
        return $this->sticky_get_arguments;
    }

    function get($name){
        return $_GET[$name];
    }

    function addStylesheet($file,$ext='.css',$locate='css'){
        //$file=$this->app->locateURL('css',$file.$ext);
        if(@$this->included[$locate.'-'.$file.$ext]++)return;

        if(strpos($file,'http')!==0 && $file[0]!='/'){
            $url=$this->locateURL($locate,$file.$ext);
        }else $url=$file;

        $this->template->appendHTML('js_include',
                '<link type="text/css" href="'.$url.'" rel="stylesheet" />'."\n");
        return $this;
    }


    // }}}

    // {{{ Very Important Methods
    /** Call this method from your index file. It is the main method of Agile Toolkit */
    function main(){
        try{
            // Initialize page and all elements from here
            $this->initLayout();
        }catch(Exception $e){
            if(!($e instanceof Exception_Stop))
                return $this->caughtException($e);
            //$this->caughtException($e);
        }

        try{
            $this->hook('post-init');
            $this->hook('afterInit');

            $this->hook('pre-exec');
            $this->hook('beforeExec');

            if(isset($_GET['submit']) && $_POST){
                $this->hook('submitted');
            }

            $this->hook('post-submit');
            $this->hook('afterSubmit');

            $this->execute();
        }catch(Exception $e){
            $this->caughtException($e);
        }
        $this->hook('saveDelayedModels');
    }
    /** Main execution loop */
    function execute(){
        $this->rendered['sub-elements']=array();
        try {
            $this->hook('pre-render');
            $this->hook('beforeRender');
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
            if($e instanceof Exception_Stop){
                $this->hook('cut-output');
                echo @$e->result;
                $this->hook('post-render-output');
                return;
            }
            throw $e;

        }
    }
    /** Renders all objects inside applications and echo all output to the browser */
    function render(){
        $this->hook('pre-js-collection');
        if(isset($this->app->jquery) && $this->app->jquery)$this->app->jquery->getJS($this);

        if(!($this->template)){
            throw new BaseException("You should specify template for APP object");
        }

        $this->hook('pre-render-output');
        if(headers_sent($file,$line)){
            echo "<br/>Direct output (echo or print) detected on $file:$line. <a target='_blank' "
                ."href='http://agiletoolkit.org/error/direct_output'>Use \$this->add('Text') instead</a>.<br/>";
        }
        echo $this->template->render();
        $this->hook('post-render-output');
    }
    // }}}

    // {{{ Miscelanious Functions
    /** Render only specified object or object with specified name */
    function cut($object){
        $_GET['cut_object']=is_object($object)?$object->name:$object;
        return $this;
    }
    /** Perform instant redirect to another page */
    function redirect($page=null,$args=array()){
        /*
         * Redirect to specified page. $args are $_GET arguments.
         * Use this function instead of issuing header("Location") stuff
         */
        $url=$this->url($page,$args);
         if($this->app->isAjaxOutput()) {
             if($_GET['cut_page']) {
                 echo "<script>".$this->app->js()->redirect($url)."</script>Redirecting page...";
                 exit;
             }
             else {
                 $this->app->js()->redirect($url)->execute();
             }
        }
        header("Location: ".$url);
        exit;
    }
    /** Called on all templates in the system, populates some system-wide tags */
    function setTags($t){

        // Determine Location to atk_public
        if ($this->app->pathfinder && $this->app->pathfinder->atk_public) {
            $q=$this->app->pathfinder->atk_public->getURL();
        } else {
            $q='http://www4.agiletoolkit.org/atk4';
        }

        $t->trySet('atk_path',$q.'/');
        $t->trySet('base_path',$q=$this->app->pm->base_path);

        // We are using new capability of SMlite to process tags individually
        try {
            $t->eachTag($tag='template',array($this,'_locateTemplate'));
            $t->eachTag($tag='public',array($this,'_locatePublic'));
            $t->eachTag($tag='js',array($this,'_locateJS'));
            $t->eachTag($tag='css',array($this,'_locateCSS'));
            $t->eachTag($tag='page',array($this,'_locatePage'));
        } catch (Exception $e) {
            throw $e
                ->addMoreInfo('processing_tag',$tag)
                ->addMoreInfo('template',$t->template_file)
                ;
        }

        $this->hook('set-tags',array($t));
    }
    /** Returns true if browser is going to EVAL output. */
    function isAjaxOutput(){
        // TODO: rename into isJSOutput();
        return isset($_POST['ajax_submit']) || ($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest');
    }
    /** @private */
    function _locateTemplate($path){
        return $this->locateURL('public',$path);
    }
    function _locatePublic($path){
        return $this->locateURL('public',$path);
    }
    function _locateJS($path){
        return $this->locateURL('js',$path);
    }
    function _locateCSS($path){
        return $this->locateURL('css',$path);
    }
    /** @private */
    function _locatePage($path){
        return $this->url($path);
    }
    /**
     * Only show $object in the final rendering
     * @obsolete and should be removed in 4.4
     */
    function renderOnly($object){
        return $this->cut($object);
    }
    // }}}

    // {{{ Layout implementation
    private $layout_initialized=false;
    /** Implements Layouts. Layout is region in shared template which may be replaced by object */
    function initLayout(){
        if($this->layout_initialized)throw $this->exception('Please do not call initLayout() directly from init()','Obsolete');
        $this->layout_initialized=true;
    }

    // TOOD: layouts need to be simplified and obsolete, because we have have other layouts now.
    // doc/layouts
    //
    /** Register new layout, which, if has method and tag in the template, will be rendered */
    function addLayout($name){
        if(!$this->template)return;
        // TODO: change to functionExists()
        if(method_exists($this,$lfunc='layout_'.$name)){
            if($this->template->is_set($name)){
                $this->$lfunc();
            }
        }
        return $this;
    }
    /** Default handling of Content page. To be replaced by App_Frontend */
    function layout_Content(){
        // This function initializes content. Content is page-dependant

        $page=str_replace('/','_',$this->page);

        if(method_exists($this,$pagefunc='page_'.$page)){
            $p=$this->add('Page',$this->page,'Content');
            $this->$pagefunc($p);
        }else{
            $this->app->locate('page',str_replace('_','/',$this->page).'.php');
            $this->add('page_'.$page,$page,'Content');
            //throw new BaseException("No such page: ".$this->page);
        }
    }
    /** Default template for the application. Redefine to add your own rules. */
    function defaultTemplate(){
        return array('html');
    }
    // }}}
}
