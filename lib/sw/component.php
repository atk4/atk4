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
 * Base component for rendering static web objects
 *
 */
class sw_component extends View {
	var $wrapping=null;  // This is a template which is going to be used as a wrapping

	var $content='';    // same thing but rendered

	var $data=array();  // this contains structured we grabed from content

	var $tmpl=array();  // this contains collected template clones

	function processRecursively(){
		$this->api->processTemplate($this);
	}
	function output($data){
		/*
		 * We do not put anything inside parent yet. We might need to wrap things up
		 */
		$this->content.=$data;
	}
	function wrapUp(){
		/*
		 * If you have called "surroundBy" earlier, that means - the whole object should be put inside
		 * a "wrapping". The actual template will be rendered and placed as a 'content' inside wrapping
		 */

		$this->wrapping->trySet('content',$this->content);
		$this->wrapping->set($this->api->info);
		// $this->template->set('content',$logic->render());
		$this->content=$this->wrapping->render();
	}
	function render(){
		$debug=$this->api->debug||$this->debug;
		if($debug)$this->output('<div style="border: 1px dashed red">');
		parent::render();

		if($this->wrapping){
			if ($debug){
				$this->output('</div');
				parent::output('<div style="border: 1px dashed blue">');
			}
			$this->wrapUp();
		}
		parent::output($this->content);
		if($debug)parent::output('</div>');
	}
	function grab($tag,$default=null,$template=null){
		if(!$template)$template=$this->template;
		if($template->is_set($tag)){
			list($class,$junk)=split('#',$tag);
			if(isset($this->tagmatch[$class]))$class=$this->tagmatch[$class];
		   	if(!class_exists('sw_'.$class,false)){
				$this->data[$tag]=$template->get($tag);
				$template->del($tag);
		   	}
		}else $this->data[$tag]=$default;
	}
	function cloneRegion($tag){
		$this->tmpl[$tag]=$this->wrapping->cloneRegion($tag);
		$this->wrapping->del($tag);
		return $this->tmpl[$tag];
	}
	function surroundBy($template=null){
		/*
		 * Normally when component is created, it inherits it's template from the parent. However usually it's
		 * not what we need. We want this template to be used as a reference, but the real template should be
		 * loaded from a component library or an external file.
		 *
		 * This function taks region defined by a $tag and replaces it with $template. If $template is not
		 * specified, then tag name will be used to determine name of a tag cloned from component library
		 *
		 *
		 * After insertion is done, the previous template is rendered and is inserted inside <content>
		 * tag, which may be defined inside component.
		 *
		 * To ilustrate, let's take this example:
		 *
		 * components:
		 *
		 *  <?title?> <h1> <?content?>sample content<?/?> </h1> <?/title?>
		 *
		 *
		 * content template:
		 *
		 *  <?title?> Hello World <?/title?>
		 *
		 * When component is initalized, it inherits template containing "hello world" by default. However
		 * if surroundBy is called on top-tag (title), this function render region <?title?> from content:
		 *   "Hello World"
		 * and then gonna insert inside <?content?> tag
		 *
		 *
		 * <?title?> <h1> <?content?>Hello world<?/content?> </h1> <?/title?>
		 */

		if(isset($template)){
			$this->wrapping=$template;
			return;
		}


		// We will use content-template as a reference
		$this->ref = $this->template;
		list($class,$junk)=split('#',$this->template->top_tag);

		// This class will clone component's region from the component library
		if(!$this->api->components->is_set($class)){
			$this->debug("Component for $class is not defined");
			return;
		}

			//throw new BaseException("Region '$class' must be defined in content library");
		$this->wrapping=$this->api->components->cloneRegion($class);
	}
	function grabTags($t){
		foreach(array_keys($t->tags) as $tag){
			if(strpos($tag,'#'))continue;
			$this->grab($tag,null,$t);
		}
	}
}
