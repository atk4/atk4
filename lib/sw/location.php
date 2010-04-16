<?php
/**
 * Location component.
 * Draws a location bar on a page
 */
class sw_location extends sw_wrap {
	function init(){
		parent::init();
		$item=$this->cloneRegion('path_part');
		$this->wrapping->del('path');

		// adding common Home element
		$item->set('link','index');//$this->api->getConfig('base_path'));
		//$item->set('content','Home');
		$this->wrapping->append('path',$item->render());

		$location=$this->api->getConfig('menu');
		if(!isset($this->api->info['_loc']))return;

		foreach($this->api->info['_loc'] as $string){
			list($link,$title)=explode(',',$string,2);
			$item->set('link',$link);
			$item->set('content',$title);
			$this->wrapping->append('path',$item->render());
		}
	}
	function processRecursively(){}
}
