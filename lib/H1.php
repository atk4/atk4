<?php
/**
 * Shortcut class for using HTML heading.
 */
class H1 extends HX
{
    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();
        $this->setElement('H1');
    }
}
