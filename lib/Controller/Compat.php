<?php
/* 
   Adds some compatibility to get 4.1-based web app
   running on 4.2 quickly.

 */

class Controller_Compat extends AbstractController {
    function init(){
        parent::init();
        $this->api->compat=$this;

        $l=$this->api->locate('template','css/atk-custom.css','location');
        if($l->relative_path!='atk4'){
            // use compatible shared templates
            $this->api->pathfinder->atk_location->contents['template']['templates']='templates/compat41';
        }

    }
}
