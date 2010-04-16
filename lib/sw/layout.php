<?php
/**
 * Layout is the base "skin" for your page. You should use <?layout?> tag inside your template. This will make it load
 * templates/layout.html and use it as a base template. Content will be substituted acordinly and further components will
 * be rendered properly
 */
class sw_layout extends sw_component {
	function init(){
		parent::init();

		$tag=$this->template->top_tag;
		list($class,$junk)=split('#',$tag);

		$name="layout".($class=='layout'?'':('_'.$class));

		$this->debug("Loading template $name for layout");

		$t = $this->template;

		$this->template=$this->add('SMlite')->loadTemplate($name);

		$c=$this->add('View','content','content',$t);

		$this->api->processTemplate($c);

		if($this->template->is_set('location'))$this->add('sw_location','location','location','location');
	}
	function processRecursively(){}
	function render(){
		$this->template->set($this->api->info);
		parent::render();
	}
}
