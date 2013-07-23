<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Adds a support for subtitles for <h1>, <h2>, <h3>, <h4> and <h5>
 * elements
 *
 * $this->add('H1')->set('Welcome')
 *      ->sub('we really mean it!');
 *
 * Syntactically, subtitle appears inside the <h1> element:
 *
 * <h1>Hello<sub>world</sub></h1>
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
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
    function sub($text)
    {
        $this->sub = $text;
        return $this;
    }

    // {{{ Inherited Methods
    function set($text)
    {
        $this->text = $text;
        return parent::set($text);
    }

    function recursiveRender()
    {
        if (!is_null($this->sub)) {
            $this->add('Text')->set($this->text);
            $this->add('HtmlElement')->setElement('small')->set($this->sub);
        }
        parent::recursiveRender();
    }
    //}}}
}
