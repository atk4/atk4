<?php
/**
 * Undocumented.
 */
class Html extends AbstractView
{
    // HTML message text
    public $html = 'Your text goes <u>here</u>......';

    /**
     * Set HTML text.
     *
     * @param string $html HTML text
     *
     * @return $this
     */
    public function set($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Render.
     */
    public function render()
    {
        $this->output($this->html);
    }

    /**
     * Initialize template.
     */
    public function initializeTemplate()
    {
        $this->spot = $this->defaultSpot();
    }
}
