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
class View_Icon extends View_HtmlElement {
	public $color=null;
	function init(){
		parent::init();
		$this->setElement('i');
		parent::set('');
		$this->setColor($this->api->getConfig('icon/default-color','orange'));
	}
	function set($shape){
		$this->shape=$shape;
		return $this;
	}
	function setColor($color){
		$this->color=$color;
		return $this;
	}
	function render(){
		$this->addClass('atk-icon');
		$this->addClass('atk-icons-'.$this->color);
		$this->addClass('atk-icon-'.$this->shape);

		parent::render();
	}

}
