<?php
/***********************************************************
  Very simple object for adding Text anywhere. Particularly
  useful to avoid loosing text you would have set directly into
  template when adding other object, such as icon.
   $page->add('Icon');
   $page->add('Text');

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
class Text extends AbstractView {
    public $text='Your text goes here......';
    public $html=null;
    /** Set text. Will be HTML-escaped during render */
    function set($text){
        $this->text=$text;
        $this->html=null;
        return $this;
    }
    /** Set HTML. Will be outputed as specified. */
    function setHtml($html){
        $this->text=null;
        $this->html=$html;
    }
    function render(){
        $this->output($this->html?:htmlentities($this->text));
    }
    function initializeTemplate(){
        $this->spot=$this->defaultSpot();
    }
}
