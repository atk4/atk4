<?php
/***********************************************************
  Display standard Warning box

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
class View_Warning extends View_Box {
    public $class="ui-state-highlight";
    function init(){
        parent::init();
        $this->template->trySetHTML('Icon','<i class="ui-icon ui-icon-alert"></i>');    // change default icon
    }
}
