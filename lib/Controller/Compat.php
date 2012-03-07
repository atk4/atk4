<?php
/* 
   Adds some compatibility to get 4.1-based web app
   running on 4.2 quickly.

 */

class Controller_Compat extends AbstractController {
    function init(){
        parent::init();
        $this->api->compat=$this;
    }
}
