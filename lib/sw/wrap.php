<?php
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
