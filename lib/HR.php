<?php
/**
 * hello
 *
 * @category UI
 * @package  AgileToolkit
 * @author   Romans Malinovskis <romans@agiletoolkit.org>
 * @license  AGPL or Commercial
 * @link     http://agiletoolkit.org/
 */


/**
 * Add HTML horizontal-ruler <hr> element
 *
 * @uses     HtmlElement
 * @category UI
 * @package  AgileToolkit
 * @author   Romans Malinovskis <romans@agiletoolkit.org>
 * @license  AGPL http://agiletoolkit.org/license
 * @link     http://agiletoolkit.org/
 */
class HR extends HtmlElement
{
    /**
     * HR places a
     *
     * @access public
     * @return void
     */
    function init()
    {
        parent::init();
        $this->addClass('hr')->set('');
    }
}
