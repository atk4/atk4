<?php
/***********************************************************
  Add icon from standard icon set of Agile Toolkit

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class View_Icon extends View {
    public $shappe=null;
    function init(){
        parent::init();
        $this->setElement('span');
    }
    /* Sets icon by its shape. http://agiletoolkit.org/ref/icon */
    function setText($shape){
        $this->shape=$shape;
        return $this;
    }
    function render(){
        $this->addClass('icon-'.$this->shape);
        parent::render();
    }

}
