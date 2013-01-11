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
class HX extends HtmlElement {
    public $text=null;
    public $sub=null;
    function set($text){
        $this->text=$text;
        return parent::set($text);
    }
    /** Adds subtitle */
    function sub($text){
        $this->sub=$text;
        return $this;
    }
    function recursiveRender(){
        if(!is_null($this->sub)){
            $this->add('Text')->set($this->text);
            $this->add('HtmlElement')->setElement('small')->set($this->sub);
        }
        parent::recursiveRender();
    }
}
