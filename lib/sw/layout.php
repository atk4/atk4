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
