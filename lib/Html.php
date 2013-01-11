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
class Html extends AbstractView {
    public $html='Your text goes <u>here</u>......';
    /* Set text */
    function set($html){
        $this->html=$html;
        return $this;
    }
    function render(){
        $this->output($this->html);
    }
    function initializeTemplate(){
        $this->spot=$this->defaultSpot();
    }
}
