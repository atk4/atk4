<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

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
/* Implementation of plain HTML-only form. Does not support submission or anything */
class Form_Plain extends HtmlElement {
	function init(){
		parent::init();
		$this->setElement('form');
	}
	function addInput($type,$name,$value,$tag='Content'){
		$f=$this->add('HtmlElement',$name,$tag);
		$f->setElement('input');
		$f->setAttr('type',$type);
		$f->setAttr('value',$value);
		$f->set('');
		return $f;
	}
}
