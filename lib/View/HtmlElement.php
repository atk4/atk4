<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
/*
 * This object add one simple element on the page such as div or tag. However it's extremely useful
 * for adding different properties, events or setting crlass
 */
class View_HtmlElement extends View {
	function setElement($element){
		$this->template->trySet('element',$element);
		return $this;
	}
	function setAttr($attribute,$value=null){
		if(is_array($attribute)&&is_null($value)){
			foreach($attribute as $a=>$b)$this->setAttr($a,$b);
			return $this;
		}
		$this->template->append('attributes',' '.$attribute.'="'.$value.'"');
		return $this;
	}
	function addClass($class){
		if(is_array($class)){
			foreach($class as $c)$this->addClass($class);
			return $this;
		}
		$this->template->append('class'," ".$class);
		return $this;
	}
	function setStyle($property,$style=null){
		if(is_null($style)&&is_array($property)){
			foreach($property as $k=>$v)$this->setStyle($k,$v);
			return $this;
		}
		$this->template->append('style',";".$property.':'.$style);
		return $this;
	}
	function addstyle($property,$style=null){
		return $this->setStyle($property,$style);
	}
	function setText($text){
		$this->template->trySet('Content',$text);
		return $this;
	}
	function set($text){
		return $this->setText($text);
	}
	function pageLink($page){
		$this->addClass('atk4-link');
		$this->setAttr('href',$this->api->getDestinationURL($page));
		return $this;
	}
	function defaultTemplate(){
		return array('htmlelement','_top');
	}
}
