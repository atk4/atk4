<?php
/**
 * Undocumented.
 */
class View_DropButton extends Button
{
    /**
     * Render button.
     */
    public function render()
    {
        if (!isset($this->options['icons']['secondary'])) {
            $this->options['icons']['secondary'] = $this->js_triangle_class;
        }
        parent::render();
    }
}
