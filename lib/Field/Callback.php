<?php

class Field_Callback extends Field {
    public $callback=null;
    public $initialized=false;
    function init(){
        parent::init();
        $this->editable(false);
    }
    function set($callback){
        $this->callback=$callback;
        return $this;
    }
    function updateSelectQuery($select){
        $this->initialized=true;
        $this->owner->addHook('afterLoad',$this);
    }
    function afterLoad($m){
        $result=call_user_func($this->callback,$this->owner,$this);
        $this->owner->set($this->short_name,$result);
        return $this;
    }
    function updateInsertQuery($insert){
        return $this;
    }
    function updateModifyQuery($insert){
        return $this;
    }

}
