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
/* Popover is a handy view which can be used to display content in 
* frames.The popover will automatically hide itself and position itself 
* relative to your element */
class View_Popover extends View {

    public $position='top';
    // can be top, bottom, left or right
    //
    function init(){
        parent::init();
        $this->addStyle('display','none');
    }

    /* Returns JS which will position this element and show it */
    function showJS($element=null,$options=array()){

        $this->js(true)->dialog(array_extend(array(
            'modal'=>true,
            'dialogClass'=>$options['class']?:'popover',
            'dragable'=>false,
            'resizable'=>false,
            'minHeight'=>'auto',
            'autoOpen'=>false,
            'width'=>250,
            'open'=>$this->js(null, $this->js()->_selector('.ui-dialog-titlebar:last')->hide())->click(
                $this->js()->dialog('close')->_enclose()
            )->_selector('.ui-widget-overlay:last')->_enclose()->css('opacity','0'),
        ),$options))->parent()->append('<div class="arrow '.($options['arrow']?:'vertical top left').'"></div>')
        ;

        return $this->js()->dialog('open')->dialog('option',array(
            'position'=>$p=array(
                'my'=>$options['my']?:'left top',
                'at'=>$options['at']?:'left-5 bottom+5',
                'of'=>$element
                //'using'=>$this->js(null,'function(position,data){ $( this ).css( position ); console.log("Position: ",data); var rev={vertical:"horizontal",horizontal:"vertical"}; $(this).find(".arrow").addClass(rev[data.important]+" "+data.vertical+" "+data.horizontal);}')
            )
        ));
    }
}

// Deep array extend: http://stackoverflow.com/questions/12725113/php-deep-extend-array
// TODO: merge JS chains by putting them into combined chain.
function array_extend($a, $b) {
    foreach($b as $k=>$v) {
        if( is_array($v) ) {
            if( !isset($a[$k]) ) {
                $a[$k] = $v;
            } else {
                $a[$k] = array_extend($a[$k], $v);
            }
        // } else if $v or $a[$k] instanceof jQuery_Chain, merge them!
        } else {
            $a[$k] = $v;
        }
    }
    return $a;
}
