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
class sw_signup extends View {
	function init(){
		parent::init();
	}
	function render(){
		return;
	}
	function initializeTemplate($tag, $template){
		return;
	}
	function add_member(){
		/* inserts a new member in STORAGE */
		$storage = $this->add("sw_storage", "", "");
		$storage->table($this->api->getConfig('members/table'));
		$storage->field(array('email', 'name', 'surname', 'telephone', 'company', 'hash'));
		$storage->set(
			array(
				"email" => $_POST[$_GET["submit"] . "_f2"],
				"name" => $_POST[$_GET["submit"] . "_f3"],
				"surname" => $_POST[$_GET["submit"] . "_f4"],
				"telephone" => $_POST[$_GET["submit"] . "_f5"],
				"company" => $_POST[$_GET["submit"] . "_f6"],
				"hash" => md5(time() . rand(0,1000))
			)
		);
		$storage->do_insert();
		$generic = $this->add("sw_generic", null, null, null);
		$generic->redirect();
	}
	function confirm_member(){
		/* confirms member via hash */

	}
}
?>
