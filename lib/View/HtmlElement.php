<?php // vim:ts=4:sw=4:et:fdm=marker
/**
  HtmlElement is a base class of any View which would render as a
  single HTML element. By default it puts <div> on your page, but
  you can change the element with setElement()

  Many other views such as H1. H2, P, are based on this.

  Learn:
  http://agiletoolkit.org/learn/understand/view/usage

  Reference:
  http://agiletoolkit.org/doc/htmlelement
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2011 Romans Malinovskis <atk@agiletech.ie>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class View_HtmlElement extends View {
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
        $this->template->append('attributes',' '.$attribute.'="'.$value.'"');
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
    /** Sets text appearing inside element */
    function setText($text){
        $this->template->trySet('Content',$text);
        return $this;
    }
    /** Alias for setText. */
    function set($text){
        return $this->setText($text);
    }
    function defaultTemplate(){
        return array('htmlelement');
    }
}
