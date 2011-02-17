<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
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
    
    public $base_url=null;


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
		// '../page' = page besides our own (foo/bar -> foo/page)
		// 'index' = properly points to the index page defined in API

		$destination='';
		if(is_null($page))$page='.';
		$path=explode('/',$page);

		foreach($path as $component){
			if($component=='')continue;
			if($component=='.' && $destination==''){
				if($this->api->page=='index')continue;
				$destination=str_replace('_','/',$this->api->page);
				continue;
			}

			if($component=='..'){
				if(!$destination)$destination=str_replace('_','/',$this->api->page);
				$tmp=explode('/',$destination);
				array_pop($tmp);
				$destination=join('/',$tmp);
				continue;
			}

			if($component=='index' && $destination=''){
				$destination=$this->api->index_page;
				continue;
			}


			$destination=$destination?$destination.'/'.$component:$component;

		}
		if($destination==='')$destination=$this->api->index_page;

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
    function setBaseURL($base){
        $this->base_url=$base;
        return $this;
    }
	function getBaseURL(){
        // Oherwise - calculate from detected values
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
        // baseURL can be set for sites with other URL
        if($this->base_url)return $this->base_url.$this->getArguments();

		$url=$this->getBaseURL();
		$url.=$this->page;
		$url.=$this->getExtension();
        $url.=$this->getArguments();


		return $url;
	}
    function getArguments(){
		$tmp=array();
		foreach($this->arguments as $key=>$value){
			if($value===false)continue;
			$tmp[]=$key.'='.urlencode($value);
		}

		if($tmp)return '?'.join('&',$tmp);
        return '';
    }
	function getHTMLURL(){
		return htmlentities($this->getURL());
	}

}
