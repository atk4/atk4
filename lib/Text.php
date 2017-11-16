<?php
/**
 * Very simple object for adding Text anywhere. Particularly
 * useful to avoid loosing text you would have set directly into
 * template when adding other object, such as icon.
 *  $page->add('Icon');
 *  $page->add('Text');
 */
class Text extends AbstractView
{
    public $text = 'Your text goes here......';
    
    public $html = null;
    
    /** Set text. Will be HTML-escaped during render */
    public function set($text)
    {
        $this->text = $text;
        $this->html = null;

        return $this;
    }

    /** Set HTML. Will be outputed as specified. */
    public function setHtml($html)
    {
        $this->text = null;
        $this->html = $html;
    }
    
    public function render()
    {
        $this->output($this->html ?: $this->app->encodeHtmlChars($this->text, ENT_NOQUOTES));
    }
}
