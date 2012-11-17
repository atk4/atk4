<?php
class View_DropButton extends Button {

    public $menu=null;

    function render(){
        if(!isset($this->options['icons']['secondary'])){
            $this->options['icons']['secondary']='ui-icon-triangle-1-s';
        }
        parent::render();
        if($this->menu){
        }
    }
    /* Show menu when clicked */
    function useMenu($width=3){
        $v=$this->owner->add('View',null,$this->spot)->addClass('flyout');
        $this->menu=$m=$v->add('Menu_jUI');
        $m->addStyle('display','none')->addStyle('position','absolute');
        $v->add('View')->setClass('arrow top');
        $v->addStyle('border: 1px solid red');
        //if($width)$v->addClass('span'.$width);


        $this->js('click',array(
            $m->js()->show()->position(array(
                'my'=>'center top+10',
                'at'=>'center bottom',
                'of'=>$this,
            ),
            $this->js()->_selectorDocument()->one('click',$m->js()->hide()->_enclose())
        )));


        return $m;
    }
}
