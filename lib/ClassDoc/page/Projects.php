<?php
/*
 * Created on 21.03.2006 by *Camper*
 */
class page_Projects extends Page{
	function init(){
		parent::init();
		$this->add('ListProjects', null, 'Content');
	}
}
?>
