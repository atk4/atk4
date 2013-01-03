<?php
/***********************************************************
  ..

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
/**
 * Displays submit button
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 * @version     $Id$
 */
class Form_Submit extends Button {
    public $no_save=null;

    function init(){
        parent::init();
        $this->template->trySet('type','submit');
        if($this->owner->js_widget){
            $this->js('click',array(
                        $this->owner->js()->find('input[name=ajax_submit]')->val($this->short_name),
                        $this->owner->js()->submit()
                        ));
        }
    }
    function setNoSave(){
        // Field value will not be saved into defined source (such as database)
        $this->no_save=true;
        return $this;
    }
    function disable(){
        $this->js(true)->attr('disabled','disabled');
        return $this;
    }
}
