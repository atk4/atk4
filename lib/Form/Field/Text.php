<?php
class Form_Field_Text extends Form_Field {
    function init(){
        $this->attr=array('rows'=>5);
        parent::init();
    }
    function getInput($attr=array()){
        return
            parent::getInput(array_merge(array(''=>'textarea'),$attr)).
            htmlspecialchars($this->value,ENT_COMPAT,'ISO-8859-1',false).
            $this->getTag('/textarea');
    }
}
