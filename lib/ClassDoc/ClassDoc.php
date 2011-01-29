<?php
class ClassDoc extends Namespace {
	function initNamespace(){
		$r=$this->api->frame('RightSidebar','Class Doc',null,'width="200"')
			->add('HelloWorld',null,'content')->setMessage('Documentation for this application is available online.<p><a href="javascript:w(\''.$this->api->getDestinationURL($this->short_name.';Projects').'\',600,400)">Click here</a>');
	}
}
