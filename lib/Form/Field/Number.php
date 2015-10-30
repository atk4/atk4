<?php
class Form_Field_Number extends Form_Field_Line {

    function setForm($form){
        parent::setForm($form);
        $this->validate('number');
    }
}
