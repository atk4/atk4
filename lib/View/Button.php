<?php // vim:ts=4:sw=4:et:fdm=marker
/**
  Implements a simple button 
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2011 Romans Malinovskis <atk@agiletech.ie>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class View_Button extends View_HtmlElement {

    /** @obsolete */
    private $link=null;

    /** use setIcon() to change icon displayed on the button */
    private $icon=null;
    function defaultTemplate(){
        return array('button','button');
    }

    // {{ Management of button 
    /** Button management */
    function setIcon($icon, $colour='blue'){
        // TODO: implment thorugh Icon
        $this->icon=$icon;
        //$this->template->set('icon',$icon);
        //$this->template->set('colour',$colour);
        return $this;
    }
    function setLabel($label){
        return $this->setText($label);
    }
    /** Adds CSS of the news  */
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
    /** redefine this method with empty one if you DONT want buttons to use jQuery UI */
    function jsButton(){
        $opt=array();
        if($this->icon)$opt['icons']['primary']='ui-icon-'.$this->icon;
        if(!($this->owner instanceof ButtonSet))$this->js(true)->button($opt);
    }
    function render(){
        $this->jsButton();

        return parent::render();
    }
    // }}}

    // {{{ Click handlers
    /** Add click handler on button and returns true if button was clicked */
    function isClicked($confirm=null){

        $cl=$this->js('click')->univ();
        if($confirm)$cl->confirm($confirm);

        $cl->ajaxec($this->api->getDestinationURL(null,array($this->name=>'clicked')));

        return isset($_GET[$this->name]);
    }
    /** Add click handler on button and executes $callback if butotn was clicked */
    function onClick($callback,$confirm=null){
        if($this->isClicked($confirm)){

            // TODO: add try catch here
            $ret=call_user_func($callback,$this);

            if($ret instanceof jQuery_Chain)$ret->execute();

            // blank chain otherwise
            $this->js()->execute();
        }
    }
    // }}}

    // {{{ Obsolete
    /** @obsolete */
    function setAction($js=null,$page=null){
        throw $this->exception('setAction is not obsolete. use onClick or redirect method');

        return $this;
    }
    /** @obsolete */
    function redirect($page){
        return $this->js('click')->univ()->redirect($this->api->getDestinationURL($page));
    }
    /** @obsolete */
    function submitForm($form){
        throw $this->exception('submitForm() is obsolete, use button->js("click",$form->js()->submit());');
        return $this->js('click',$form->js()->submit());
    }
    // }}}
}
