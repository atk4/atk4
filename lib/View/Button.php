<?php // vim:ts=4:sw=4:et:fdm=marker
/**
  Implements a simple button 
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class View_Button extends View_HtmlElement {

    /** use setIcon() to change icon displayed on the button */
    private $icon=null;

    public $menu = null;
    public $options=array();
    public $menu_class = 'Menu_jUI';
    function defaultTemplate(){
        return array('button','button');
    }

    // {{ Management of button 
    /** Button management */
    function setNoText($icon=null){
        $this->options['text']=false;
        if($icon)$this->setIcon($icon);
        return $this;
    }
    function setIcon($icon){
        // TODO: implement through Icon
        $this->icon=$icon;
        //$this->template->set('icon',$icon);
        //$this->template->set('colour',$colour);
        return $this;
    }
    function setLabel($label){
        return $this->setText($this->api->_($label));
    }
    /** redefine this method with empty one if you DONT want buttons to use jQuery UI */
    private $js_button_called=false;
    function jsButton(){
        if($this->js_button_called)return $this;
        $this->js_button_called=true;
        $opt=$this->options;
        if($this->icon)$opt['icons']['primary']=$this->icon;
        
        // Imants: commented out because we still need to be able to pass
        //         parameters to buttons even if they are inside buttonset.
        //         Not sure why this exceptional case was created before.
        //if(!($this->owner instanceof ButtonSet))$this->js(true)->button($opt);
        $this->js(true)->button($opt);
        return $this;
    }
    function render(){
        $this->jsButton();

        return parent::render();
    }
    function link($page,$args=array()){
        $this->setElement('a');
        $this->setAttr('href',$this->api->url($page,$args));
        return $this;
    }

    // }}}
    // {{{ Enhanced javascript functionality
    /**
     * When button is clicked, opens a frame containing generic view.
     * Because this is based on a dialog (popover), this is a modal action
     * even though it does not add dimming / transparency.
     */
    function addPopover($options=null){
        $this->options['icons']['secondary'] = 'ui-icon-triangle-1-s';
        $popover = $this->owner->add('View_Popover', null, $this->spot);
        $this->js('click', $popover->showJS($this,$options));
        return $popover;
    }
    /**
     * Adds another button after this one with an arrow and returns it.
     */
    function addSplitButton($options=null){

        $options=//array_merge(
            array(
                'text'=>false,
                'icons'=>array(
                    'primary'=>'ui-icon-triangle-1-s'
                ),
            );
            /*
            $options
        );

             */
        $but = $this->owner->add('Button',array('options'=>$options),$this->spot);
        $this->owner->add('Order')->move($but,'after',$this)->now();

        // Not very pretty, but works well
        $but->jsButton()
            ->js(true)
            ->removeClass('ui-corner-all')
            ->addClass('ui-corner-right')
            ->css('margin-left','-2px');

        $this->jsButton()
            ->js(true)
            ->removeClass('ui-corner-all')
            ->addClass('ui-corner-left')
            ->css('margin-right','-2px');
        return $but;
    }

    /* Show menu when clicked */
    function useMenu(){
        // depreciated, use addMenu
       return $this->addMenu();
    }
    function addMenu($width='200px'){
        $this->options['icons']['secondary'] = 'ui-icon-triangle-1-s';

        $this->menu = $this->owner->add($this->menu_class, null, $this->spot);
        $this->menu->addStyle('display','none');

        $this->js('click', array(
            $this->menu->js()->show()->position(array(
                'my'=>'left top',
                'at'=>'left bottom',
                'of'=>$this
            )),
        ));
        $this->menu->js(true)->width($width);
        $this->js(true)->_selectorDocument()->bind('click',
            $this->js(null,'function(ev){'.$this->menu->js()->hide().'}')
        );

        return $this->menu;
    }

    // }}}

    // {{{ Click handlers
    /** Add click handler on button and returns true if button was clicked */
    function isClicked($confirm=null){

        $cl=$this->js('click')->univ();
        if($confirm)$cl->confirm($confirm);

        $cl->ajaxec($this->api->url(null,array($this->name=>'clicked')));

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
        throw $this->exception('setAction() is now obsolete. use onClick() or redirect() method');

        return $this;
    }
    /** @obsolete */
    function redirect($page){
        return $this->js('click')->univ()->redirect($this->api->url($page));
    }
    /** @obsolete */
    function submitForm($form){
        throw $this->exception('submitForm() is obsolete, use button->js("click",$form->js()->submit());');
        return $this->js('click',$form->js()->submit());
    }
    // }}}
}
