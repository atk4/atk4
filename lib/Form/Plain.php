<?php
/***********************************************************
  ..

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
/* Implementation of plain HTML-only form. Does not support submission or anything */
class Form_Plain extends HtmlElement {
    function init(){
        parent::init();
        $this->setElement('form');
    }
    function addInput($type,$name,$value,$tag='Content'){
        $f=$this->add('HtmlElement',$name,$tag);
        $f->setElement('input');
        $f->setAttr(array('type'=>$type,'value'=>$value));
        $f->set('');
        return $f;
    }
}
