<?php
/**
 * Shortcut class for using HTML heading.
 */
class H2 extends HX
{
    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();
        $this->setElement('H2');
    }
}
