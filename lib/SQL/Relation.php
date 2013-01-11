<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class SQL_Relation extends AbstractModel {
    public $f1=null;            // Foreign Table (actual name)
    // short_name = Foreign alias

    public $t=null;             // Join kind (left|right|inner|cross etc.)

    public $expr=null;          // Using expression when joining

    public $f2=null;            // Foreign field
    public $m2=null;            // Master field

    public $m1=null;            // Master table (defaults to owner->table / owner->table_alias)
    // $m1 == $relation->f1
    public $relation=null;

    public $delete_behaviour='cascade';          // cascade, setnull, ignore

    function init(){
        parent::init();
        $this->table_alias=$this->short_name;
    }

    /** Second argument to addField() will specify how the field is really called */
    function addField($n,$actual_field=null){
        $f=$this->owner->addField($n,$actual_field)->from($this);
        return $f;
    }
    function join($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null){
        return $this->owner->join($foreign_table, $master_field, $join_kind, $_foreign_alias,$this);
    }
    function leftJoin($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null){
        return $this->owner->leftJoin($foreign_table, $master_field, $join_kind, $_foreign_alias,$this);
    }
    function hasOne($model,$our_field=null,$display_field=null){
        return $this->owner->hasOne($model,$our_field,$display_field)->from($this);
    }
    function hasMany($model,$their_field=null,$our_field=null){
        return $this->owner->hasMany($model,$their_field,$our_field)->from($this);
    }
    // TODO: hasMany()

    function set($foreign_table,$master_field=null,$join_kind=null,$relation=null){
        // http://dev.mysql.com/doc/refman/5.0/en/join.html
        $join_types = array('left','right','inner','cross','natural','left outer','right outer','natural left','natural right','natural left outer','natural right outer');
        if($join_kind && !in_array(strtolower($join_kind),$join_types)) {
            throw $this->exception('Specify reasonable SQL join type.')
                ->addMoreInfo('Specified',$join_kind)
                ->addMoreInfo('Allowed',implode(', ',$join_types));
        }

        $this->relation=$relation;

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

        $this->t=$join_kind?:'inner';
        $this->fa=$this->short_name;

        // Use the real ID field as defined by the model as default
        $this->owner->_dsql()->join($foreign_table,$this->expr?:($m1.'.'.$m2),$this->t,$this->short_name);

        // If our ID field is NOT used, must insert record in OTHER table first and use their primary value in OUR field
        if($this->m2 && $this->m2 != $this->owner->id_field){
            // user.contactinfo_id = contactinfo.id
            $this->owner->addHook('beforeInsert',$this,array(),-5);
            $this->owner->addHook('beforeModify',$this,array(),-5);
            $this->owner->addHook('afterDelete',$this,array(),-5);
        }elseif($this->m2){
            // author.id = book.author_id
            $this->owner->addHook('afterInsert',$this);
            $this->owner->addHook('beforeModify',$this);
            $this->owner->addHook('beforeDelete',$this);
        }// else $m2 is not set, expression is used, so don't try to do anything unnecessary

        $this->owner->addHook('beforeSave',$this);
        $this->owner->addHook('beforeLoad',$this);
        $this->owner->addHook('afterLoad',$this);

        return $this;
    }
    function beforeSave($m){
        $this->dsql=$this->owner->_dsql()->dsql()->table($this->f1);
        if($this->owner->_dsql()->debug)$this->dsql->debug();
    }
    function beforeInsert($m,$q){
        // Insert related table data and add ID into the main query
        // TODO: handle cases when $this->m1 != $this->owner->table?:$this->owner->table_alias
        if($this->delete_behaviour=='ignore')return;

        if($this->owner->hasElement($this->m2) && $this->owner->get($this->m2) !== null){
            return; // using existing element
        }

        $this->dsql->set($this->f2,null);
        $this->id=$this->dsql->insert();

        if($this->relation)$q=$this->relation->dsql;

        $q->set($this->m2,$this->id);
    }
    function afterInsert($m,$id){
        if($this->delete_behaviour=='ignore')return;

        $this->id=$this->dsql->set($this->f2,$this->relation?$this->relation->id?:$id:$id)->insert();
    }
    function beforeModify($m,$q){
        if($this->dsql->args['set'])$this->dsql->where($this->f2,$this->id)->update();
    }
    function beforeDelete($m,$id){
        // Let's hope that cascading sorts this 
        /*
        $this->dsql=$this->owner->_dsql()->dsql()->table($this->f1);
        if($this->owner->_dsql()->debug)$this->dsql->debug();

        if($this->delete_behaviour=='cascade'){
            $this->dsql->del('field')->where($this->f2,$this->id)->debug()->delete();
        }elseif($this->delete_behaviour=='ignore'){
            $this->dsql->del('field')->set($this->f2,null)->where($this->f2,$this->id)->debug()->update();

        }*/
    }
    function afterDelete($m){
        $this->dsql=$this->owner->_dsql()->dsql()->table($this->f1);
        if($this->owner->_dsql()->debug)$this->dsql->debug();

        if($this->delete_behaviour=='cascade'){
            try{
            $this->dsql->del('field')->where($this->f2,$this->id)->delete();
            }catch(Exception $e){
                $this->api->caughtException($e);
            }
        }elseif($this->delete_behaviour=='ignore'){
            //$this->dsql->del('field')->set($this->f2,null)->where($this->f2,$this->id)->update();

        }
    }

    /** Add query for the relation's ID, but then remove it from results. Remove ID when unloading. */
    function beforeLoad($m,$q=null){
        if(is_null($q))return;  // manual load

        if($this->m2 && $this->m2 != $this->owner->id_field){
            $q->field($this->m2,$this->m1,$this->short_name);
        }elseif($this->m2){
            $q->field($this->f2,$this->fa?:$this->f1,$this->short_name);
        }
    }
    function afterLoad($m){
        $this->id=$m->data[$this->short_name];
        unset($m->data[$this->short_name]);
    }
    function afterUnload($m){
        $this->id=null;
    }

    function fieldExpr($f){
        return $this->owner->_dsql()->expr(
            $this->owner->_dsql()->bt($this->short_name).
            '.'.
            $this->owner->_dsql()->bt($f)
        );
    }
}
