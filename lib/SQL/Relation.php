<?php
class SQL_Relation extends AbstractModel {
    public $f1=null;            // Foreign Table (actual name)
    // short_name = Foreign alias

    public $t=null;             // Join kind

    public $expr=null;          // Using expression when joining

    public $f2=null;            // Foreign field
    public $m2=null;            // Master field

    public $m1=null;            // Master table (defaults to owner->table / owner->table_alias)
    // $m1 == $relation->f1
    public $relation=null;

    function init(){
        parent::init();
    }

    function addField($n){
        return $this->owner->addField($n)->from($this);
    }
	function join($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null){
        return $this->owner->join($foreign_table, $master_field, $join_kind, $_foreign_alias)
            ->relation($this);
    }
    function relation($relation){
        $this->relation=$relation;
        return $this;
    }

    function set($foreign_table,$master_field=null,$join_kind=null){


        // Split and deduce fields
        list($f1,$f2)=explode('.',$foreign_table,2);

        if(is_object($master_field)){
            $this->expr=$master_field;
        }else{
            $m1=$this->relation?:$this->owner;
            $m1=$m1->table_alias?:$m1->table;

            // Split and deduce primary table
            $m2=$master_field;

            // Identify fields we use for joins
            if(is_null($f2) && is_null($m2))$m2=$f1.'_id';
            if(is_null($m2))$m2=$this->owner->id_field;
            $this->f1=$f1;
            $this->m1=$m1;
            $this->m2=$m2;
        }
        if(is_null($f2))$f2='id';
        $this->f2=$f2;

        $jthis->t=$join_kind?:'left';
        $this->fa=$this->short_name;

        // Use the real ID field as defined by the model as default
        $this->owner->dsql->join($foreign_table,$this->expr?:($m1.'.'.$m2),$join_kind,$this->short_name);

        // If our ID field is NOT used, must insert record in OTHER table first and use their primary value in OUR field
        if($this->m2 && $this->m2 != $this->owner->id_field){
            // user.contactinfo_id = contactinfo.id
            $this->owner->addHook('beforeInsert',$this,null,-5);
            $this->owner->addHook('beforeModify',$this,null,-5);
        }elseif($this->m2){
            // author.id = book.author_id
            $this->owner->addHook('afterInsert',$this);
            $this->owner->addHook('beforeModify',$this);
        }// else $m2 is not set, expression is used, so don't try to do anything unnecessary

        $this->owner->addHook('beforeSave',$this);

        return $this;
    }
    function beforeSave($m){
        $this->dsql=$this->owner->dsql->dsql()->table($this->f1);
        if($this->owner->dsql->debug)$this->dsql->debug();
    }
    function beforeInsert($m,$q){
        // Insert related table data and add ID into the main query
        // TODO: handle cases when $this->m1 != $this->owner->table?:$this->owner->table_alias
        $this->dsql->set($this->f2,null);
        $this->id=$this->dsql->do_insert();

        if($this->relation)$q=$this->relation->dsql;

        $q->set($this->m2,$this->id);
    }
    function afterInsert($m,$id){
        $this->id=$this->dsql->set($this->f2,$id)->do_insert();
    }
    function beforeModify($m,$q){
        if($this->dsql->args['set'])$this->dsql->where($this->f2,$this->id)->do_update();
    }

    /** Add query for the relation's ID, but then remove it from results. Remove ID when unloading. */
    function beforeLoad($m,$q){
        if($this->m2 && $this->m2 != $this->owner->id_field){
            $q->field($this->m1.'.'.$this->m2,$this->short_name);
        }elseif($this->m2){
            $q->field($this->f1.'.'.$this->f2,$this->short_name);
        }
    }
    function afterLoad($m){
        $this->id=$m->data[$this->short_name];
        unset($m->data[$this->short_name]);
    }
    function afterUnload($m){
        $this->id=null;
    }
}
