<?php
/***********************************************************
  Generic class for several UI elements (warnings, errors, info)

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
class View_Box extends View {
    public $class=null;         // ui-state-highligt, ui-state-error, etc
    public $has_close=false;
    function set($text){
        $this->template->set('Content',$text);
        return $this;
    }
    /** View box can be closed by clicking on the cross */
    function addClose($state=true){
        $this->has_close=$state;
        return $this;
    }
    /** By default box uses information Icon. You can use addIcon() to override or $this->del('Icon') to remove. */
    function addIcon($i){
        return $this->add('Icon',null,'Icon')->set($i);
    }
    function render(){
        $this->template->trySet('class',$this->class);
        if($this->has_close){
            $this->js('click',
                $this->js()->fadeOut()
            )->_selector('#'.$this->name.' .ui-icon-closethick');
        }else{
            $this->template->tryDel('close');
        }
        return parent::render();
    }
    function defaultTemplate(){
        return array('view/box');
    }
}
