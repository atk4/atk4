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
trigger_error("Class Container is obsolete. Now any class is container, so change to AbbstractView. Called from ".caller_lookup(2,true));
exit;
/**
 * This class generalizes base methods and properties for all classes which are
 * going to contain childs. This class maintains downCall correctly so the call
 * is passed down the tree for all elements.
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Container extends BaseObject {
	/**
	 * Sub-elements of container object.
	 */
	/**
	 * Description of the Variable
	 */
	public $elements = array();



	/////////////// C r o s s   f u n c t i o n s ///////////////
	function downCall($type,$args=null){
		/**
		 * Execute handler for this element. If it's not defined, pass event to
		 * all sub-elements. If any of the elements returns true or false value,
		 * execution terminates and that value is returned.
		 */
		foreach(array_keys($this->elements) as $key){
			if($this->elements[$key] instanceof BaseObject){
				$this_result = $this->elements[$key]->downCall($type,$args);
				if($this_result===false)return false;
			}
		}
		return parent::downCall($type,$args);
	}
}
