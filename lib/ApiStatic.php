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
/**
 * Generic class to work with static sites.
 * Description coming soon
 *
 * TODO: importFromConfig will not work with sub-pages (located in subdirs)
 */
class ApiStatic extends ApiWeb{
	var $tagmatch=array(
			/*
			 * Tagmatch array describes which component is going to be inserted inside the template
			 * instead of the tag. For example if you have the "header" tag it is going to be replaced
			 * with the component.
			 *
			 * 'header'=>'wrap',
			 *
			 * Header wouldn't be defined inside the standard component file (components.html), so we
			 * must specify which template it's defined in
			 *
			 * 'header'=>'wrap(head_and_tail)',
			 *
			 * There are several ways to further extend the syntax of your tagmatch elements, please see
			 * attached documentation.
			 *
			 *
			 * In default configuration you would need only 2 files. "layout" and "components". Layout defines
			 * the outside part of your template, while components define inside elements.
			 *
			 */

			'layout'=>'wrap(layout)',               // layout is defined in separate template file: templates/layout.html
			'lorem_ipsum'=>'simple(lorem_ipsum)',   // This is here for your testing
			'comment'=>'delete',                    // Do not render comments


			'recursive_test'=>'recursive',          // This is to test a recurnsive component


			'loc'=>'collect',

			);


	// Standard template libraries
	var $components=null;                           // component library template

	/*
	 * Apart from the components your page may have a simplier tags such as <?_title?>. Those are
	 * for your convinience and will be replaced with elements from this array.
	 *
	 * Insertion is done during the rendering phase, so you can change $api->info during the
	 * initialization.
	 */
	var $info=array(
			'_title'=>'!!Title is not defined!!',
			);



	function importFromConfig($elements){
		if(is_array($elements))foreach($elements as $el)$this->importFromConfig($el);
		else{
			if(!$this->info[$elements]=$this->getConfig(basename($this->page).'/'.$elements,null))
				$this->info[$elements]=$this->getConfig($elements,'');
		}
	}
	function loadConfig(){
		//$this->importFromConfig(array());
	}
	function calculatePageName(){
		parent::calculatePageName();
		//$this->page=str_replace('.html','',$this->page_base);

	}
	function init(){
		parent::init();

		/*
		 * Let's start with the regular routine.
		 */
		$this->logger=$this->add('Logger');


		/*
		 * Some variables comes from configuration file.
		 */
		$this->loadConfig();

		/*
		 * Here we determine, which page was loaded.
		 */
//        $this->page=$_SERVER['REDIRECT_URL'];


		/*
		 * By Content here we call a template, which defines the overal look of the page. It
		 * have the same name as appears in the URL. This page can be completely static without
		 * any tags, or you can slowly add tags into. You can also use the layout tag, which will
		 * allow you to get rid of HTML completely.
		 */
		$this->debug('Initializing content template');
		$this->template=$this->add('SMlite');
		if (!file_exists($this->page.".html")){
			throw new BaseException("Requested page does not exist!");
			return;
		}
		$f=join('',file($this->page.'.html'));

		// temporary: //what is this?
		// page.html?bypass=1 <-- would just output html without replacing tags
		// but problem is <-- get arguments are destroyed by htaccess
		// yes this is serious problem for .htacces.. it cannot work with ?.*
		// no way can it pass :( I adjusted forms so that action would be
		// e.g. action.html&param1=... and then it works
		// so
		// page.html&bypass=1 should do the trick
		// ok
		if($_GET['bypass']){
			echo $f;
			exit;
		}
		$this->template->loadTemplateFromString($f);


		/*
		 * Components library is handy and will be used over and over during a normal run.
		 * We preload the template and elements will get cloned from there as they are
		 * being used. If template for component is specified manually - that would
		 * load additional file
		 */

		$this->debug('Now initializing components');
		$this->components=$this->add('SMlite')->loadTemplate('components');

		/*
		 * From here our way to do things is rather simple. We will go through all
		 * the tags on the current level and try to replace them with the components.
		 *
		 * Tags starting with underscope are exceptions and will be treated as global
		 * variables.
		 */


		$this->addHook('pre-exec',array($this,'processRootTemplate'));


	}
	function processRootTemplate(){
		$this->processTemplate($this);
	}
	function getPath($link,$menu){
		// returns the space separated path of the link given related to the menu
		// result is similar to getConfig path

		foreach($menu as $id=>$data){
			if($link==$this->api->getConfig('base_path').$id){
				return $id;
			}
			if(isset($data['submenu'])&&is_array($data['submenu'])){
				// going into submenu
				$path=$this->getPath($link,$data['submenu']);
				if($path!='')return $id.' '.$path;
			}
		}
		return '';
	}
	function processTemplate($parent,$template=null){
		/*
		 * Process template will walk through the template of an object, replace tags with components
		 * according to the rules specified in $api->tagmatch
		 */

		$this->debug("Processing template for ".$parent->__toString());

		foreach(array_keys($parent->template->template) as $tag){
			/*
			 * Tags are of 2 types. Names without # can point to several elements
			 * but tags with # always point to single element. We may encounter
			 * multilple tags of the same name in the template and we don't want
			 * those to get mixed up, so we are only interested in tags with #
			 */
			list($class,$junk)=split('#',$tag);
			if($class[0]=='_'){
				// variable
				$class=substr($class,1);
				if(isset($this->info[$class]))$parent->template->set($tag,$this->info[$class]);
				continue;
			}
			if(is_numeric($class))continue;     // numeric ones are just a text, not really a tag
			$original_class=$class;

			// If tag is present inside tagmatch class, it can redefine the class name
			// and some additional options
			if(isset($this->tagmatch[$class])){
				$this->debug("*tagmach present for $class = ".$this->tagmatch[$class]);
				$class=$this->tagmatch[$class];
			}else
				$this->debug("*tagmach NOT present for $class = ".$this->tagmatch[$class]);
			if(!$class){
				continue;   // tagmatch says we should ignore this tag
			}

			/*
			 * Class may contain something like 'myclass(foo,bar,baz)'. We need to extract arguments
			 */
			list($class,$rest)=explode('(',$class);
			$class=trim($class);
			if($rest){
				if(substr($rest,-1,1)==')')$rest=substr($rest,1,-1);
				$rest=explode(',',$rest);
			}else $rest=null;


			// Before we start with class initialization let's see if it can be initialized at all.
			// We will only do this if the calss was not found in the tagmatch array. If it WAS defined
			// we assume those people know what they are doing and let them have their error.

			$class_exist_status = loadClass($class);
			if($class==$original_class && !$class_exist_status){
				// Perhaps we could load it
				if(!loadClass('sw_'.$class)){
					// It's no use. Class cannot be found, so we will use "sw_wrap" as default class.
					$this->debug("Couldn't find separate class definition for tag sw_$class, so using 'sw_wrap'");
					$class='wrap';
				}
			}

			// We need to pass some arguments to the class when initializing. Our add('classname') method is
			// not passing any arguments before initialization. Setting global variables or relying on owner's
			// property seems like a wrong way to do. So instead we manually initialize object and insert it
			// into parent. add() when used with object is really lazy.
			$this->debug("Initializing component '$class' for '$tag'");
			$class_name='sw_'.$class;
			$component=new $class_name;
			$component->short_name='sw_'.$tag;
			$component->name=$parent->name.'_'.$component->short_name;
			$component->api=$parent->api;
			$parent->add($component);
			if($component instanceof sw_component){
				/* try to force using piece from components */
				list($plain_tag, $junk) = split("#", $tag);
				/*try this first if file exists under templates, load it.
				* remember that underscore is folder separator */
				$tag_file = preg_replace("/_/", "/", $plain_tag);
				if (file_exists($file = "templates/" . $plain_tag . ".html")){
					$component->initializeTemplate($tag, array($plain_tag, $plain_tag));
				} if (file_exists($file = "templates/" . $tag_file . ".html")){
					$component->initializeTemplate($tag, array($tag_file, $plain_tag));
				} else if ($this->components->get($tag)){
					$component->initializeTemplate($tag, array("components", $tag));
				} else if ($this->components->get($plain_tag)){
					$component->initializeTemplate($tag, array("components", $plain_tag));
				} else {
					$component->initializeTemplate($tag,$tag);
				}
				$component->init();
				$component->processRecursively();
			}else{
				/*
				 * In the case here - we are trying to insert a
				 * regular View into our template, which is most probably
				 * a dynamic element. It will require a template to render.
				 */
				if($this->api->components->is_set($class)){
					$template=$this->api->components->cloneRegion($class);
				}else $template=null;

				/*
				 * Additionally we will pass argument "logic" to it, with content
				 * of the tag from your page. This can be used to write some rules
				 * or checks on top of standard component.
				 */
				$component->logic=$parent->template->cloneRegion($tag);

				/*
				 * Good
				 */
				$component->initializeTemplate($tag,$template);
				$component->init();
			}

			$this->debug("Component $tag initialization completed");
		}
	}
	function getDestinationURL($page=null,$args=array()){
		$tmp=array();
		if(!$page)$page=$this->api->page;
		foreach($args as $arg=>$val){
			if(!isset($val) || $val===false)continue;
			if(is_array($val)||is_object($val))$val=serialize($val);
			$tmp[]="$arg=".urlencode($val);
		}
		return
			$this->getConfig('url_prefix','').
			$page.
			$this->getConfig('url_postfix','').
			($tmp?'?'.join('&',$tmp):'');
		/*if($this->getConfig('url_prefix',false)){
			return $this->getConfig('url_prefix','').$page.($tmp?"&".join('&',$tmp):'');
		}else return $page.'.html'.($tmp?"?".join('&',$tmp):'');
		*/
	}
}
