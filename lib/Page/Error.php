<?php
/**
 * This page is displayed every time unhandled exception occurs
 *
 * Created on 23.01.2008 by *Camper* (camper@adevel.com)
 */
class page_Error extends Page{

	function setError($error){
		$this->template->trySet('message',$error->getMessage());
		return $this;
	}
	function defaultTemplate(){
		return array('page_error','_top');
	}
}