<?php
/**
 * This is the description for the Class
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Page extends AbstractView {
	function defaultTemplate(){
		$page_name='page/'.strtolower($this->short_name);
		if($this->api->template->findTemplate($page_name))return array($page_name,'_top');
		else return 'Content';
	}
}
