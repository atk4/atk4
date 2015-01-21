<?php
class Form_Field_Search extends Form_Field {
    // WARNING: <input type=search> is safari extention and will not validate as valid HTML
    function getInput($attr=array()){
        return parent::getInput(array_merge(array('type'=>'search'),$attr));
    }
}
