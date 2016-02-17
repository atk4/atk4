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
 * @license See https://github.com/atk4/atk4/blob/master/LICENSE
 *
**/
class View_Error extends View_Box {
    function init(){
        parent::init();
        $this->addClass('atk-effect-danger');
        $this->template->set('label',$this->app->_('Error').': ');
        $this->addIcon('attention');
    }
}
