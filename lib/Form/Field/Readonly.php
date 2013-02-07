<?php
class Form_Field_Readonly extends Form_Field {
    function init(){
        parent::init();
        $this->setNoSave();
    }

    function getInput($attr=array()){
        return nl2br(isset($this->value_list) ? $this->value_list[$this->value] : $this->value);
    }
    function loadPOST(){
        // do nothing, readonly field
    }
    function setValueList($list){
        $this->value_list = $list;
        return $this;
    }

}
