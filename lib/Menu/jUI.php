<?php
/**
 * Undocumented
 */
class Menu_jUI extends Menu_Objective
{
    // Implementation of jQuery UI Menus

    public $current_menu_class = 'current';
    public $inactive_menu_class = '';

    public $js_widget = 'menu';
    public $options = array();

    public function render()
    {
        if ($this->js_widget !== false) {
            $this->js(true)->{$this->js_widget}($this->options);
        }

        return parent::render();
    }
    public function addSubMenu($name)
    {
        $p = parent::addSubMenu($name);
        $p->js_widget = false;

        return $p;
    }
}
