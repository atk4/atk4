<?php
class sw_grab extends sw_delete {
	function init(){
		parent::init();
		list($class,$junk)=explode('#',$this->template->top_tag);
		$info_key='_'.$class;

		$val=$this->template->render();
		$this->api->info[$info_key]=$val;
	}
}
