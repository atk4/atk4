<?php
class Api_Admin extends ApiFrontend {

    public $menu;

    function init() {
        parent::init();


        $this->add('Layout_Fluid');

        $this->menu = $this->layout->addMenu();

    }
}
