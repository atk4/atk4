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
/**
 * This page is displayed every time unhandled exception occurs
 *
 * Created on 23.01.2008 by *Camper* (camper@adevel.com)
 */
class page_Error extends Page{

	function setError($error){
		$this->template->trySet('message',$error->getMessage());
		return $this;
	}
	function defaultTemplate(){
		return array('page_error','_top');
	}
}
