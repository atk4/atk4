<?php
/**
 * Similar to wrap, but additionally renders nested components
 */
class sw_simple extends sw_wrap {
	function init(){
		$this->grabTags($this->template);
		parent::init();
		$this->wrapping->set($this->data);
	}
}
