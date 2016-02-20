<?php
/**
 * Undocumented
 */
class Layout_Footer extends View
{
    public function init()
    {
        parent::init();
        $this->setElement('footer');
    }
    public function getJSID()
    {
        return 'atk-footer';
    }
}
