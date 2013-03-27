<?php // vim:ts=4:sw=4:et:fdm=marker
/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * View represents a stand-alone HTML element in your render-tree,
 * by default <div>
 *
 * Most other views would contain a template with many HTML elements,
 * but "View" is for those cases when you need to add an image,
 * div or video tag.
 *
 *  $this->add('View')
 *      ->setElement('a')
 *      ->setAttr('href',$this->api->url('reminder'))
 *      ->addClass('password_reminder')
 *      ->set('Forgot your password?');
 *
 * For a commonly used elements such as "P", "H1" etc
 * you will find dedicated classes inherited from View.
 * 
*/
class View extends AbstractView
{
    /**
     * Change which element is used. 'div' by default, but
     * can be changed with this function
     *
     * @param string $element Any HTML elment
     *
     * @return $this
     */
    function setElement($element)
    {
        $this->template->trySet('element', $element);
        return $this;
    }
    /**
     * Add attribute to element. Previously added attributes are not affected
     *
     * @param string,array $attribute Name of the attribute, or hash
     * @param string       $value     New value of the attribute
     *
     * @return $this
     */
    function setAttr($attribute, $value = null)
    {
        if (is_array($attribute)&&is_null($value)) {
            foreach ($attribute as $a => $b) {
                $this->setAttr($a, $b);
            }
            return $this;
        }
        $this->template->appendHTML('attributes', ' '.$attribute.'="'.$value.'"');
        return $this;
    }
    /** 
     * Add class to element. Previously added classes are not affected. 
     * Multiple classes can also be separated by a space.
     *
     * @param string $class HTML class or array of classes
     *
     * @return $this
     */
    function addClass($class)
    {
        if (is_array($class)) {
            foreach ($class as $c) {
                $this->addClass($class);
            }
            return $this;
        }
        $this->template->append('class', " ".$class);
        return $this;
    }
    /** 
     * Remove class from element, if it was added with addClass or setClass.
     *
     * @param string $class Single class to remove (no spaces)
     *
     * @return $this
     */
    function removeClass($class)
    {
        $cl=' '.$this->template->get('class').' ';
        $cl=str_replace($cl, ' '.$class.' ', ' ');
        $this->template->set('class', trim($cl));
        return $this;
    }
    /** 
     * Replace all classes with a new ones.
     *
     * @param string $class New class (can contain spaces)
     *
     * @return $this
     */
    function setClass($class)
    {
        $this->template->trySet('class', $class);
        return $this;
    }

    /** 
     * Add inline style to element.
     *
     * @param string $property CSS Property
     * @param string $style    CSS Style definition
     *
     * @return $this
     */
    function addStyle($property, $style = null)
    {
        return $this->setStyle($property, $style);
    }

    /** 
     * Same as addStyle
     *
     * @param string $property CSS Property
     * @param string $style    CSS Style definition
     *
     * @return $this
     * @TODO: Align functionality with addClass / setClass
     */
    function setStyle($property, $style = null)
    {
        if (is_null($style) && is_array($property)) {
            foreach ($property as $k => $v) {
                $this->setStyle($k, $v);
            }
            return $this;
        }
        $this->template->append('style', ";".$property.':'.$style);
        return $this;
    }
    /**
     * Sets text to appear inside element. Automatically escapes
     * HTML characters.See also setHTML()
     *
     * @param string $text Text
     *
     * @return $this
     */
    function setText($text)
    {
        $this->template->trySet('Content', $text);
        return $this;
    }
    /**
     * Sets text to appear inside element. Automatically escapes
     * HTML characters.See also setHTML(). Same as setText()
     *
     * @param string $text Text
     *
     * @return $this
     */
    function set($text)
    {
        return $this->setText($text);
    }
    /**
     * Sets HTML to appear inside element. 
     *
     * @param string $html HTML
     *
     * @return $this
     */
    function setHTML($html)
    {
        $this->template->trySetHTML('Content', $html);
        return $this;
    }

    // {{{ Inherited Methods
    function defaultTemplate(){
        return array('htmlelement');
    }
    // }}}
}
