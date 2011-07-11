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
/**
 * Displays submit button
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form_Submit extends Button {
	protected $label;	// absolute: TODO: remove
	public $no_save=null;
	protected $style=array();

	/*
	   function setLabel($_label){
	   $this->label=$_label;
	   return $this;
	   }
	 */
	function init(){
		parent::init();
		$this->template->trySet('type','submit');
	}
	function setNoSave(){
		// Field value will not be saved into defined source (such as database)
		$this->no_save=true;
		return $this;
	}
	function disable(){
		$this->js(true)->attr('disabled','disabled');
		return $this;
	}
	/*
	   function render(){
	   $this->owner->template_chunks['form']
	   ->append('form_buttons','<input type="submit" name="'.$this->name.
	   '" value="'.$this->label.'" ' .
	   ((count($this->style)?'style="'.implode(';',$this->style).'"':'')).
	   '>');
	   }
	   function setColor($color) {
	   $this->style[] = 'color: '.$color;
	   return $this;
	   }
	 */
}
