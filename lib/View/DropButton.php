<?php
class View_DropButton extends Button {

    public $menu=null;

    function render(){
        if(!isset($this->options['icons']['secondary'])){
            $this->options['icons']['secondary']='ui-icon-triangle-1-s';
        }
        parent::render();
    }
    /* Show menu when clicked */
    function useMenu($width=3){
        $flyout=$this->owner->add('View_Flyout',null,$this->spot);
        $flyout->useArrow();

        $this->menu=$flyout->add('Menu_jUI');

        $this->js('click',$flyout->showJS($this));


        return $this->menu;
    }
}
