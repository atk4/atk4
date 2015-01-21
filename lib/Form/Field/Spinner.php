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
class Form_Field_Spinner extends Form_Field_Number {
    public $min = 0;
    public $max = 10;
    public $step = 1;
    
    function setStep($n){
        $this->step = $n;
        return $this;
    }
    function getInput(){
        $s = $this->name.'_spinner';
        $this->js(true)->_selector('#'.$s)->spinner(array(
                    'min' => $this->min,
                    'max' => $this->max,
                    'step' => $this->step,
                    'value' => $this->js()->val(),
                    'change' => $this->js()->_enclose()->val(
                        $this->js()->_selector('#'.$s)->spinner('value')
                        )->change()
                    ));
        $this->setAttr('style','display: none');
        
        return parent::getInput().'<div id="'.$s.'"></div>';
    }
}
