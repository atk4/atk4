<?php
/**
 * Adds a support for subtitles for <h1>, <h2>, <h3>, <h4> and <h5>
 * elements.
 *
 * $this->add('H1')->set('Welcome')
 *      ->sub('we really mean it!');
 *
 * Syntactically, subtitle appears inside the <h1> element:
 *
 * <h1>Hello<sub>world</sub></h1>
 */
class HX extends HtmlElement
{
    // Heading text
    public $text = null;

    // Subtitle text
    public $sub = null;

    /**
     * Adds subtitle to your header.
     *
     * @param string $text Subheader text
     *
     * @return $this
     */
    public function sub($text)
    {
        $this->sub = $text;

        return $this;
    }

    // {{{ Inherited Methods
    public function set($text)
    {
        $this->text = $text;

        return parent::set($text);
    }

    public function recursiveRender()
    {
        if (!is_null($this->sub)) {
            $this->add('Text')->set($this->text);
            $this->add('HtmlElement')->setElement('small')->set($this->sub);
        }
        parent::recursiveRender();
    }
    //}}}
}
