<?php
class Form_Field_Number extends Form_Field_Line {
    public $min = null;
    public $max = null;
    
    function setRange($min,$max){
        $this->min = $min;
        $this->max = $max;
        return $this;
    }
    function validate(){
        // empty value is allowed
        if($this->value!=''){
            if(!is_numeric($this->value)) {
                $this->displayFieldError('Not a valid number');
            }
            if( ($this->min!==null && $this->value < $this->min) ||
                ($this->max!==null && $this->value > $this->max)){
                $this->displayFieldError('Number not in valid range');
            }
        }
        return parent::validate();
    }
}
