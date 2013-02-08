<?php
class Form_Field_Number extends Form_Field_Line {
    function validate(){
        if($this->value && !is_numeric($this->value)) {
            $this->displayFieldError('Not a valid number');
        }
        return parent::validate();
    }
}
