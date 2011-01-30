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
class QuickSearch extends Filter {
	/*
	 * Quicksearch represents one-field filter which goes perfectly with a grid
	 */

	public $js_widget='ui.atk4_form';
	var $region=null;
	var $region_url=null;

	function defaultTemplate(){
		return array('form/quicksearch','form');
	}
	function init(){
		parent::init();
		$this->js(true)->_load('ui.atk4_form')->atk4_form();
		$this->useDQ($this->owner->dq);
		//on field change we should change a name of a button also: 'clear' in the name will clear fields
		$this->addField('Search','q','Find');//->onChange()->ajaxFunc($this->setGoFunc());
		/*
		$this->getElement('q')
			->js('autochange',$this->owner->js()->atk4_grid('reloadData',
					array('q'=>$this->last_field->js()->val())
					));
					*/
		$this->addSubmit('Go');
	}
	function setGoFunc(){
		return "btn=document.getElementById('".$this->name.'_Clear'."'); if(btn){btn.value='Go'; btn.name='".
			$this->name."_go'; btn.id='".$this->name."_go';}";
	}
	function useFields($fields){
		$this->fields=$fields;
		return $this;
	}
	function applyDQ($dq){
		if(!($v=$this->get('q')))return;

		$v=addslashes($v);  // quote it

		$q=array();
		foreach($this->fields as $field){
			$q[]="$field like '%".$v."%'";
		}
		if($q){
			$dq->having(join(' or ',$q));
		}
	}
	function submitted(){
		if(parent::submitted()){
			$this->owner->js()->reload()->execute();
		}
	}
}
