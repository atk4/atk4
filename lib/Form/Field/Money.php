<?php
class Form_Field_Money extends Form_Field_Number {
    public $digits = 2;
    function setDigits($n){
        $this->digits = $n;
        return $this;
    }
	function normalize(){
		$v = $this->get();
        // remove non-numbers
		$v = preg_replace('/[^-0-9\.]/','', $v);
		$this->set($v);
	}
    function getInput($attr=array()){
        return parent::getInput(array_merge(array(
                'value'=>round($this->value,$this->digits)
            ),$attr));
    }
}
