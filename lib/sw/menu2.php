<?php

class sw_menu2 extends Menu {
	function init(){
		foreach($this->logic->tags as $tag){
			//.. if continue;
			//.. if continue;

			$this->addMenuItem($this->logic->get[$tag]);
		}
	}
}
