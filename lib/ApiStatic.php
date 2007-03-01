<?php
/**
 * Generic class to work with static sites.
 * Description coming soon
 * 
 */
class ApiStatic extends ApiWeb{
    var $dontrender=false;
	public $depth = 0;
	public $generated = 0;
    var $tagmatch=array(
            'test'=>'wrap',

            // HTML header footer
            'header'=>'wrap',
            'footer'=>'wrap',

            // Text headers
			'header1'=>'header1',
			'headerTopPage' => 'header1',
            'header2'=>'wrap',
            'header3'=>'wrap',
			'header4'=>'wrap',

            //
            'textandimage'=>'simple',
            'columntable'=>'list2',
			'3columntable'=>'list',
			'smallHeading'=>'simple',
            'tabNavigation'=>'simple',

            // Spaces
            'space'=>'simple',
			'subdir'=>'subdir',
            'prHeader'=>'simple',
			'listWithDates'=>'list',
            'listWithDottedLine'=>'list',

            // System elements
            'title'=>'delete',
            'main_content'=>'',
            'menuitem'=>'delete'
            );


    var $components=null;    // component library template
    var $general=null;      // general page template
    var $current_menuitem=null;		// current menu item

	function __construct($d=0, $g=''){
		$this->depth = $d;
		$this->generated=$g;
		parent::__construct();
	}

    function init(){
        parent::init();
        
        $this->logger=$this->add('Logger');

#        $this->base='<base href="'.$this->getConfig('base').'">';
        $this->base_path=$this->getConfig('base_path');

        // Here we get the name of the page and load related file as a template
        $this->page=$_SERVER['REDIRECT_URL'];
        if(!$this->page)$this->page=$_SERVER['REDIRECT_SCRIPT_URL'];
        if(!strpos($this->page,'.html'))$this->page.='/index.html';
        $this->page=str_replace('.html','',$this->page);
        $this->current_menuitem=$this->page;
        
        $this->template=$this->add('SMlite');
        $f=join('',file($_SERVER['DOCUMENT_ROOT'].$this->page.'.html'));
        $this->template->loadTemplateFromString($f);

        // calculating menu related path
        $path=$this->getPath($this->page,$this->getConfig('menu'));
        if($path==''){
        	// this page was not defined in menu
        	// getting the path by the tag 'menuitem' of this page
        	$this->current_menuitem=$this->template->get('menuitem');
        	$path=$this->getPath($this->getConfig('base_path').$this->current_menuitem,$this->getConfig('menu'));
        }
        $this->path=split(' ',$path);
        
        // Template we just loaded will have some useful information we will need
        // such as page title
        if($this->template->is_set('title')){
            $this->title=trim($this->template->get('title'));
            $this->template->del('title');
        }else $this->title="!!Title must be defined!!";

		// should be an obsolete property
		/*
        if($this->template->is_set('parent')){
            $this->parent=trim($this->template->get('parent'));
            $this->template->del('parent');
        }else $this->parent='';
		*/
        // Also we will need a component library
        $this->components=$this->add('SMlite')->loadTemplate('components');

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
    function processTemplate($object){
        foreach(array_keys($object->template->template) as $tag){
            list($class,$junk)=split('#',$tag);
            if(is_numeric($class))continue;
            if(isset($this->tagmatch[$class]))$class=$this->tagmatch[$class];
            if(!$class){
                continue;
            }
            if($class[0]=='_')continue;
            $object->template->trySet('_title',$this->title);
			
			#We also try setting the subdirectory level here as well.
			$subdir_string = '';
			$count = 0;
			if($object->depth == 0) 
			{	
        		$uri = $_SERVER['REQUEST_URI'];
        		$components = preg_split('/\//', $uri);
        		$count = count($components);
        		// decreasing by the number of components in the path, if site is not at the top
       			$count = $count - count(preg_split('/\//', $this->getConfig('base_path')));
       			$subdir_string = '';
			}
			else 
			{
				$count = $object->depth;
			}
		    for($i = 0; $i < $count; $i++) 
			{
				$subdir_string .= '../';
			}
			$depth = $object->depth;
			$generated = $object->generated;
			$object->subdir = "$subdir_string";;
			$object->template->trySet('_subdir', "$subdir_string");
            $object->add('sw_'.$class,$tag,$tag,$tag);
        }
    }
    function render(){
        if(!$this->dontrender)return parent::render();
    }

}