<?php
class View_DropButton extends Button {

    public $menu=null;

    function render(){
        if(!isset($this->options['icons']['secondary'])){
            $this->options['icons']['secondary']='ui-icon-triangle-1-s';
        }
        parent::render();
        if($this->menu){
            $this->js(true)->_selector('#'.$this->menu->name)->append('<div class="arrow top"></div>');
        }
    }
    /* Show menu when clicked */
    function useMenu($width=3){
        $this->menu=$m=$this->owner->add('Menu_jUI',null,$this->owner->spot);
        $this->owner->add('Order')->move($this->menu,'before',$this)->now();
        $m->addStyle('display','none')->addStyle('position','absolute');


        $this->js('click',array(
            $m->js()->show()->position(array(
                'my'=>'center top',
                'at'=>'center bottom',
                'of'=>$this,
                'offset'=>'20px'
            ),
            $this->js()->_selectorDocument()->one('click',$m->js()->hide()->_enclose())
        )));

        if($width)$m->addClass('span'.$width);

        return $m;
    }
}
