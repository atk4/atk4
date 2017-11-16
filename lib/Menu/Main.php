<?php
/**
 * Undocumented
 */
class Menu_Main extends Menu_Objective
{
    public $current_menu_class = 'atk-state-active';

    /**
     * Default template.
     *
     * @return array|string
     */
    public function defaultTemplate()
    {
        return array('menu/main');
    }
}
