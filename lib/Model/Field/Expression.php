<?php
class Model_Field_Expression extends Model_Field {
    public $expr=null;
    function editable(){
        return false;
    }
    function calculated($expr=undefined){
        if($expr===undefined)return true;
        if(is_string($expr))$expr=array($this->owner,$expr);
        if($expr===true)$expr=array($this->owner,'calculate_'.$this->short_name);

        $this->expr=$expr;
        return $this;
    }
    function updateSelectQuery($select){
        $e=call_user_func($this->expr,$select);
        if($e instanceof DB_dsql){
            return $select->field($e,$this->short_name);
        }
        return $select->field($select->expr($e), $this->short_name);
    }
}
