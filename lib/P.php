<?php
/**
 * Adds a <p> element.
 *
 * $this->add('H1')->set('Welcome');
 * $this->add('P')->set('Your balance is: '.$balance);
 */
class P extends HtmlElement
{
    public function init()
    {
        parent::init();
        $this->setElement('p');
    }
}
