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
class APortalException extends BaseException {
	var $related_obj=null;
	function __construct($msg,$related_obj=null){
		parent::__construct($msg);
		$this->related_obj = $related_obj;
	}
	function getAdditionalMessage(){
		if(!$this->related_obj)return '';
		return "Related object: ID: ".$this->related_obj->id.", Type: ".$this->related_obj->type.", Name: ".$this->related_obj->name;
	}
}
