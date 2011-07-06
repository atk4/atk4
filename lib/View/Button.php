<?php
/***********************************************************
  Implements a regular button

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
class View_Button extends View_HtmlElement {
    private $icon=null;
    private $link=null;
    function defaultTemplate(){
        return array('button','button');
    }
    function setIcon($icon){
        // TODO: implment thorugh Icon
        $this->icon=$icon;
        $this->template->set('icon',$icon);
        return $this;
    }
    function setLabel($label){
        return $this->setText($label);
    }
    function setButtonStyle($n){
        $this->template->set('button_style',$n);
        return $this;
    }
    function setStyle($key,$value=null){
        return parent::setStyle($key,$value);
        //$this->style[]="$key: $value";
        //return $this;
    }
    function setClass($class){
        $this->class=$class;
        return $this;
    }
    /* redefine this method with empty one if you DONT want buttons to use jQuery UI */
    function jsButton(){
        if(!($this->owner instanceof ButtonSet))$this->js(true)->button();
    }
    function render(){
        $this->jsButton();

        if($this->icon){
            $this->addClass('ui-button-and-icon');
        }else{
            //$this->addClass('ui-button');
            $this->template->tryDel('icon_span');
        }

        return parent::render();
    }
    /* Add click handler on button and returns true if button was clicked */
    function isClicked($confirm=null){

        $cl=$this->js('click')->univ();
        if($confirm)$cl->confirm($confirm);

        $cl->ajaxec($this->api->getDestinationURL(null,array($this->name=>'clicked')));

        return isset($_GET[$this->name]);
    }
    /* Add click handler on button and executes $callback if butotn was clicked */
    function onClick($callback,$confirm=null){
        if($this->isClicked($confirm)){

            // TODO: add try catch here
            $ret=call_user_func($callback,$this);

            if($ret instanceof jQuery_Chain)$ret->execute();

            // blank chain
            $this->js()->execute();
        }
    }
    function setAction($js=null,$page=null){
        throw $this->exception('setAction is not obsolete. use onClick or redirect method');

        return $this;
    }
    function redirect($page){
        return $this->js('click')->univ()->redirect($this->api->getDestinationURL($page));
    }
    function submitForm($form){
        throw $this->exception('submitForm() is obsolete, use button->js("click",$form->js()->submit());');
        return $this->js('click',$form->js()->submit());
    }
}
