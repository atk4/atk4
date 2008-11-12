<?php
/*
 * Created on 13.04.2006 by *Camper*
 */
class page_Projects_name extends Page{
	function init(){
		parent::init();
		$this->add('ProjectStructure', null, 'Content');
	}
}
