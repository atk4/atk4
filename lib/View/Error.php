<?php
/**
 * Implementation of Error Message Box
 *
 * Use: 
 *  $this->add('View_Error')->set('Error Text');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class View_Error extends View_Box {
    public $class="ui-state-error";
    function init(){
        parent::init();
        $this->template->trySetHTML('Icon','<i class="ui-icon ui-icon-alert"></i>');    // change default icon
    }
}
