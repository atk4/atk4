<?php
/**
 * Add HTML horizontal-ruler <hr> element.
 *
 * @author   Romans Malinovskis <romans@agiletoolkit.org>
 */
class HR extends HtmlElement
{
    public function init()
    {
        parent::init();
        $this->setElement('hr')->set('');
    }
}
