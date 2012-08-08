<?php

class Field extends AbstractModel {
    public $type='string';
    public $readonly=false;
    public $system=false;
    public $hidden=false;
    public $editable=true;
    public $visible=true;
    public $display=null;
    public $caption=null;
    public $group=null;
    public $allowHTML=false;
    public $sortable=false;
    public $searchable=false;
    public $mandatory=false;
    public $defaultValue=null;
    public $emptyText="Please, select";
    public $auto_track_element=true;
    public $listData=null;
    public $theModel=null;  // 

    public $relation=null;
    public $actual_field=null;

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
        if($this->owner->loaded() || isset($this->owner->data[$this->short_name]))return $this->owner->get($this->short_name);
        return $this->defaultValue();
    }
    function __toString() {
        return get_class($this). " ['".$this->short_name."']".' of '. $this->owner ;
    }

    function type($t=undefined){ return $this->setterGetter('type',$t); }
    function caption($t=undefined){ 
        if($t===undefined && !$this->caption)
            return ucwords(strtr(preg_replace('/_id$/','',$this->short_name),'_',' ')); 
        return $this->setterGetter('caption',$t); 
    }
    function group($t=undefined){ return $this->setterGetter('group',$t); }
    function readonly($t=undefined){ return $this->setterGetter('readonly',$t); }
    function mandatory($t=undefined){ return $this->setterGetter('mandatory',$t); }
    function required($t=undefined){ return $this->mandatory($t); }
    function editable($t=undefined){ return $this->setterGetter('editable',$t); }
    function allowHTML($t=undefined){ return $this->setterGetter('allowHTML',$t); }
    function searchable($t=undefined){ return $this->setterGetter('searchable',$t); }
    function sortable($t=undefined){ return $this->setterGetter('sortable',$t); }
    function display($t=undefined){ return $this->setterGetter('display',$t); }
    function actual($t=undefined){ return $this->setterGetter('actual_field',$t); }
    function system($t=undefined){ 
        if($t===true){
            $this->editable(false)->visible(false);
        }
        return $this->setterGetter('system',$t); 
    }
    function hidden($t=undefined){ return $this->setterGetter('hidden',$t); }
    function length($t=undefined){ return $this->setterGetter('length',$t); }
    function defaultValue($t=undefined){ return $this->setterGetter('defaultValue',$t); }
    function visible($t=undefined){ return $this->setterGetter('visible',$t); }
    function listData($t=undefined){ return $this->setterGetter('listData',$t); }
    function emptyText($t=undefined){ return $this->setterGetter('emptyText',$t); }
    function setModel($t=undefined){ return $this->setterGetter('theModel',$t); }
    function getModel(){ return $this->theModel; }
    function setValueList($t){ return $this->listData($t); }
    function enum($t){ return $this->listData(array_combine($t,$t)); }
    /** Binds the field to a realtion (returned by join() function) */
    function from($m){
        if($m===undefined)return $this->relation;
        if(is_object($m)){
            $this->relation=$m;
        }else{
            $this->relations=$this->owner->relations[$m];
        }
        return $this;
    }
    // what is alias?
    //function alias($t=undefined){ return $this->setterGetter('alias',$t); }

    /** Modifies specified query to include this particular field */
    function updateSelectQuery($select){
        $p=null;
        if($this->owner->relations)$p=$this->owner->table_alias?:$this->owner->table;

        if($this->relation){
            $select->field($this->actual_field?:$this->short_name,$this->relation->short_name,$this->short_name);
        }elseif(!(is_null($this->actual_field)) && $this->actual_field != $this->short_name){
            $select->field($this->actual_field,$p,$this->short_name);
            return $this;
        }else{
            $select->field($this->short_name,$p);
        }
        return $this;
    }

    /** Modify insert query to set value of this field */
    function updateInsertQuery($insert){
        if($this->relation)$insert=$this->relation->dsql;

        $insert->set($this->actual_field?:$this->short_name,
            $this->getSQL()
        );
        return $this;
    }
    /** Modify insert query to set value of this field */
    function updateModifyQuery($modify){
        if($this->relation)$modify=$this->relation->dsql;

        $modify->set($this->actual_field?:$this->short_name,
            $this->getSQL()
        );
        return $this;
    }
    /** Converts true/false into boolean representation according to the "enum" */
    function getBooleanValue($value){
        if($this->listData){
            reset($this->listData);
            list($junk,$yes_value)=each($this->listData);
            @list($junk,$no_value)=each($this->listData);
            if($no_value==null)$no_value='';
            /* not to convert N to Y */
            if ($yes_value == $value){
                return $yes_value;
            }
            if ($no_value == $value){
                return $no_value;
            }
        }else{
            $yes_value=1;$no_value=0;
        }

        return $value?$yes_value:$no_value;
    }
    /** Get value of this field formatted for SQL. Redefine if you need to convert */
    function getSQL(){
        $val=$this->owner->get($this->short_name);
        if($this->type=='boolean'){
            $val=$this->getBooleanValue($val);
        }
        if($val=='' && ($this->listData || $this instanceof Field_Reference) && $this->type!='boolean'){
            $val=null;
        }
        return $val;
    }
    /** Returns field of this model */
    function getExpr(){
        $q=$this->owner->_dsql();
        return $q->bt($this->relation?$this->relation->short_name:$q->main_table).'.'.$q->bt($this->short_name);
    }

    /** @obsolete use hasOne instead */
    function refModel($m){
        if($m=='Model_Filestore_File'){
            return $this->add('filestore/Field_File');
        }
        $this->destroy();
        $fld = $this->add('Field_Reference');

        foreach((Array)$this as $key=>$val){
            $fld->$key=$val;
        }
        return $this->owner->add($fld)->setModel(str_replace('Model_','',$m));
    }
    function datatype($v=undefined){ 
        return $this->type($v); 
    }
    function calculated($v=undefined){
        if($v===undefined)return false;
        if($v===false)return $this;

        $this->destroy();
        $fld = $this->add('Field_Expression');

        foreach((Array)$this as $key=>$val){
            $fld->$key=$val;
        }
        return $this->owner->add($fld)->set($v);
    }

}
