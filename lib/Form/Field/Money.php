<?php
class Form_Field_Money extends Form_Field_Number {
    public $digits = 2;
    function setDigits($n){
        $this->digits = $n;
        return $this;
    }
    function getInput($attr=array()){
        return parent::getInput(array_merge(array(
                'value'=>number_format($this->value,$this->digits)
            ),$attr));
    }
}
