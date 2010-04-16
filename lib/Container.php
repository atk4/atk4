<?php
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
