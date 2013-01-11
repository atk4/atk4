<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/* Flyout is a handy view which can be used to display content in 
* frames.The flayout will automatically hide itself and position itself 
* relative to your element */
class View_Flyout extends View {

    public $position='top';
    // can be top, bottom, left or right

    public $offset='';

    function useArrow(){
        $this->addStyle('display','none');
        $this->offset='+10';
        /*
         */
        return $this;
    }

    /* Returns JS which will position this element and show it */
    function showJS($element=null,$options=array()){

        $this->js(true)->dialog(array(
            'modal'=>true,
            'dialogClass'=>'flyout',
            'dragable'=>false,
            'resizable'=>false,
            'autoOpen'=>false,
            'width'=>$options['width']?:250,
            'open'=>$this->js(null, $this->js()->_selector('.ui-dialog-titlebar')->remove())->click(
                $this->js()->dialog('close')->_enclose()
            )->_selector('.ui-widget-overlay:last')->_enclose()->css('opacity','0'),
            'position'=>$p=array(
                'my'=>$options['my']?:'left top',
                'at'=>$options['at']?:'left-5 bottom+5',
                'of'=>$element?:$this->owner,
                //'using'=>$this->js(null,'function(position,data){ $( this ).css( position ); console.log("Position: ",data); var rev={vertical:"horizontal",horizontal:"vertical"}; $(this).find(".arrow").addClass(rev[data.important]+" "+data.vertical+" "+data.horizontal);}')
            )
        ))->parent()->append('<div class="arrow '.($options['arrow']?:'vertical top left').'"></div>');

        return $this->js()->dialog('open');
    }
}
