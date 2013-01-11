<?php
/***********************************************************
  Display standard Warning box

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class View_Warning extends View_Box {
    public $class="ui-state-highlight";
    function init(){
        parent::init();
        $this->template->trySetHTML('Icon','<i class="ui-icon ui-icon-alert"></i>');    // change default icon
    }
}
