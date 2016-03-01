<?php
/**
 * Adds standard box with success message
 */
class View_Success extends View_Box
{
    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        $this->addClass('atk-effect-success');
        $this->addIcon('thumbs-up');
    }
}
