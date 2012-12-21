<?php
/* Flyout is a handy view which can be used to display content in 
* frames.The flayout will automatically hide itself and position itself 
* relative to your element */
class View_Flyout extends View {

    public $position='top';
    // can be top, bottom, left or right

    public $offset='';

    function useArrow(){
        $this->offset='+10';
        /*
        $this->add('View')->setClass('arrow '.$this->position);
         */
        return $this;
    }

    /* Returns JS which will position this element and show it */
    function showJS($element=null){
        $this->js(true)->dialog(array(
            'modal'=>true,
            'dragable'=>false,
            'resizable'=>false,
            'autoOpen'=>false,
            'width'=>500,
            'open'=>$this->js(null, $this->js()->_selector('.ui-dialog-titlebar')->remove())->click(
                $this->js()->dialog('close')->_enclose()
            )->_selector('.ui-widget-overlay:last')->_enclose(),
            'position'=>array(
                'my'=>'center '.$this->position.$this->offset,
                'at'=>'center bottom',
                'of'=>$element?:$this->owner,
                'using'=>$this->js(null,'function(position,data){ $( this ).css( position ); $(this).addClass("arrow-"+data[data.important]);}')
            )
        ));

        return $this->js()->dialog('open');
    }
}
