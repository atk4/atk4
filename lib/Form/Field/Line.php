<?php
class Form_Field_Line extends Form_Field {
    function getInput($attr=array()){
        return parent::getInput(array_merge(array('type'=>'text'),$attr));
    }
}
