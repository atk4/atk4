<?php // vim:ts=4:sw=4:et:fdm=marker
/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Base class for Command-Line Applications. The purpose of Application class
 * is to initialize all the other classes and aid their connectivity. API
 * class can be accessed from any object through $this->api property. 
 *
 * API classes are derrived from AbstractView because normally they would have
 * a template and will be able to render themselves consistently to any other
 * view in the system. Although ApiCLI does not do any rendering, it's descendants
 * do
 *
 * @link http://agiletoolkit.org/doc/api
 */
class ApiCLI extends AbstractView
{
    /**
     * In a typical application, one connection to the database is enough for
     * majority of applications. Calling $api->dbConnect will read Database
     * data from config file and store it in $db property. If you requires
     * a more advanced connectivity or multiple connections, you can manually
     * initialize more database connections.
     *
     * @see dbConnect()  
     */
    public $db=null;

    /**
     * ApiCLI implements a API for accessing your application configuration.
     * Once configuration file is read, data is saved inside this property.
     * 
     * @see getConfig()
     * @see readConfig()
     */
    protected $config = null;

    /**
     * Without logger, API will dump out errors and exceptions in a very brief
     * and straigtforward way. Logger is a controller which enhances error
     * output and in most cases you do need one. Logger can be further configured
     * to either output detailed errors or show brief message instead.
     *
     * @see Logger
     */
    public $logger=null;

    /**
     * If you want to use your own logger class, redefine this property
     */
    public $logger_class='Logger';

    /**
     * PathFinder is a controller which is responsible for locating resources,
     * such as PHP includes, JavaScript files, templates, etc. API Initializes
     * PathFinder as soon as possible, then defines "Locations" which describe
     * type of data found in different folders.
     */
    public $pathfinder=null;

    /**
     * If you would want to use your own PathFinder class, you must change
     * this property and include it.
     */
    protected $pathfinder_class='PathFinder';

    /**
     * This is a major version of Agile Toolkit. The API of Agile Toolkit is
     * very well established and changes rarely. Your application would generally
     * be compatible throughout the same major version of Agile Tooolkit.
     *
     * @see requires();
     */
    public $atk_version=4.2;

    /**
     * Some Agile Toolkit classes contain references to profiler. Profiler
     * would be initialized early and reference would be kept in this variable.
     * Profiler measures relative time it took in certain parts of your
     * application to help you find a slow-perfoming parts of application.
     *
     * By default $pr points to empty profiler object, which implements empty
     * methods. All the lines referencing $pr myst be prefixed with the
     * 4-symbol sequence "/ ** /" (no spaces). If you want to speed up Agile
     * Toolkit further, you can eliminate all lines started with this sequence
     * from your source code. 
     */
    /**/public $pr;

    /**
     * Object in Agile Toolkit contain $name property which is derrived from
     * the owher object and keeps extending as you add objects deeper into
     * run-time tree. Sometimes that may generate long names. Long names are
     * difficult to read, they increase HTML output size but most importantly
     * they may be restricted by security extensions such as SUHOSIN.
     *
     * Agile Toolkit implements a mechanism which will replace common beginning
     * of objects with an abbreviation thus keeping object name length under
     * control. This variable defines the maximum length of the object's $name.
     * Be mindful that some objects will concatinate theri name with fields,
     * so the maximum letgth of GET argument names can exceed this value by
     * the length of your field. 
     *
     * We recommend you to increase SUHOSIN get limits if you encounter any
     * problems. Set this value to "false" to turn off name shortening.
     */
    public $max_name_length=60;

    /**
     * As more names are shortened, the substituted part is being placed into
     * this hash and the value contains the new key. This helps to avoid creating
     * many sequential prefixes for the same character sequenece.
     */
    public $unique_hashes=array();

    // {{{ Start-up of application
    /**
     * Regular objects in Agile Toolkit use init() and are added through add().
     * Application class is differente, you use "new" keyword because it's the
     * first class to be created. That's why constructor will perform quite a
     * bit of initialization.
     *
     * Do not redefine constructor but instead use init();
     *
     * $realm defines a top-level name of your application. This impacts all
     * id= prefixes in your HTML code, form field names and many other things,
     * such as session name. If you have two application classes which are part
     * of same web app and may want to use same realm, but in other cases it's
     * preferably that you keep realm unique on your domain in the interests
     * of security.
     *
     * @param string $realm Will become $api->name
     */
    function __construct($realm = null)
    {
        if (!$realm) {
            $realm=get_class($this);
        }
        $this->owner = $this;
        $this->name  = $realm;
        $this->api   = $this;

        // Profiler is a class for benchmarking your application. All calls to pr
        /**/$this->pr=new Dummy();

        try {
            $this->add($this->pathfinder_class);
            $this->init();
        } catch (Exception $e) {

            // This exception is used to abort initialisation of the objects but when
            // normal rendering is still required
            if ($e instanceof Exception_StopInit) {
                return;
            }

            // Handles output of the exception
            $this->caughtException($e);
        }
    }
    // }}}

    // {{{ Management of Global Methods 
    /**
     * Agile Toolkit objects allow method injection. This is quite similar
     * to technique used in JavaScript:
     *
     *     obj.test = function() { .. }
     *
     * All non-existant method calls on all Agile Toolkit objects will be
     * tried against local table of registered methods and then against 
     * global registered methods.
     *
     * addGlobalmethod allows you to register a globally-recognized for all
     * agile toolkit object. PHP is not particularly fast about executing
     * methods like that, but this technique can be used for adding
     * backward-compatibility or debugging, etc.
     *
     * @param string   $name     Name of the method
     * @param callable $callable Calls your function($object, $arg1, $arg2)
     *
     * @see AbstractObject::hasMethod()
     * @see AbstractObject::__call()
     *
     * @return void
     */
    function addGlobalMethod($name, $callable)
    {
        if ($this->hasMethod($name)) {
            throw $this->exception('Registering method twice')
                ->addMoreInfo('name', $name);
        }
        $this->addHook('global-method-'.$name, $callable);
    }
    /** 
     * Returns if a global method with such name was defined
     *
     * @param string $name Name of the method
     *
     * @return boolean if registered
     */
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
        if(strpos($page,'http://')===0 || strpos($page,'https://')===0) $url->setURL($page);
        else $url->setPage($page);
        return $url->setArguments($arguments);
    }
    /** @obsolete use url() */
    function getDestinationURL($page=null,$arguments=array()){ return $this->url($page,$arguments); }
    // }}}

    // {{{ Error handling
    /** Initialize logger or return existing one */
    function getLogger($class_name=undefined){
        if(is_null($this->logger)){
            $this->logger=$this->add($class_name===undefined?$this->logger_class:$class_name);
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
    function outputWarning($msg,$shift=0){
        if($this->hook('output-warning',array($msg,$shift)))return true;
        echo "warning: $msg\n";
    }
    /** @obsolete */
    function outputDebug($msg,$shift=0){
        if($this->hook('output-debug',array($msg,$shift)))return true;
        echo "debug: $msg\n";
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
        if(!$config)$config=array();
        if(!$this->config)$this->config=array();
        $this->config=array_merge($this->config,$config);
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

    // {{{ Helper / utility methods
    /**
     * Normalize field or identifier name. Can also be used in URL normalization.
     * This will replace all non alpha-numeric characters with separator.
     * Multiple separators in a row is replaced with one.
     * Separators in beginning and at the end of name are removed.
     * 
     * @param string $name      String to process
     * @param string $separator Character acting as separator
     * 
     * @return string           Normalized string
     */
    function normalizeName($name,$separator='_')
    {
        if(strlen($separator)==0) {
            return preg_replace('|[^a-z0-9]|i','',$name);
        }
        
        $s = $separator[0];
        $name = preg_replace('|[^a-z0-9\\'.$s.']|i',$s,$name);
        $name = trim($name,$s);
        $name = preg_replace('|\\'.$s.'{2,}|',$s,$name);
        
        return $name;
    }
    /**
     * Normalize class name.
     * This will add specified prefix to class name if it's not already added.
     * Class name can have namespaces and they are treated prefectly.
     * 
     * @param string|object $name   Name of class or object
     * @param string        $prefix Prefix for class name
     * 
     * @return string|object Full class name or received object
     */
    function normalizeClassName($name,$prefix)
    {
        if(!is_string($name)) return $name;

        $name = str_replace('/','\\',$name);
        if($prefix) {
            $class = ltrim(strrchr($name,'\\'),'\\')?:$name;
            $prefix = ucfirst($prefix);
            if (strpos($class,$prefix)!==0) {
                $name = preg_replace('|^(.*\\\)?(.*)$|', '\1'.$prefix.'_\2', $name);
            }
        }
        
        return $name;
    }
    // }}}
}
