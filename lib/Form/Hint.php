<?php
/**
 * Undocumented
 */
class Form_Hint extends View_HtmlElement
{
    public function init()
    {
        parent::init();
        $this->setElement('p');
        $this->addClass('atk-text-dimmed');
    }
}
