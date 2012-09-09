<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Base class for Command-Line Applications. If you need to share
 * code between multiple APIs, create a controller.
 *
 * More Info
 *  @link http://agiletoolkit.org/learn/learn/understand/api
 *  @link http://agiletoolkit.org/doc/apicli
 */
/*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class ApiCLI extends AbstractView {

    /** Default database connection.
     * @see dbConnect()  */
    public $db=null;
    
    /** Configuration loaded from config.php and config-defaults.php files. Use getConfig() to access */
    protected $config = null;

    /** Points to the instance of system logger (lib/Logger.php) for enriching error logging */
    public $logger=null;

    /** Points to the instance of PathFinder class, which is used to locate resource files. PathFinder
     * is the first class to be initialized after API. */
    public $pathfinder=null;
    protected $pathfinder_class='PathFinder';

    /** Skin for web application templates */
    public $skin;

    /** For fast compatibility checks. To be more specific use $api->requires() */
    public $atk_version=4.2;

    /** $pr points to profiler. All lines referencing $pr myst be prefixed with the
     * 4-symbol sequence "/ ** /" (no spaces). When deploying to production, you can
     * remove all lines from all files starting with the sequence without affecting
     * how your application works, but slightly improving performance */
    /**/public $pr;

    /** Maximum length of the name arguments (for SUHOSIN) */
    public $max_name_length=60;

    /** Contains list of hashes which used for name shortening */
    public $unique_hashes=array();

    // {{{ Start-up of application
    /** Initializes properties of the application. Redefine init() instead of this */
    function __construct($realm=null){
        if(!$realm)$realm=get_class($this);
        $this->owner = $this;
        $this->name  = $realm;
        $this->api   = $this;

        // Profiler is a class for benchmarking your application. All calls to pr 
        /**/$this->pr=new Dummy();

        try {
            $this->add($this->pathfinder_class);
            $this->init();


        }catch(Exception $e){

            // This exception is used to abort initialisation of the objects but when
            // normal rendering is still required
            if($e instanceof Exception_StopInit)return;

            $this->caughtException($e);
        }
    }
    // }}}

    // {{{ Management of Global Methods 
    /** Register method with all objects in Agile Toolkit. 
     * @see AbstractObject::hasMethod()
     * @see AbstractObject::__call()
     */
    function addGlobalMethod($name,$callable){
        if($this->hasMethod($name))
            throw $this->exception('Registering method twice');
        $this->addHook('global-method-'.$name,$callable);
    }
    /** Returns if a global method with such name was defined */
    function hasGlobalMethod($name){
        return isset($this->hooks['global-method-'.$name]);
    }
    /** Removes global method */
    function removeGlobalMethod($name){
        $this->removeHook('global-method-'.$name);
    }
    // }}}

    // {{{ Localization
    /** Redefine this function to introduce your localization. Agile Toolkit will pass all system strings
     * through this method. If some methods are not properly passed through, please fork Agile Toolkit in
     * http://github.com/atk4/atk4/ , modify, commit, push your fix and notify authors of Agile Toolkit
     * using contact form on http://agiletoolkit.org/contact
     *
     * See file CONTRIBUTING
     */
    function _($str){

        $x=$this->hook('localizeString',array($str));
        if($x)return $x[0];

        return $str;
    }
    // }}}

    // {{{ PathFinder and PageManager bindings
    /** Find relative path to the resource respective to the current directory. */
    function locate($type,$filename='',$return='relative'){
        return $this->pathfinder->locate($type,$filename,$return);
    }
    /** Calculate URL pointing to specified resource */
    function locateURL($type,$filename=''){
        return $this->pathfinder->locate($type,$filename,'url');
    }
    /** Return full system path to specified resource */
    function locatePath($type,$filename=''){
        return $this->pathfinder->locate($type,$filename,'path');
    }
    /** Add new location with additional resources */
    function addLocation($location,$contents){
        return $this->pathfinder->addLocation($location,$contents);
    }
    /** Returns base URL of this Web application installation. If you require
     * link to a page, you can use URL::useAbsoluteURL();
     *
     * @see URL::useAbsoluteURL() */
    function getBaseURL(){
        return $this->pm->base_path;
    }
    /** Generates URL for specified page. Useful for building links on pages or emails. Returns URL object. */
    function url($page=null,$arguments=array()){
        if(is_object($page) && $page instanceof URL){
            // we receive URL
            return $page->setArguments($arguments);
        }
        $url=$this->add('URL','url_'.$this->url_object_count++);
        unset($this->elements[$url->short_name]);   // garbage collect URLs
        if(substr($page,0,7)=='http://')$url->setURL($page);elseif
            (substr($page,0,8)=='https://')$url->setURL($page);else
                $url->setPage($page);
        return $url->setArguments($arguments);
    }
    /** @obsolete use url() */
    function getDestinationURL($page=null,$arguments=array()){ return $this->url($page,$arguments); }
    // }}}

    // {{{ Error handling
    /** Initialize logger or return existing one */
    function getLogger($class_name='Logger'){
        if(is_null($this->logger)){
            $this->logger=$this->add($class_name);
        }
        return $this->logger;
    }
    /** Is executed if exception is raised during execution. Re-define to have custom handling of exceptions system-wide */
    function caughtException($e){
        $this->hook('caught-exception',array($e));
        echo get_class($e),": ".$e->getMessage();
        exit;
    }
    /** @obsolete */
    function outputFatal($msg,$shift){
        $this->hook('output-fatal',array($msg,$shift+1));
        echo "Fatal: $msg\n";exit;
    }
    /** @obsolete */
    function outputWarning($msg,$shift=0){
        if($this->hook('output-warning',array($msg,$shift)))return true;
        echo "warning: $msg\n";
    }
    /** @obsolete */
    function outputDebug($msg,$shift=0){
        if($this->hook('output-debug',array($msg,$shift)))return true;
        echo "debug: $msg\n";
    }
    /** @obsolete */
    function outputInfo($msg,$shift=0){
        if($this->hook('output-info',array($msg,$shift)))return true;
        echo "info: $msg\n";
    }
    /** @obsolete */
    function upCall($type,$args=array()){
        /**
         * Uncaught call default handler.
         *
         * In your application you should handle your own calls. If you do not,
         * the call will be forwarded to API and finaly this method will be
         * executed displaying error message about uncaught call
         */
        if(($x=parent::upCall($type,$args))===false){
            throw new BaseException("Uncaught upCall");
        }
    }
    // }}}

    // {{{ Configuration File Handling 
    /** Executed when trying to access config parameter which is not find in the file */
    function configExceptionOrDefault($default,$exceptiontext){
        if($default!='_config_get_false')return $default;
        throw new BaseException($exceptiontext);
    }
    /** Read config file and store it in $this->config. Use getConfig() to access */
    function readConfig($file='config.php'){
        $orig_file = $file;
        if(is_null($this->config))$this->config=array();
        $config=array();
        if(strpos($file,'/')===false){
            $file=getcwd().'/'.$file;
        }
        if (!file_exists($file)){
            foreach (explode(PATH_SEPARATOR, get_include_path()) as $path){
                $fullpath = $path . DIRECTORY_SEPARATOR . $orig_file;
                if (file_exists($fullpath)){
                    $file = $fullpath;
                    break;
                }
            }
        }
        if (file_exists($file)) {
            // some tricky thing to make config be read in some cases it could not in simple way
            if(!$config)global $config;
            include_once $file;
        }

        $this->config = array_merge($this->config,$config);

        $tz = $this->getConfig('timezone',null);
        if(!is_null($tz) && function_exists('date_default_timezone_set')){
            // with seting default timezone
            date_default_timezone_set($tz);
        }


    }
    /** Manually set configuration option */
    function setConfig($config=array()){
        $this->config=safe_array_merge($this->config,$config);
    }
    /** Load config if necessary and look up corresponding setting */
    function getConfig($path, $default_value = undefined){
        /**
         * For given path such as 'dsn' or 'logger/log_dir' returns
         * corresponding config value. Throws ExceptionNotConfigured if not set.
         *
         * To find out if config is set, do this:
         *
         * $var_is_set=true;
         * try { $api->getConfig($path); } catch ExceptionNotConfigured($e) { $var_is_set=false; };
         */
        if(is_null($this->config)){
            $this->readConfig('config-default.php');
            $this->readConfig();
        }
        $parts = explode('/',$path);
        $current_position = $this->config;
        foreach($parts as $part){
            if(!array_key_exists($part,$current_position)){
                if($default_value!==undefined)return $default_value;
                throw $this->exception("Configuration parameter is missing in config.php",'NotConfigured')
                    ->addMoreInfo("missign_line"," \$config['".join("']['",explode('/',$path))."']");
            }else{
                $current_position = $current_position[$part];
            }
        }
        return $current_position;
    }
    // }}}

    // {{{ Version handling
    /** Determine version of Agile Toolkit or specified plug-in */
    private $version_cache=null;
    function getVersion($of='atk'){
        // TODO: get version of add-on
        if(!$this->version_cache){
            $f=$this->api->pathfinder->atk_location->base_path.DIRECTORY_SEPARATOR.'VERSION';
            if(file_exists($f)){
                $this->version_cache=trim(file_get_contents($f));
            }else{
                $this->version_cache='4.0.1';
            }
        }
        return $this->version_cache;
    }
    /** Verifies version. Should be used by addons. For speed improvement, redefine this into empty function */
    function requires($addon='atk',$v,$return_only=false){
        $cv=$this->getVersion($addon);
        if(version_compare($cv,$v)<0){
            if($addon=='atk'){
                $e=$this->exception('Agile Toolkit version is too old');
            }else{
                $e=$this->exception('Add-on is outdated')
                    ->addMoreInfo('addon',$addon);
            }
            $e->addMoreInfo('required',$v)
                ->addMoreInfo('you have',$cv);
            throw $e;
        }

        // Possibly we need to enable compatibility version
        if($addon=='atk'){
            if(
                version_compare($v,'4.2')<0 &&
                version_compare($v,'4.1.4')>=0 
            ){
                $this->add('Controller_Compat');
                return true;
            }
        }
        return true;
    }
    /** @obsolete use @requires */
    function versionRequirement($v,$return_only=false){
        return $this->requires('atk',$v,$return_only);
    }
    // }}}

    // {{{ Database connection handling
    /** Use database configuration settings from config file to establish default connection */
    function dbConnect($dsn=null){
        $this->db=$this->add('DB')->connect($dsn);
        return $this;
    }
    /** Attempts to connect, but does not raise exception on failure. */
    function tryConnect($dsn){
        $this->db=DBlite::tryConnect($dsn);
    }
    // }}}
}
