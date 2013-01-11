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
