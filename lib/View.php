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
 * but "View" is for those cases when you need to add an image, simple
 * div or video tag, for example.
 *
 *  $this->add('View')
 *      ->setElement('a')
 *      ->setAttr('href', $this->api->url('reminder'))
 *      ->addClass('password_reminder')
 *      ->set('Forgot your password?');
 *
 * For a commonly used elements such as "P", "H1" etc.
 * you will find dedicated classes inherited from View.
 */
class View extends AbstractView
{
    /**
     * Change which HTML element is used. 'div' will be used by default.
     *
     * @param string $element Any HTML element
     *
     * @return $this
     * @TODO: Imants: I believe we should use set() here not trySet()
     */
    function setElement($element)
    {
        $this->template->trySet('element', $element);
        return $this;
    }
    /**
     * Add attribute to element. Previously added attributes are not affected.
     *
     * @param string|array $attribute Name of the attribute, or hash
     * @param string       $value     New value of the attribute
     *
     * @return $this
     */
    function setAttr($attribute, $value = null)
    {
        if (is_array($attribute) && is_null($value)) {
            foreach ($attribute as $k => $v) {
                $this->setAttr($k, $v);
            }
            return $this;
        }
        $this->template->appendHTML('attributes', ' '.$attribute.'="'.$value.'"');
        return $this;
    }


    /**
     * Replace all CSS classes with new ones.
     * Multiple CSS classes can also be set if passed as space separated
     * string or array of class names.
     *
     * @param string|array $class CSS class name or array of class names
     *
     * @return $this
     */
    function setClass($class)
    {
        $this->template->del('class');
        $this->addClass($class);
        return $this;
    }
    /**
     * Add CSS class to element. Previously added classes are not affected.
     * Multiple CSS classes can also be added if passed as space separated
     * string or array of class names.
     *
     * @param string|array $class CSS class name or array of class names
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
     * Agile Toolkit CSS now supports concept of Components. Using this method
     * you can define various components for this element:
     *
     * addComponents( [ 'size'=>'mega', 'swatch'=>'green' ] );
     */
    function addComponents(array $class, $append_to_tag='class')
    {
        foreach($class as $key=>$value){
            if(is_array($value)){
                continue;
            }
            if($value === true){
                $this->template->append($append_to_tag, ' atk-'.$key);
                continue;
            }
            $this->template->append($append_to_tag, ' atk-'.$key.'-'.$value);
        }
        return $this;
    }

    /**
     * Remove CSS class from element, if it was added with setClass
     * or addClass.
     *
     * @param string $class Single CSS class name to remove
     *
     * @return $this
     */
    function removeClass($class)
    {
        $cl = ' '.$this->template->get('class').' ';
        $cl = str_replace(' '.trim($class).' ', ' ', $cl);
        $this->template->set('class', trim($cl));
        return $this;
    }

    /**
     * Set inline CSS style of element. Old styles will be removed.
     * Multiple CSS styles can also be set if passed as array.
     *
     * @param string|array $property CSS Property or hash
     * @param string       $style    CSS Style definition
     *
     * @return $this
     */
    function setStyle($property, $style = null)
    {
        $this->template->del('style');
        $this->addStyle($property, $style);
        return $this;
    }

    /**
     * Add inline CSS style to element.
     * Multiple CSS styles can also be set if passed as array.
     *
     * @param string|array $property CSS Property or hash
     * @param string       $style    CSS Style definition
     *
     * @return $this
     */
    function addStyle($property, $style = null)
    {
        if (is_array($property) && is_null($style)) {
            foreach ($property as $k => $v) {
                $this->addStyle($k, $v);
            }
            return $this;
        }
        $this->template->append('style', ';'.$property.':'.$style);
        return $this;
    }
    /**
     * Remove inline CSS style from element, if it was added with setStyle
     * or addStyle.
     *
     * @param string $property CSS Property to remove
     *
     * @return $this
     */
    function removeStyle($property)
    {
        // get string or array of style tags added
        $st = $this->template->get('style');

        // if no style, do nothing
        if (!$st) {
            return $this;
        }

        // if only one style, then put it in array
        if (!is_array($st)) {
            $st = array($st);
        }

        // remove all styles and set back the ones which don't match property
        $this->template->del('style');
        foreach ($st as $k=>$rule) {
            if (strpos($rule, ';'.$property.':') === false) {
                $this->template->append('style', $rule);
            }
        }

        return $this;
    }

    /**
     * Sets text to appear inside element. Automatically escapes
     * HTML characters. See also setHTML().
     *
     * 4.3: You can now pass array to this, which will cleverly
     * affect components of this widget and possibly assign
     * icon
     *
     * @param string $text Text
     *
     * @return $this
     */
    function set($text)
    {

        if(!is_array($text))return $this->setText($text);

        if(!is_null($text[0]))$this->setText($text[0]);

        // If icon is defined, it will either insert it into
        // a designated spot or will combine it with text
        if($text['icon']){

            if($this->template->hasTag('icon')) {
                $this->add('Icon',null,'icon')
                    ->set($text['icon']);
            }else{
                $this->add('Icon')->set($text['icon']);
                if($text[0]){
                    $this->add('HTML')->set('&nbsp;');
                    $this->add('Text')->set($text[0]);
                }
            }

            unset($text['icon']);
        }
        if($text['icon-r']){

            if($this->template->hasTag('icon-r')) {
                $this->add('Icon',null,'icon')
                    ->set($text['icon']);
            }else{
                if($text[0]){
                    $this->add('Text')->set($text[0]);
                    $this->add('HTML')->set('&nbsp;');
                }
                $this->add('Icon')->set($text['icon-r']);
            }

            unset($text['icon']);
        }
        if($text[0])unset($text[0]);

        // for remaining items - apply them as components
        if($text){
            $this->addComponents($text);
        }

        return $this;
    }
    /**
     * Sets text to appear inside element. Automatically escapes
     * HTML characters. See also setHTML().
     *
     * @param string $text Text
     *
     * @return $this
     * @TODO: Imants: I believe we should use set() here not trySet()
     */
    function setText($text)
    {
        $this->template->trySet('Content', $this->api->_($text));
        return $this;
    }
    /**
     * Sets HTML to appear inside element. Don't escape HTML characters.
     *
     * @param string $html HTML markup
     *
     * @return $this
     * @TODO: Imants: I believe we should use setHTML() here not trySetHTML()
     */
    function setHTML($html)
    {
        $this->template->trySetHTML('Content', $html);
        return $this;
    }

    // {{{ Inherited Methods
    /**
     * Set default template to htmlelement.html
     *
     * @return array
     */
    function defaultTemplate(){
        return array('htmlelement');
    }
    // }}}
}
