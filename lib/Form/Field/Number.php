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
        // empty value is allowed, there should be == instead of ===
        if($this->value == ''){
            return parent::validate();
        }
        
        if(!is_numeric($this->value)) {
            $this->displayFieldError('Not a valid number');
        }
        if( ($this->min!==null && $this->value < $this->min) ||
            ($this->max!==null && $this->value > $this->max)){
            $this->displayFieldError('Number not in valid range');
        }
        return parent::validate();
    }
    /**
     * Normalize POSTed data
     *
     * @return void
     */
    function normalize()
    {
        // empty value is not correct value for numeric field
        if ($this->get()==='') {
            $this->set(null);
        }
        return parent::normalize();
    }
}
