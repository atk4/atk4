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
class View_DropButton extends Button {

    public $menu = null;

    function render(){
        if(!isset($this->options['icons']['secondary'])){
            $this->options['icons']['secondary'] = 'ui-icon-triangle-1-s';
        }
        parent::render();
    }
    /* Show menu when clicked */
    function useMenu(){
        $popover = $this->owner->add('View_Popover', null, $this->spot);

        $this->menu = $popover->add('Menu_jUI');

        $this->js('click', $popover->showJS($this));

        return $this->menu;
    }
}
