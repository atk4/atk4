<?php
class Form_Field_Date extends Form_Field {
    private $sep = '-';
    private $is_valid = false;

    /*function getInput($attr=array()){
      return parent::getInput(array_merge(array('type'=>'text',
      'value'=>($this->is_valid ? date('Y-m-d', $this->value) : $this->value)),$attr));
      }*/
    private function invalid(){
        return $this->displayFieldError('Not a valid date');
    }
    function validate(){
        //empty value is ok
        if($this->value==''){
            $this->is_valid=true;
            return parent::validate();
        }
        //checking if there are 2 separators
        if(substr_count($this->value, $this->sep) != 2){
            $this->invalid();
        }else{
            $c = explode($this->sep, $this->value);
            //day must go first, month should be second and a year should be last
            if(strlen($c[0]) != 4 ||
                    $c[1] <= 0 || $c[1] > 12 ||
                    $c[2] <= 0 || $c[2] > 31)
            {
                $this->invalid();
            }
            //now attemting to convert to date
            if(strtotime($this->value)==''){
                $this->invalid();
            }else{
                //$this->set(strtotime($this->value));
                $this->set($this->value);
                $this->is_valid=true;
            }
        }
        return parent::validate();
    }
}
