<?php
/**
 * Inserts random number
 */
class sw_random extends View {
	function render(){
		$this->output(rand());
		parent::render();
	}
	function processRecursively() {}
}
