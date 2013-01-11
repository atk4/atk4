<?php
/***********************************************************
  When $api->url() is called, this object is used to avoid
  double-encoding. Return URL when converting to string.

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class URL extends AbstractModel {

    // Page is a location of destination page. It have to be absolute and relative to project root
    protected $page=null;

    protected $arguments=array();

    protected $extension='.html';

    protected $absolute=false;  // if true then will return full URL (for external documents)

    public $base_url=null;


    function init(){
        parent::init();
        $this->setPage(null);
        $this->addStickyArguments();
        $this->extension=$this->api->getConfig('url_postfix',$this->extension);
    }
    /** [private] add arguments set as sticky through API */
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
    /** Call this if you want full URL, not relative */
    function useAbsoluteURL(){
        /*
           Produced URL will contain absolute, rather than relative address:
http://mysite:123/install/dir/my/page.html
         */
        $this->absolute=true;
        return $this;
    }
    /** [private] automatically called with 1st argument of api->url() */
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
        // '/admin/' = will not be converted

        $destination='';

        if(substr($page,-1)=='/'){
            return $this->setBaseURL(str_replace('//','/',$this->api->pm->base_path.$page));
        }
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

            if($component=='index' && $destination==''){
                $destination=$this->api->index_page;
                continue;
            }


            $destination=$destination?$destination.'/'.$component:$component;

        }
        if($destination==='')$destination=$this->api->index_page;

        $this->page=$destination;
        return $this;
    }
    /** Set additional arguments */
    function set($argument,$value=null){
        if(!is_array($argument))$argument=array($argument=>$value);
        return $this->setArguments($argument);
    }
    /** Get value of an argument */
    function get($argument){
        return $this->arguments[$argument];
    }

    /** Set arguments to specified array */
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
    function setURL($url){
        return $this->setBaseURL($url);
    }
    /** By default uses detected base_url, but you can use this to redefine */
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

        return $url;
    }
    function getExtension(){
        return $this->extension;
    }
    function getArguments($url=null){
        $tmp=array();
        foreach($this->arguments as $key=>$value){
            if($value===false)continue;
            $tmp[]=$key.'='.urlencode($value);
        }

        $arguments='';
        if($tmp)$arguments=(strpos($url,'?')!==false?'&':'?').join('&',$tmp);

        return $arguments;
    }
    function getURL(){
        if($this->base_url)return $this->base_url.$this->getArguments($this->base_url);


        $url=$this->getBaseURL();
        if($this->page && $this->page!='index'){
            // add prefix if defined in config
            $url.=$this->api->getConfig('url_prefix','');

            $url.=$this->page;
            $url.=$this->getExtension();
        }


        $url.=$this->getArguments($url);

        return $url;
    }
    /** Returns html-encoded URL */
    function getHTMLURL(){
        return htmlentities($this->getURL());
    }

}
