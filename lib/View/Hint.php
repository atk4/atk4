<?php
/**
 * Standard hints. Add on page or forms
 */
class View_Hint extends View_Box
{
    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        $this->addClass('atk-effect-info');
        $this->template->set('label', $this->app->_('Hint').': ');
        $this->addIcon('info');
    }

    public function setTitle($title)
    {
        $this->template->set('label', $title);

        return $this;
    }
}
