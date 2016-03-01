<?php
/**
 * Creates multiple buttons without gaps.
 */
class View_ButtonSet extends HtmlElement
{
    // options to pass to buttonset JS widget
    public $options = array();

    // buttonset direction (false = horizontal, true = vertical)
    public $vertical = false;

    /**
     * Initialization
     */
    public function init()
    {
        parent::init();
        $this->addClass('atk-buttonset atk-inline');
    }

    /**
     * Add button to buttonset.
     *
     * @param string $label   Label of button
     * @param array  $options Options to pass to button
     *
     * @return Button
     */
    public function addButton($label = null, $options = array())
    {
        $but = $this->add('Button', $options)->set($label);
        if ($this->vertical) {
            $but->js(true)->css('margin-top', '-3px');
        }

        return $but;
    }

    public function jsButtonSet()
    {
        return;
        if ($this->vertical) {
            $this->js(true)->_load('jquery-ui.buttonset-vertical');
            $this->js(true)->buttonsetv($this->options);
        } else {
            $this->js(true)->buttonset($this->options);
        }
    }
}
