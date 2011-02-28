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
/* TODO:
* 1) logic support for validators
* 2) logic support for data restoration upon failed post request, don't stay at
* post
* 3) add submit handler
* */
class sw_form extends Form {
	function init(){
		parent::init();
		echo $this->api->recall("entrance_url");
		$counter = 0;
		$this->name = preg_replace("/#/", "___", $this->name);
		foreach(array_keys($this->logic->template) as $tag){
			list($class,$junk)=split('#',$tag);
			if(is_numeric($class))continue;     // numeric ones are just a text, not really a tag
			$type='text';
			$counter++;
			$caption = null;
			$validator = null;
			$field_type = null;
			if ($class == "onsuccess"){
				foreach (array_keys($this->logic->template[$tag]) as $subtag){
					list($class,$junk)=split('#',$subtag);
					if ($class == "redirect"){
						$this->onsuccess_method = "redirect";
						$to = $this->onsuccess_aux = $this->logic->template[$tag][$subtag][0];
						continue;
					} else if ($class == "callmethod"){
						$this->onsuccess_method = "method";
						foreach (array_keys($this->logic->template[$tag][$subtag]) as $subsubtag){
							list($class,$junk)=split('#',$subsubtag);
							if ($class == "class"){
								$this->onsuccess_class = $this->logic->template[$tag][$subtag][$subsubtag][0];
							} else if ($class == "method"){
								$this->onsuccess_aux = $this->logic->template[$tag][$subtag][$subsubtag][0];
							}
						}
					}
				}
			}
			foreach(array_keys($this->logic->template[$tag]) as $subtag){
				list($class,$junk)=split('#',$subtag);
				if(is_numeric($subtag)){
					if ($subtag == 0){
						$caption = $this->logic->template[$tag][$subtag];
					}
				} else if ($class=='type'){
					$type=$this->logic->get($subtag);
					if ($type=="text"){
						$field_type = "line";
					} elseif ($type == "password"){
						$field_type = "password";
					}
				} else if ($class == 'validator'){
					$validator = $this->logic->template[$tag][$subtag][0];
				}
			}
			if($field_type){
				$last_field = $this->addField($field_type,'f'.$counter, $caption);
				if ($validator){
					if (method_exists($last_field, $validator)){
						$last_field->$validator();
					}
				}
			}
		}

		$this->addSubmit('Ok');
		$this->addButton('Cancel');
		if($this->isSubmitted()){
			if ($method = $this->onsuccess_method){
				$this->$method($this->onsuccess_aux);
			}
			/* what to do? */
		} else {
			/* what to do? */
		}
	}
	function method($aux){
		if (class_exists($class = $this->onsuccess_class)){
			$class = $this->add($class, "", "class");
			if (method_exists($class, $aux)){
				$class->$aux();
			}
		}

	}
	function redirect($url){
		if (!$url){
			$url = $this->api->recall("entrance_url");
			$this->api->forget("entrance_url");
		}
		$generic = new sw_generic();
		$generic->redirect($url);
	}
}
