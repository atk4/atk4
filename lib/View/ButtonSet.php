<?php
class View_ButtonSet extends HtmlElement {
	function addButton($label){
		return $this->add('Button')->setLabel($label);
	}
	function render(){
		$this->js(true)->buttonset();
		parent::render();
	}
}
