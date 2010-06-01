<?php
class ApiCLI extends AbstractView {
	public $db=null;
	protected $config = null;     // use getConfig method to access this variable
	public $logger=null;	// TODO: protect this
	protected $pathfinder_class='PathFinder';


	function __construct($realm=null){
		$this->owner = null;
		$this->name  = $realm;
		$this->api   = $this;

		set_error_handler("error_handler");

		try {
			$this->add($this->pathfinder_class);
			$this->init();

			$this->hook('api-defaults');
			$this->hook('post-init');

		}catch(Exception $e){

			// This exception is used to abort initialisation of the objects but when
			// normal rendering is still required
			if($e instanceof Exception_StopInit)return;

			$this->caughtException($e);
		}
	}
	function locate($type,$filename='',$return='relative'){
		return $this->pathfinder->locate($type,$filename,$return);
	}
	function locateURL($type,$filename=''){
		return $this->pathfinder->locate($type,$filename,'url');
	}
	function locatePath($type,$filename=''){
		return $this->pathfinder->locate($type,$filename,'path');
	}
	function addLocation($location,$contents){
		return $this->pathfinder->addLocation($location,$contents);
	}
	function init(){
		parent::init();
		$this->addHook('api-defaults',array($this,'initDefaults'));
	}
	function initDefaults(){
		if(!defined('DTP'))define('DTP','');
	}
	function getBaseURL(){
		return $this->pm->base_path;
	}
	function getDestinationURL($page=null,$arguments=array(),$full='depricated'){
		if($full!='depricated')throw new BaseException('Using 3rd argument for getDestinationURL is depricated');
		if(is_object($page) && $page instanceof URL){
			// we receive URL
			return $page->setArguments($arguments);
		}
		$url=$this->add('URL','url_'.$this->url_object_count++);
		return $url->setPage($page)->setArguments($arguments);
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
	function dbConnect($dsn=null){
		if (is_null($dsn)) $dsn=$this->getConfig('dsn');
		$result=$this->db=DBlite::connect($dsn);
		if(is_string($result))throw new DBlite_Exception($result,"Please edit 'config.php' file, where you can set your database connection properties",2);
		$this->db->owner=$this;
		$this->db->api=$this;
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
