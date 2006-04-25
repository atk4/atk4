<?php
/*
 * Created on 21.04.2006 by *Camper*
 */
class page_Projects_name_name_detail extends Page{
	function init(){
		parent::init();
		$this->frame('Content', $this->api->getMemberName($_GET['member_id']).' (id='.$_GET['member_id'].')')
			->add('Form_MemberDetails', null, 'content');
	}
}