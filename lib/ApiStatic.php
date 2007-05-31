<?php
/**
 * Generic class to work with static sites.
 * Description coming soon
 * 
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


            'title'=>'grab',
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
        else $this->info[$elements]=$this->getConfig($elements,'');
    }
    function loadConfig(){
        //$this->importFromConfig(array());
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
        $this->page=$_SERVER['REDIRECT_URL'];
        if(!$this->page)$this->page=$_SERVER['REDIRECT_SCRIPT_URL'];
        if(!strpos($this->page,'.html'))$this->page.='/index.html';
        $this->page=str_replace('.html','',$this->page);


        /*
         * By Content here we call a template, which defines the overal look of the page. It
         * have the same name as appears in the URL. This page can be completely static without
         * any tags, or you can slowly add tags into. You can also use the layout tag, which will
         * allow you to get rid of HTML completely.
         */
        $this->debug('Initializing content template');
        $this->template=$this->add('SMlite');
        $f=join('',file($_SERVER['DOCUMENT_ROOT'].$this->page.'.html'));
        $this->template->loadTemplateFromString($f);

        /*
         * --> menu specific things go into menu component
        $path=$this->getPath($this->page,$this->getConfig('menu'));
        if($path==''){
        	// this page was not defined in menu
        	// getting the path by the tag 'menuitem' of this page
            $this->debug('This page is not defined in menu');
        	$this->current_menuitem=$this->template->get('menuitem');
        	$path=$this->getPath($this->getConfig('base_path').$this->current_menuitem,$this->getConfig('menu'));
        }else{
            $this->debug("This page's path is $path");
        }
        $this->path=split(' ',$path);
        */
        
        // Template we just loaded will have some useful information we will need
        // such as page title
        /*
        if($this->template->is_set('title')){
            $this->title=trim($this->template->get('title'));
            $this->template->del('title');
        }else $this->title="!!Title must be defined!!";
         *--> this is going to bet moved into sw_title
        */

		// should be an obsolete property
		/*
        if($this->template->is_set('parent')){
            $this->parent=trim($this->template->get('parent'));
            $this->template->del('parent');
        }else $this->parent='';
		*/


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


        $this->processTemplate($this);


        /*
        // There are two ways page could be defined. It could actually be a
        // valid HTML file with some content we are going to replace OR
        // it might be just a list of tags surrounded with <!main_content!>
        // tag. 
        // In 1st case we will render this template and output
        // In 2nd case we will insert result into "global" template
        if($this->template->is_set('main_content')){

            // Ok, we don't care for anything outside the main_content region
            $this->template=$this->template->cloneRegion('main_content');

            // This will insert components into the template
            $this->processTemplate($this);

            // This will render our template
            $this->dontrender=true;
            $this->downCall('render');
            $this->dontrender=false;
            unset($this->elements);

            // Now we are preparing general page - inserting result inside it
            $this->general=$this->add('SMlite')->loadTemplate('general');
            $this->general->set('main_content',$this->template->render());

            // General template may contain some components too, so we need
            // to process it again
            $this->template=$this->general;
            $this->processTemplate($this);
        }else{
            // Process our page and output it completely
            $this->processTemplate($this);
        }
        */

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
    function processTemplate($parent){
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
            if($tag[0]=='_')continue;
            list($class,$junk)=split('#',$tag);
            if(is_numeric($class))continue;     // numeric ones are just a text, not really a tag
            $original_class=$class;

            // If tag is present inside tagmatch class, it can redefine the class name
            // and some additional options
            if(isset($this->tagmatch[$class]))$class=$this->tagmatch[$class];

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
            if($class==$original_class && !class_exists($class,false)){
                // Perhaps we could load it
                if(!loadClass($class)){
                    // It's no use. Class cannot be found, so we will use "sw_wrap" as default class.
                    $this->debug("Couldn't find separate class definition for tag $tag, so using 'sw_wrap'");
                    $class='sw_wrap';
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
            $component->initializeTemplate($tag,$tag);


            // Now set some arguments and do initialization
            $component->args=$rest;
            $component->init();

            if($component instanceof sw_component)$component->processRecursively();
            $this->debug("Component $tag initialization completed");
        }
    }
}
