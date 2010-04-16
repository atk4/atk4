<?php
/**
 * Displays submit button
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form_AjaxSubmit extends AbstractView {
	public $label;
	function setLabel($_label){
		$this->label=$_label;
	}
	function render(){
		$this->owner->template_chunks['form']->append('form_buttons',
		'<input type="button" value="'.$this->label.'" onclick="submit_'.$this->owner->name.'(\''.$this->name.'\')">');
	}
}
