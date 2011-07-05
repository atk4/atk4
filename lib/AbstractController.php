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
class AbstractController extends AbstractObject {
	protected $model;
	
	function __clone(){
		parent::__clone();
		if($this->model)$this->model=clone $this->model;
	}
	public function setModel($classname) {
		$this->model = $this->add($classname);
		return $this;
	}
	
	public function getModel() {
		return $this->model;
	}
}
