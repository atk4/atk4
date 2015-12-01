<?php
class Form_Field_Text extends Form_Field {
    public $rows = 5;
    
    function init(){
        $this->setAttr('rows',$this->rows);
        parent::init();
    }
    function setRows($n){
        $this->rows = $n;
        $this->setAttr('rows',$n);
        return $this;
    }
    function getInput($attr=array()){
        return
            parent::getInput(array_merge(array(''=>'textarea'),$attr)).
            $this->app->encodeHtmlChars($this->value).
            $this->getTag('/textarea');
    }
}
