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
 * Location component.
 * Draws a location bar on a page
 */
class sw_location extends sw_wrap {
	function init(){
		parent::init();
		$item=$this->cloneRegion('path_part');
		$this->wrapping->del('path');

		// adding common Home element
		$item->set('link','index');//$this->api->getConfig('base_path'));
		//$item->set('content','Home');
		$this->wrapping->append('path',$item->render());

		$location=$this->api->getConfig('menu');
		if(!isset($this->api->info['_loc']))return;

		foreach($this->api->info['_loc'] as $string){
			list($link,$title)=explode(',',$string,2);
			$item->set('link',$link);
			$item->set('content',$title);
			$this->wrapping->append('path',$item->render());
		}
	}
	function processRecursively(){}
}
