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
 * Menu component
 * Draws a one-level menu on a page.
 * Redefine buildMenu() to render your menu properly or add additional sublevels
 */
class sw_menu extends sw_wrap {
	protected $menu_content=array();

	function init(){
		parent::init();
		$this->l1=$this->template->cloneRegion('level1');
		$this->template->del('items');

		$this->buildMenu($this->api->getConfig('menu'));
	}
	function buildMenu($menu){
		if(!is_array($menu))return;
		foreach($menu as $link=>$data){
			$this->setCurrent($this->owner->page==$this->api->getConfig('base_path').$link);
			$this->l1->set('link',$this->api->getConfig('base_path').(!strpos($link,'.')?$link.'.html':$link));
			$this->l1->set('text',is_array($data)?$data['title']:$data);
			$this->template->append('items',$this->l1->render());
		}
	}
	function setCurrent($is_current=true){
		// sets the menuitem marked as selected/deselected
		// add whatever formatting code you need here
		//if($is_current)$this->l1->set('classes','<td class="bg01On"><div class="lbtn01On">');
		//else $this->l1->set('classes','<td class="bg01Off"><div class="lbtn01Off">');
	}
}
