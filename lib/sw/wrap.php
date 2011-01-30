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
 * Base component for wrapping content into something
 */
class sw_wrap extends sw_component {
	function init(){
		parent::init();
		$this->surroundBy();
		if (isset($this->owner->page)){
			$this->template->trySet('_page',$this->owner->page);
		}
		if (isset($this->owner->parent)){
			$this->template->trySet('_parent',$this->owner->parent);
		}
		if (isset($this->owner->title)){
			$this->template->trySet('_title',$this->owner->title);
		}
		if (isset($this->owner->base_path)){
			$this->template->trySet('_base',$this->api->base_path);
		}
		if (isset($this->owner->subdir)){
			$this->template->trySet('_subdir', $this->owner->subdir);
		}
	}
	function initializeTemplate($tag, $template){
		$this->init_tag = $tag;
		$this->init_template = $template;
		parent::initializeTemplate($tag, $template);
	}
	function render(){
		parent::render();
		$this->owner->template->trySet($this->init_tag, $this->template->render());
	}
	function processRecursively(){
		parent::processRecursively();
	}
}
