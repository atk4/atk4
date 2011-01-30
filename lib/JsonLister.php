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
 * Renderer for JSON objects
 * Can be used by any type of javascript controls which require JSON data as an input:
 * - SigmaGrid
 * - flexbox
 * - etc.
 * 
 * Acts as a generic CompleteLister and can get data from DB or static arrays
 * 
 * @author Camper (cmd@adevel.com) on 14.04.2009
 */
class JsonLister extends CompleteLister{
	function init(){
		// we don't need anything from CompleteLister
		AbstractView::init();
	}
	function execQuery(){
		$this->data=$this->dq->do_getAllHash();
	}
	function render(){
		return (json_encode(array('results'=>$this->data)));
	}
	function defaultTemplate(){
		// we need an empty template with Content in it
		return array('empty','Content');
	}
}
