<?
class PathFinder extends AbstractController {
	/*
	 PathFinder will help you to maintain consistent structure of files. This
	 controller is used by many other parts of Agile Toolkit

	 PathFinder concerns itself only with relative paths. It relies on PageManager
	 ($api->pm) to convert relative paths into absolute.
	*/

	public $base_location=null;
	// Object referencing base location. You might want to add more content here

	public $atk_location=null;
	// Referencing to location of a shared library

	function init(){
		parent::init();
		$this->api->pathfinder=$this;
		$GLOBALS['atk_pathfinder']=$this;	// used by autoload


		// getting ready for atk
		//$this->api->proxyFunctions($this,array('addLocation','locate'));

		$this->addDefaultLocations();
	}



	function addDefaultLocations(){
		// Typically base directory is good for includes,
		// but amodules3/ can also contain some data

		// Primary search point is the webroot directory. We are defining
		// those so you don't have to
        $base_directory=dirname(@$_SERVER['SCRIPT_FILENAME']);

		// Compatibility with command-line
		if(!$base_directory)$base_directory=realpath($GLOBALS['argv'][0]);

		$this->base_location=$this->addLocation('/',array(
			'php'=>'lib',
			'page'=>'page',
			'template'=>'templates/'.$this->api->skin,
			'mail'=>'templates/mail',
			'js'=>'templates/js',
			'banners'=>'banners',
			'logs'=>'logs',
			'dbupdates'=>'docs/dbupdates',
			))->setBasePath($base_directory)
			;

		// Files not found in webroot - will be looked for in library dir
		// We are assuming that we are located as atk4/lib/PathFinder.php
		$atk_directory=dirname(dirname(__FILE__));
		$atk_url=basename($atk_directory);

		$this->atk_location=$this->addLocation('amodules3',array(
			'docs'=>'',	// files like README, COPYING etc
			'images'=>'img',
			'php'=>'lib',
			'js'=>'templates/js',
			'css'=>array('templates/js','templates/'.$this->api->skin.'/css','templates/shared/css'),
			'template'=>array('templates/'.$this->api->skin,'templates'=>'templates/shared')
			))
		->setBasePath(dirname(dirname(__FILE__)))
		->setBaseURL($this->api->getConfig('atk/base_path','/amodules3/'))
		;
	}

	function addLocation($path,$contents=array()){
		$location=$this
			->add('PathFinder_Location',$path)
			->defineContents($contents)
			;
		return $location;
	}

	function locate($type,$filename='',$return='relative'){
		/*
		 Search for filename inside multiple locations, which contain
		 resources of $type

		 if filename is not defined, the location of the first available resource is defined
		*/

		$attempted_locations=array();
		foreach($this->elements as $location){
			if(!($location instanceof PathFinder_Location))continue;

			$path=$location->locate($type,$filename,$return);

			if(is_string($path)){
				// file found!
				return $path;
			}elseif(is_array($path)){
				$attempted_locations=array_merge($attempted_locations,$path);
			}
		}

		throw new PathFinder_Exception($type,$filename,$attempted_locations);
	}
}

class PathFinder_Exception extends BaseException {
	function __construct($type,$filename,$attempted_locations,$message=null){
		parent::__construct("Unable to include $filename".($message?':'.$message:''));
		$this->addMoreInfo('type',$type);
		$this->addMoreInfo('attempted_locations',$attempted_locations);
	}
}

class PathFinder_Location extends AbstractModel {
	/*
	  Represents a location, which contains number of sub-locations. Each
	  of which may contain certain type of data
	*/


	public $parent_location=null;

	public $contents=array();
	// contains list of 'type'=>'subdir' which lists all the
	// resources which can be found in this directory


	public $relative_path=null;
							// Path to relative file within this resource

	public $base_url=null;
	public $base_path=null;


	function init(){
		parent::init();

		$this->relative_path=$this->short_name;

		if($this->short_name[0]=='/' || $this->short_name[1]==':'){
			// Absolute path. Independent from base_location

		}else{
			$this->setParent($this->owner->base_location);
		}
	}

	function setParent($parent){
		$this->parent_location=$parent;
		return $this;
	}

	function __toString(){
		// this is our path
		$s=(isset($this->parent_location)?
			((string)$this->parent_location):'');
		if($s && substr($s,-1)!='/' && $this->relative_path)$s.='/';
		$s.=$this->relative_path;
		return $s;
	}

	function getURL($file_path=null){
		// Returns how this location or file can be accessed through web
		// base url + relative path + file_path

		$url='';
		if($this->base_url)$url=$this->base_url;else
		if($this->parent_location){
			$url=$this->parent_location->getURL();
			if(substr($url,-1)!='/')$url.='/';
			$url.=$this->relative_path;
		}else
		throw new BaseException('Unable to determine URL');

		if($file_path){
			if(substr($url,-1)!='/')$url.='/';
			$url.=$file_path;
		}
		return $url;
	}

	function getPath($file_path=null){
		// Returns how this location or file can be accessed through filesystem

		$path='';
		if($this->base_path)$path=$this->base_path;else
		if($this->parent_location){
			$path=$this->parent_location->getPath();
			if(substr($path,-1)!='/')$path.='/';
			$path.=$this->relative_path;
		}else
		throw new BaseException('Unable to determine Path for '.$this.', parent='.$this->parent_location);

		if($file_path){
			if(substr($path,-1)!='/')$path.='/';
			$path.=$file_path;
		}
		return $path;
	}

	function setBaseURL($url){
		/*
		 something like /my/app
		*/
		$this->base_url=$url;
		return $this;
	}
	function setBasePath($path){
		/*
		 something like /home/web/public_html
		*/
		$this->base_path=$path;
		return $this;
	}
	function defineContents($contents){
		if($contents==='all'){
			$contents=array('all'=>'all');
		}
		if(is_string($contents)){
			$contents=array($contents=>'.');
		}
		$this->contents=array_merge_recursive($this->contents,$contents);
		return $this;
	}

	function locate($type,$filename,$return='relative'){
		// Locates the file and if found - returns location,
		// otherwise returns array of attempted locations

		// specify empty filename to find location

		$attempted_locations=array();
		$locations=array();
		$location=null;

		// first - look if type is explicitly defined in
		if(isset($this->contents[$type])){
			if(is_array($this->contents[$type])){
				$locations=$this->contents[$type];
			}else{
				$locations=array($this->contents[$type]);
			}
		// next - look if locations claims to have all resource types
		}elseif(isset($this->contents['all'])){
			$locations=array($type);
			echo (string)$this;
		}

		foreach($locations as $path){
			$f=$this->getPath($pathfile=$path.'/'.$filename);
			if(file_exists($f)){
				if(!is_readable($f)){
					throw new PathFinder_Exception($type,$filename,$f,'File found but it is not readable');
				}

				if($return=='relative')return $pathfile;
				if($return=='url')return $this->getURL($pathfile);
				if($return=='path')return $f;

				throw new BaseException('Wrong return type for locate()');

			}else $attempted_locations[]=$f;
		}

		return $attempted_locations;
	}
}
