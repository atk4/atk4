<?php
class View_DropButton extends Button {
    function render(){
        if(!isset($this->options['icons']['secondary'])){
            $this->options['icons']['secondary']='ui-icon-triangle-1-s';
        }
        parent::render();
    }
    /* Show menu when clicked */
    function useMenu($width=3){
        $m=$this->owner->add('Menu_jUI',null,$this->owner->spot);
        $m->addStyle('display','none');

        $this->js('click',array(
            $m->js()->show()->position(array(
                'my'=>'left top',
                'at'=>'left bottom',
                'of'=>$this
            ),
            $this->js()->_selectorDocument()->one('click',$m->js()->hide()->_enclose())
        )));

        if($width)$m->addClass('span'.$width);

        return $m;
    }
}
