<?php
/**
 * Api_Admin should be used for building your own application's administration
 * model. The benefit is that you'll have access to a number of add-ons which
 * are specifically written for admin system.
 *
 * Exporting add-ons, database migration, test-suites and other add-ons
 * have developed User Interface which can be simply "attached" to your
 * application's admin.
 *
 * This is done through hooks in the Admin Class. It's also important that
 * Api_Admin relies on layout_fluid which makes it easier for add-ons to
 * add menu items, sidebars and foot-bars.
 */
class Api_Admin extends ApiFrontend {

    public $menu;

    function init() {
        parent::init();


        $this->add('Layout_Fluid');

        $this->menu = $this->layout->addMenu();

        $this->initAddons();
    }

    function initAddons() {
        // TODO: Initialize add-ons automatically which have standard
        // controllers out there
    }
}
