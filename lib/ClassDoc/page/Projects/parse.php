<?php
/*
 * Created on 14.04.2006 by *Camper*
 */
class page_Projects_parse extends Page{
	function init(){
		parent::init();
		$this->frame('Content', 'Parsing parameters')->add('Form_Project', null, 'content');
	}
}