<?php
class Model_Field_Expression extends Model_Field {
    public $expr=null;
    function editable(){
        return false;
    }
    function calculated($expr){
        if(is_string($expr))$expr=array($this->owner,$expr);
        if($expr===true)$expr=array($this->owner,'calculate_'.$this->short_name);

        $this->expr=$expr;
    }
    function updateSelectQuery($select){
        $select->field($select->expr($e=call_user_func($this->expr)),$this->short_name);
    }
}
