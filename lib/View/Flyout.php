<?php
/* Flyout is a handy view which can be used to display content in 
* frames.The flayout will automatically hide itself and position itself 
* relative to your element */
class View_Flyout extends View {

    public $position='top';
    // can be top, bottom, left or right

    public $offset='';

    function init(){
        parent::init();
        $this->addClass('flyout ui-widget-content ui-corner-all');
        $this->addStyle('display','none');
        $this->addStyle('position','absolute');
    }

    function useArrow(){
        $this->offset='+10';
        $this->add('View')->setClass('arrow '.$this->position);
    }

    /* Returns JS which will position this element and show it */
    function showJS($element=null){
        $js = $this->js()->_selectorDocument()->one('click',$this->js()->hide()->_enclose());

        return $this->js(null, $js)->show()->position(array(
            'my'=>'center '.$this->position.$this->offset,
            'at'=>'center bottom',
            'of'=>$element?:$this->owner,
        ));
    }
}
