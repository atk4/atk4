<?php
class ApiCLI extends AbstractView {
    public $db=null;
    protected $config = null;     // use getConfig method to access this variable
    public $logger=null;	// TODO: protect this
    protected $base_dir='';

    function __construct($realm=null){
        $this->owner = null;
        $this->name  = $realm;
        $this->api   = $this;

        set_error_handler("error_handler");

        try {
            $this->init();

            $this->hook('api-defaults');
            $this->hook('post-init');

        }catch(Exception $e){
            $this->caughtException($e);
        }
    }
    function init(){
    	parent::init();
    	$this->addHook('api-defaults',array($this,'initDefaults'));
    }
    function initDefaults(){
    	if(!defined('DTP'))define('DTP','');
    }
	function getBaseURL(){
		return (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['SERVER_NAME'].
			(substr($root=$this->api->getConfig('url_root','/'),0,1)=='/'?'':'/').$root;
	}
	function getBaseDir(){
		return $this->base_dir;
	}
    function getDestinationURL($page=null,$args=array()){
        // If first argument is null, stay on the same page
        if(is_null($page)||$page=='')$page=$this->page;

        // Check sticky arguments. If argument value is true,
        // GET is checked for actual value.
        if(isset($this->sticky_get_arguments)){
            foreach($this->sticky_get_arguments as $key=>$val){
                if($val===true){
                    if(isset($_GET[$key])){
                        $val=$_GET[$key];
                    }else{
                        continue;
                    }
                }
                if(!isset($args[$key])){
                    $args[$key]=$val;
                }
            }
        }
        $tmp=array();
        foreach($args as $arg=>$val){
            if(!isset($val) || $val===false)continue;
            if(is_array($val)||is_object($val))$val=serialize($val);
            $tmp[]="$arg=".urlencode($val);
        }
        return
            $this->getConfig('url_prefix','').
            $page.
            $this->getConfig('url_postfix','').
            ($tmp?(stripos($this->getConfig('url_prefix',''),'?')===false?'?':'&').join('&',$tmp):'');
    }

    function getLogger($class_name='Logger'){
    	if(is_null($this->logger)){
    		$this->logger=$this->add($class_name);
    	}
    	return $this->logger;
    }
    function caughtException($e){
        $this->hook('caught-exception',array($e));
        echo get_class($e),": ".$e->getMessage();
        exit;
    }
    function outputFatal($msg,$shift){
        $this->hook('output-fatal',array($msg,$shift+1));
        echo "Fatal: $msg\n";exit;
    }
    function outputWarning($msg,$shift=0){
        if($this->hook('output-warning',array($msg,$shift)))return true;
        echo "warning: $msg\n";
    }
    function outputDebug($msg,$shift=0){
        if($this->hook('output-debug',array($msg,$shift)))return true;
        echo "debug: $msg\n";
    }
    function outputInfo($msg,$shift=0){
        if($this->hook('output-info',array($msg,$shift)))return true;
        echo "info: $msg\n";
    }
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
    function configExceptionOrDefault($default,$exceptiontext){
        if($default!='_config_get_false')return $default;
        throw new BaseException($exceptiontext);
    }
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
            $this->readConfig();
        }
        $parts = split('/',$path);
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
    function dbConnect($dsn=null){
    	if (is_null($dsn)) $dsn=$this->getConfig('dsn');
        $result=$this->db=DBlite::connect($dsn);
        if(is_string($result))throw new DBlite_Exception($result,"Please edit 'config.php' file, where you can set your database connection properties",2);
        $this->db->owner=$this;
        return $this;
    }
    function tryConnect($dsn){
        $this->db=DBlite::tryConnect($dsn);
    }
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
        if(isset($this->config['table_prefix'])){
            if(!defined('DTP'))define('DTP',$this->config['table_prefix']);
        }

        $tz = $this->getConfig('timezone',null);
        if(!is_null($tz) && function_exists('date_default_timezone_set')){
            // with seting default timezone
            date_default_timezone_set($tz);
        }


    }
    function setConfig($config=array()){
    	$this->config=safe_array_merge($this->config,$config);
    }
}
?>
