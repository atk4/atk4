<?php
class InfoWindow extends Grid {
	function defaultTemplate(){
		return array('infogrid','_top');
	}
	function init(){
		parent::init();
		$this->addColumn('text','content','Information:');
		$this->safe_html_output=false;
		$this->setStaticSource($this->api->info_messages);
	}
	function render(){
		if($this->api->info_messages)return parent::render();
	}
}
