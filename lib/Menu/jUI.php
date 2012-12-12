<?php
class Menu_jUI extends Menu_Basic {
    // Implementation of jQuery UI Menus

	public $current_menu_class="current";
	public $inactive_menu_class="";

    public $options=array();
    function render(){
        $this->js(true)->menu($this->options);
        return parent::render();
    }
    function defaultTemplate(){
		return array('menu','MenujUI');
	}
}
