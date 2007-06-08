<?php
/*
 * Created on 26.05.2007 by *Camper* (camper@adevel.com)
 */
class Auth_SignupProcessor extends AbstractController{
	function init(){
		parent::init();
		if($this->api->page!==$this->api->getConfig('auth/register_page','none')){
			return;
		}
		$p=$this->add('Page');
        $p->template->loadTemplate('empty');
		$p->add('page_'.$this->api->getConfig('auth/register_page'), null, 'Content');
		$p->template->set('page_title',trim($this->getResourceTitle().' Sign Up'));
		$p->downCall('render');
		echo $p->template->render();
		exit;
	}
	function getResourceTitle(){
		return $this->api->getConfig('resource_name','');
	}
	function getLink(){
		return '<a href="'.$this->api->getDestinationURL($this->api->getConfig('auth/register_page')).'">' .
				'Register</a>';
	}
}