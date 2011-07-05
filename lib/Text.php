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
class Text extends AbstractView {
	public $text='Your text goes here......';
	function set($text){
		$this->text=$text;
		return $this;
	}
	function render(){
		$this->output($this->text);
	}
	function setSource(){
		return call_user_func_array(array($this->owner,'setSource'),func_get_args());
	}
}
