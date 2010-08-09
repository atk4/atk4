<?
/*
 * This class implements a object to be used by getDestitanionURL(). Instead of returning string,
 * it will return this object. Not only object can be used AS string, but it will also be handled
 * properly if you will try supply it to getDestinationURL several timess
 */
class URL extends AbstractModel {

	// Page is a location of destination page. It have to be absolute and relative to project root
	protected $page=null;

	protected $arguments=array();

	protected $extension='.html';

	protected $absolute=false;	// if true then will return full URL (for external documents)


	function init(){
		parent::init();
		$this->setPage(null);

		// add sticky arguments
		$this->addStickyArguments();

		$this->extension=$this->api->getConfig('url_postfix',$this->extension);
	}
	function addStickyArguments(){
		$sticky=$this->api->getStickyArguments();
		$args=array();

        if($sticky && is_array($sticky)){
            foreach($sticky as $key=>$val){
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
		$this->setArguments($args);
	}
	function useAbsoluteURL(){
		/*
		   Produced URL will contain absolute, rather than relative address:
			http://mysite:123/install/dir/my/page.html
		*/
		$this->absolute=true;
		return $this;
	}
	function setPage($page=null){
		// The following argument formats are supported:
		//
		// null = set current page
		// '.' = set current page
		// 'page' = sets webroot/page.html
		// './page' = set page relatively to current page
		// '..' = parent page
		// '../page' = also available
		// 'index' = properly points to the index page defined in API

		$destination='';
		if(is_null($page))$page='.';
		$path=explode('/',$page);

		foreach($path as $component){
			if($component=='')continue;
			if($component=='.' && $destination==''){
				$destination=str_replace('_','/',$this->api->page);
				continue;
			}

			if($component=='..'){
				$tmp=explode('/',$destination);
				array_shift($tmp);
				$destination=join('/',$tmp);
				continue;
			}

			if($component=='index' && $destination=''){
				$destination=$api->index_page;
				continue;
			}


			$destination=$destination?$destination.'/'.$component:$component;

		}

		$this->page=$destination;
		return $this;
	}
	function setArguments($arguments=array()){
		// add additional arguments
		if(is_null($arguments))$arguments=array();
		if(!is_array($arguments)){
			throw new BaseException('Arguments must be always an array');
		}
		$this->arguments=$args=array_merge($this->arguments,$arguments);
		foreach($args as $arg=>$val){
			if(is_null($val))unset($this->arguments[$arg]);
		}
		return $this;
	}
	function __toString(){
		return $this->getURL();
	}
	function getBaseURL(){
		$url='';

		// add absolute if necessary
		if($this->absolute)$url.=$this->api->pm->base_url;

		// add base path
		$url.=$this->api->pm->base_path;

		// add prefix if defined in config
		$url.=$this->api->getConfig('url_prefix','');

		return $url;
	}
	function getExtension(){
		return $this->extension;
	}
	function getURL(){
		$url=$this->getBaseURL();
		$url.=$this->page;
		$url.=$this->getExtension();

		$tmp=array();
		foreach($this->arguments as $key=>$value){
			if($value===false)continue;
			$tmp[]=$key.'='.urlencode($value);
		}

		if($tmp)$url.='?'.join('&',$tmp);

		return $url;
	}
	function getHTMLURL(){
		return htmlentities($this->getURL());
	}

}
