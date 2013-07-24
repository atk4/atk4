<?php // vim:ts=4:sw=4:et:fdm=marker
/**
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
class View_ButtonMenuButton extends Button
{
    // Menu class
    // Dependency: https://github.com/DarkSide666/ds-addons/tree/master/menu
    // but you can use simple 'Menu_jUI' too
    public $menu_class = 'menu/Menu_Dropdown';

    // direction of menu. False = horizontal, true = vertical
    public $up = false;



    /**
     * Create button and menu, show menu when button clicked
     *
     * @param array $options Options to pass to Menu class 
     *
     * @return Menu
     */
    function addButtonMenu($options = array())
    {
        $but_options = array(
            'text'  => false,
            'icons' => array(
                'primary' => ($this->up
                   ? 'ui-icon-triangle-1-n'
                   : 'ui-icon-triangle-1-s'
                ),
            ),
        );

        $but = $this->owner->add('Button')->set('Menu');

        // But in theory 
        $menu = $this->owner->add($this->menu_class, $options);
        $menu->js(true)->hide();

        $but->js('click',
            $menu->js()->show()
                ->css('position', 'absolute')
                ->position(
                    $this->setPosition($but)
                )
        );

        $this->js(true)->_selectorDocument()->bind('click',
            $this->js(null, $menu->js()->hide()->_enclose())
        );

        $but->js(true)
                ->css('margin-left', '-2px')
                ->button($but_options)
                ->removeClass('ui-corner-all')
                ->addClass('ui-corner-right');
        $this->js(true)
                ->css('margin-right', '-2px')
                ->button()
                ->removeClass('ui-corner-all')
                ->addClass('ui-corner-left');

        return $menu;
    }
    
    /**
     * Return menu position
     *
     * @param Button $but Button object
     *
     * @return array
     */
    function setPosition($but)
    {
        if ($this->up) {
            // vertical menu
            return array(
              'my' => "left top",
              'at' => "right top",
              'of' => $this->js(null, "'#".$but->name."'")
            );
        } else {
            // horizontal menu
            return array(
              'my' => "left top",
              'at' => "left bottom",
              'of' => $this->js(null, "'#".$but->name."'")
            );
        }
    }
}
