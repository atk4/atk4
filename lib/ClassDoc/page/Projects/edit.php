<?php
/*
 * Created on 22.03.2006 by *Camper*
 */
class page_Projects_edit extends Page{
	function init(){
		parent::init();
		$this->frame('Content', 'Project')->add('Form_ProjectProps', null, 'content');
	}
}
?>
