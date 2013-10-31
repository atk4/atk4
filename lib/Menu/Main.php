<?php
class Menu_Main extends Menu_Objective {
    public $current_menu_class = 'atk-state-active';

    function defaultTemplate() {
        return array('menu/main');
    }
}
