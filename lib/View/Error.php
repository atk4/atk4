<?php
/**
 * Implementation of Error Message Box.
 *
 * Use:
 *  $this->add('View_Error')->set('Error Text');
 */
class View_Error extends View_Box
{
    public function init()
    {
        parent::init();
        $this->addClass('atk-effect-danger');
        $this->template->set('label', $this->app->_('Error').': ');
        $this->addIcon('attention');
    }
}
