<?php
/**
 * Adds some compatibility to get 4.1-based web app
 * running on 4.2 quickly.
 */
class Controller_Compat extends AbstractController
{
    public function init()
    {
        parent::init();
        $this->app->compat = $this;

        $l = $this->app->locate('template', 'css/atk-custom.css', 'location');
        if ($l->relative_path != 'atk4') {
            // use compatible shared templates
            $this->app->pathfinder->atk_location->contents['template']['templates'] = 'templates/compat41';
        }
    }
}
