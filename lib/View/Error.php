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
/**
 * Implementation of Error Message Box
 *
 * Use: 
 *  $this->add('View_Error')->set('Error Text');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class View_Error extends View_Box {
    public $class="ui-state-error";
    function init(){
        parent::init();
        $this->template->trySetHTML('Icon','<i class="ui-icon ui-icon-alert"></i>');    // change default icon
    }
}
