<?php
/**
 * Display standard Warning box.
 */
class View_Warning extends View_Box
{
    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        $this->addClass('atk-effect-warning');
        $this->template->set('label', $this->app->_('Warning').': ');
        $this->addIcon('attention');
    }
}
