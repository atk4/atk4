<?php
/**
 * Add HTML horizontal-ruler <hr> element.
 *
 * @uses     HtmlElement
 *
 * @category UI
 *
 * @author   Romans Malinovskis <romans@agiletoolkit.org>
 * @license  AGPL http://agiletoolkit.org/license
 *
 * @link     http://agiletoolkit.org/
 */
class HR extends HtmlElement
{
    /**
     * HR places a.
     */
    public function init()
    {
        parent::init();
        $this->setElement('hr')->set('');
    }
}
