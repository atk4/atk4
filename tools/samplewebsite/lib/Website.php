<?
/*
   Commonly you would want to re-define ApiFrontend for your own application.
 */
class Website extends ApiFrontend {
	function init(){
		parent::init();

		$this->initLayout();
	}
	function initLayout(){
		parent::initLayout();
	}
}
