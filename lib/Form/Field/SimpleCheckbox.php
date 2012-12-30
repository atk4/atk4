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
class Form_Field_SimpleCheckbox extends Form_Field {
    function getInput($attr=array()){
        return parent::getInput(array_merge(
                    array(
                        'type'=>'checkbox',
                        'value'=>'Y',
                        'checked'=>$this->value=='Y'
                         ),$attr
                    ));
    }
    function loadPOST(){
        if(isset($_POST[$this->name])){
            $this->set('Y');
        }else{
            $this->set('');
        }
    }
}
