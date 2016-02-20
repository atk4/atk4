<?php
/**
 * Shortcut class for using HTML heading.
 */
class H3 extends HX
{
    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();
        $this->setElement('H3');
    }
}
