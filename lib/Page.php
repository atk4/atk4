<?php
/**
 * This is the description for the Class
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Page extends AbstractView {
	protected $title='Page';
	function init(){
		if($_GET['cut_page'] && !$_GET['cut_object'] && !$_GET['cut_region'])
			$_GET['cut_object']=$this->short_name;
		parent::init();
	}
	function defaultTemplate(){
		if($_GET['cut_page'])return array('page','Content');
		$page_name='page/'.strtolower($this->short_name);
		if($this->api->template->findTemplate($page_name))return array($page_name,'_top');
		else return 'Content';
	}
	function getTitle(){
		return $this->title;
	}
}
