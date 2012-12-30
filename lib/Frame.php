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
class Frame extends View {
    function setTitle($title){
        $this->template->trySet('title',$title);
        return $this;
    }
    function opt($opt){
        $this->template->trySet('opt',$opt);
        return $this;
    }
    function render(){
        if(!$this->template->get('title')){
            $this->template->tryDel('title_tag');
        }
        return parent::render();
    }
    function defaultTemplate(){
        return array('frames','MsgBox');
    }
}
