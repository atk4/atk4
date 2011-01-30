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
class View_Columns extends View {
	public $cnt=0;
	function init(){
		parent::init();
	}
	function addColumn($width='*',$name='column'){
		// TODO: implement  width
		++$this->cnt;
		$c=$this->add('View',$name,'Columns',array('view/columns','Columns'));
		$c->template->trySet('width',$width);
		$this->template->set('cnt',$this->cnt);
		return $c;
	}
	function defaultTemplate(){
		return array('view/columns','_top');
	}
}
?>
