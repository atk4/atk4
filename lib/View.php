<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * HtmlElement is a base class of any View which would render as a
 * single HTML element. By default it puts <div> on your page, but
 * you can change the element with setElement()
 *
 * Use: 
 *  $tabs=$this->add('View')->set('Hello')->addClass('myclass');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class View extends AbstractView {
    /** Change which element is used. 'div' by default, but change with this funciton */
    function setElement($element){
        $this->template->trySet('element',$element);
        return $this;
    }
    /** Add attribute to element. Also supports hash for multiple attributes */
    function setAttr($attribute,$value=null){
        if(is_array($attribute)&&is_null($value)){
            foreach($attribute as $a=>$b)$this->setAttr($a,$b);
            return $this;
        }
        $this->template->appendHTML('attributes',' '.$attribute.'="'.$value.'"');
        return $this;
    }
    /** Add class to element. */
    function addClass($class){
        if(is_array($class)){
            foreach($class as $c)$this->addClass($class);
            return $this;
        }
        $this->template->append('class'," ".$class);
        return $this;
    }
    function removeClass($class){
        $cl=' '.$this->template->get('class').' ';
        $cl=str_replace($cl,' '.$class.' ',' ');
        $this->template->set('class',trim($cl));
        return $this;
    }
    function setClass($class){
        $this->template->trySet('class', $class);
        return $this;
    }
    /** Add style to element. */
    function setStyle($property,$style=null){
        if(is_null($style)&&is_array($property)){
            foreach($property as $k=>$v)$this->setStyle($k,$v);
            return $this;
        }
        $this->template->append('style',";".$property.':'.$style);
        return $this;
    }
    /** Add style to element */
    function addStyle($property,$style=null){
        return $this->setStyle($property,$style);
    }
    /** Sets text appearing inside element. Automatically escapes HTML characters */
    function setText($text){
        $this->template->trySet('Content',$text);
        return $this;
    }
    /** Alias for setText. Escapes HTML characters. */
    function set($text){
        return $this->setText($text);
    }
    /** Sets HTML */
    function setHtml($html){
        $this->template->trySetHTML('Content',$html);
        return $this;
    }
    function defaultTemplate(){
        return array('htmlelement');
    }
}
