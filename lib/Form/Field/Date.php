<?php
class Form_Field_Date extends Form_Field {
    private $sep = '-';

    private function invalid(){
        return $this->displayFieldError('Not a valid date');
    }
    function validate(){
        //empty value is ok
        if($this->value==''){
            return parent::validate();
        }
        //checking if there are exactly 2 separators
        if(substr_count($this->value, $this->sep) != 2){
            $this->invalid();
        }else{
            $c = explode($this->sep, $this->value);
            //year should be first, month should be second and a day should be last
            if(strlen($c[0]) != 4 ||
                    $c[1] <= 0 || $c[1] > 12 ||
                    $c[2] <= 0 || $c[2] > 31)
            {
                $this->invalid();
            }
            //now attempting to convert to date
            if(strtotime($this->value)===false){
                $this->invalid();
            }else{
                $this->set($this->value);
            }
        }
        return parent::validate();
    }
}
