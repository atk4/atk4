<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
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
