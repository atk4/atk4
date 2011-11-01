<?php

class Model_Field extends AbstractModel {
    public $system=false;
    public $hidden=false;
    public $editable=true;
    public $display=null;
    public $caption=null;
    public $defaultValue=null;

    function setterGetter($type,$value=undefined){
        if($value === undefined){
            return $this->$type;
        }
        $this->$type=$value;
        return $this;
    }

    // shortcuts to using access to data
    function set($value=null){
        $this->owner->set($this->short_name,$value);
        return $this;
    }
    function get($type=undefined){
        if($this->owner->loaded())return $this->owner->get($this->short_name);
        return $this->defaultValue();
    }
    function __toString() {
        return get_class($this). " ['".$this->short_name."']".' of '. $this->owner ;
    }

    function type($t=undefined){ return $this->setterGetter('type',$t); }
    function caption($t=undefined){ if(!$this->caption)return ucwords(strtr($this->short_name,'_',' ')); return $this->setterGetter('caption',$t); }
    function readonly($t=undefined){ return $this->setterGetter('readonly',$t); }
    function editable($t=undefined){ return $this->setterGetter('editable',$t); }
    function allowHTML($t=undefined){ return $this->setterGetter('allowHTML',$t); }
    function searchable($t=undefined){ return $this->setterGetter('searchable',$t); }
    function sortable($t=undefined){ return $this->setterGetter('sortable',$t); }
    function display($t=undefined){ return $this->setterGetter('display',$t); }
    function system($t=undefined){ return $this->setterGetter('system',$t); }
    function hidden($t=undefined){ return $this->setterGetter('hidden',$t); }
    function calculated($t=undefined){ return $this->setterGetter('calculated',$t); }
    function length($t=undefined){ return $this->setterGetter('length',$t); }
    function defaultValue($t=undefined){ return $this->setterGetter('defaultValue',$t); }
    function visible($t=undefined){ return $this->setterGetter('visible',$t); }
    function listData($t=undefined){ return $this->setterGetter('listData',$t); }
    // what is alias?
    function alias($t=undefined){ return $this->setterGetter('alias',$t); }

    function updateSelectQuery($select){
        $select->field($this->short_name);
        return $this;
    }
}
