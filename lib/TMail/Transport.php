<?php
/**
 * Generic implementation of TMail transport.
 */
class TMail_Transport extends AbstractController
{
    public function init()
    {
        parent::init();

        $this->owner->addHook('send', array($this, 'send'));
    }
}
