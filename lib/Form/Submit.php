<?php
/**
 * Displays submit button
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form_Submit extends View_Button {
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
