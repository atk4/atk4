<?php
/**
 * Renderer for JSON objects
 * Can be used by any type of javascript controls which require JSON data as an input:
 * - SigmaGrid
 * - flexbox
 * - etc.
 * 
 * Acts as a generic CompleteLister and can get data from DB or static arrays
 * 
 * @author Camper (cmd@adevel.com) on 14.04.2009
 */
class JsonLister extends CompleteLister{
	function init(){
		// we don't need anything from CompleteLister
		AbstractView::init();
	}
	function execQuery(){
		$this->data=$this->dq->do_getAllHash();
	}
	function render(){
		return (json_encode(array('results'=>$this->data)));
	}
	function defaultTemplate(){
		// we need an empty template with Content in it
		return array('empty','Content');
	}
}