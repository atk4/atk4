<?php
/***********************************************************
  Creates multiple buttons without gaps.

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
class View_ButtonSet extends HtmlElement
{
    public $options = array();
    public $vertical = false;

    function setVertical($true=true) {
        $this->vertical = $true;
        return $this;
    }
    
    function addButton($label=null, $options = array())
    {
        $button=$this->add('Button', $options);
        if($label)$button->setLabel($label);
        return $button;
    }
    
    function render()
    {
        if ($this->vertical) {
            $this->js(true)->_load('jquery-ui.buttonset-vertical');
            $this->js(true)->buttonsetv($this->options);
        } else {
            $this->js(true)->buttonset($this->options);
        }
        parent::render();
    }
}
