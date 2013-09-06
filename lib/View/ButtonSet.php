<?php
/**
 * Creates multiple buttons without gaps.
 *
 * Reference:
 * http://agiletoolkit.org/doc/ref
 */
/*==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class View_ButtonSet extends HtmlElement
{
    // options to pass to buttonset JS widget
    public $options = array();
    
    // buttonset direction (false = horizontal, true = vertical)
    public $vertical = false;



    /**
     * Add button to buttonset
     *
     * @param string $label Label of button
     * @param array $options Options to pass to button
     *
     * @return Button
     */
    function addButton($label = null, $options = array())
    {
        $but = $this->add('Button', $options)->setLabel($label);
        if ($this->vertical) {
            $but->js(true)->css('margin-top', '-3px');
        }
        return $but;
    }

    function jsButtonSet() {
        if ($this->vertical) {
            $this->js(true)->_load('jquery-ui.buttonset-vertical');
            $this->js(true)->buttonsetv($this->options);
        } else {
            $this->js(true)->buttonset($this->options);
        }
    }

    /**
     * Render
     *
     * @return void
     */
    function render()
    {
        $this->jsButtonSet();
        parent::render();
    }
}
