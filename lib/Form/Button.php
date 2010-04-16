<?php
/**
 * Displays flexible button
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form_Button extends Button {
	public $label;

	public $onclick='';

	protected $style = array();

	function setLabel($_label){
		$this->label=$_label;
		parent::setLabel($_label);
		return $this;
	}
	function setNoSave(){
		// Field value will not be saved into defined source (such as database)
		$this->no_save=true;
		return $this;
	}
}
