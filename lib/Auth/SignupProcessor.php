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
/*
 * Created on 26.05.2007 by *Camper* (camper@adevel.com)
 */
class Auth_SignupProcessor extends AbstractController{
	function init(){
		parent::init();
		if($this->api->page!==$this->api->getConfig('auth/register_page','none')){
			return;
		}
		$p=$this->add('Page');
		$p->template->loadTemplate('empty');
		$p->add('page_'.$this->api->getConfig('auth/register_page'), null, 'Content');
		$p->template->set('page_title',trim($this->getResourceTitle().' Sign Up'));
		$p->downCall('render');
		echo $p->template->render();
		exit;
	}
	function getResourceTitle(){
		return $this->api->getConfig('resource_name','');
	}
	function getLink(){
		return '<a href="'.$this->api->getDestinationURL($this->api->getConfig('auth/register_page')).'">' .
				'Register</a>';
	}
}
