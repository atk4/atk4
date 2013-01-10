<?php
class Form_Field_Money extends Form_Field_Line {
    function getInput($attr=array()){
        return parent::getInput(array_merge(array('value'=>number_format($this->value,2)),$attr));
    }
}
