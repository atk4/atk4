<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Base class for Command-Line Applications
 *
 * More Info
 *  @link http://agiletoolkit.org/learn/learn/understand/api
 *  @link http://agiletoolkit.org/doc/apicli
 */
/*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2011 Romans Malinovskis <atk@agiletech.ie>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class ApiCLI extends AbstractView {

    /** Default database connection */
    public $db=null;
    
    /** Configuration loaded from config.php and config-defaults.php files. Use getConfig() to access */
    protected $config = null;

    /** Points to the instance of system logger (lib/Logger.php) for enriching error logging */
    public $logger=null;

    /** Points to the instance of PathFinder class, which is used to locate resource files */
    protected $pathfinder_class='PathFinder';

    /** Skin for web application templates */
    public $skin;

    // {{{ Start-up of application
    /** Initialize application. Redefine in your application but always call parent */
    function __construct($realm=null){
        if(!$realm)$realm=get_class($this);
        $this->owner = null;
        $this->name  = $realm;
        $this->api   = $this;

        set_error_handler("error_handler");

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
    function init(){
        parent::init();
    }
    // }}}

    // {{{ Management of Global Methods 
    /** Register method with all objects in Agile Toolkit. Use only in controllers. */
    function addGlobalMethod($name,$callable){
        if($this->hasMethod($name))
            throw $this->exception('Registering method twice');
        $this->addHook('global-method-'.$name,$callable);
    }
    /** Use only in Controllers */
    function hasGlobalMethod($name){
        return isset($this->hooks['global-method-'.$name]);
    }
    /** Use only in Controllers */
    function removeGlobalMethod($name){
        $this->removeHook('global-method-'.$name);
    }
    // }}}

    // {{{ Localization
    /** Redefine this function to introduce your localization. Agile Toolkit will call it with some system strings */
    function _($str){
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
    /** Returns base URL of this Web application installation */
    function getBaseURL(){
        return $this->pm->base_path;
    }
    /** Generates URL for specified page. Useful for building links on pages or emails. Returns URL object. */
    function getDestinationURL($page=null,$arguments=array(),$full='depricated'){
        if($full!='depricated')throw new BaseException('Using 3rd argument for getDestinationURL is depricated');
        if(is_object($page) && $page instanceof URL){
            // we receive URL
            return $page->setArguments($arguments);
        }
        $url=$this->add('URL','url_'.$this->url_object_count++);
        if(substr($page,0,7)=='http://')$url->setURL($page);elseif
            (substr($page,0,8)=='https://')$url->setURL($page);else
                $url->setPage($page);
        return $url->setArguments($arguments);
    }
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
    /** Read config file and store it in memory */
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
    function getConfig($path, $default_value = '**undefined_value**'){
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
                if($default_value!=='**undefined_value**')return $default_value;
                throw new ExceptionNotConfigured("You must specify \$config['".
                        join("']['",explode('/',$path)).
                        "'] in your config.php");
            }else{
                $current_position = $current_position[$part];
            }
        }
        return $current_position;
    }
    // }}}

    // {{{ Version handling
    /** Determine version of Agile Toolkit */
    private $version_cache=null;
    function getVersion($of='atk'){
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
    /** Verifies version. Should be used by addons */
    function versionRequirement($v,$return_only=false){
        if(($vc=version_compare($this->getVersion(),$v))<0){
            if($soft)return false;
            throw new BaseException('Agile Toolkit is too old. Required at least: '.$v.', you have '.$this->getVersion());
        }
        return true;
    }
    // }}}

    // {{{ Database connection handling
    /** Use database configuration settings from config file to establish default connection */
    function dbConnect($dsn=null){
        if (is_null($dsn)) $dsn=$this->getConfig('dsn');
        $result=$this->db=DBlite::connect($dsn);
        if(is_string($result))throw new DBlite_Exception($result,"Please edit 'config.php' file, where you can set your database connection properties",2);
        $this->db->owner=$this;
        $this->db->api=$this;
        return $this;
    }
    /** Attempts to connect, but does not raise exception on failure */
    function tryConnect($dsn){
        $this->db=DBlite::tryConnect($dsn);
    }
    // }}}
}
