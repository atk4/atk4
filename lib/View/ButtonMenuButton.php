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
class View_ButtonMenuButton extends Button {

    public $menu=null;
    /* Show menu when clicked */
    function addButtonMenu() {
        $menu_button_options = array(
            'text'=>false,
            'icons'=>array(
                'primary'=>'ui-icon-triangle-1-s',
            ),
        );

        $but = $this->owner->add('Button')->set('Menu');

        $menu = $this->owner->add('menu/Menu_Dropdown');
        $menu->js(true)->hide();

        $but->js('click',
            $menu->js()->show()
                    ->css('position','absolute')
                    ->position(array(
                          'my' => "left top",
                          'at' => "left bottom",
                          'of' => $this->js(null,"'#".$but->name."'")
                    ))
        );

        $this->js(true)->_selectorDocument()->bind('click',
            $this->js(null,'function(ev){'.$menu->js()->hide().'}')
        );

        $but->js(true)->button($menu_button_options)->removeClass('ui-corner-all')->addClass('ui-corner-right');
        $but->js(true)->css('margin-left','-2px');
        $this->js(true)->button()->removeClass('ui-corner-all')->addClass('ui-corner-left');
        $this->js(true)->css('margin-right','-2px');

        return $menu;
    }
}
