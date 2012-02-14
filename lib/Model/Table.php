<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implementation of a Relational SQL-backed Model
 * @link http://agiletoolkit.org/doc/modeltable
 *
 * Model_Table allows you to take advantage of relational SQL database without neglecting
 * powerful functionality of your RDBMS. On top of basic load/save/delete operations, you can
 * pefrorm multi-row operations, traverse relations, or use SQL expressions
 *
 * The $table property of Model_Table always contains the primary table. The $this->id will
 * always correspond with ID field of that table and when inserting record will always be
 * placed inside primary table first.
 *
 * Use:
 * class Model_User extends Model_Table {
 *     public $table='user';
 *     function init(){
 *         parent::init();
 *         $this->addField('name');
 *         $this->addField('email');
 *     }
 * }
 *
 *
 * // Creates new user, but looks for email duplicates before inserting
 * $user=$this->add('Model_User');
 * $user->loadBy('email',$email);
 *
 * if(!$user->loaded()){
 *     $user->unload();
 *     return $user->set('email',$email)->save();
 * }else throw $user->exception('User with such email already exists');
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class Model_Table extends Model {

    /** Master DSQL record which will be cloned by other operations */
    public $dsql; 

    /** The actual ID field of the table might now always be "id" */
    public $id_field='id';   // name of ID field

    public $title_field='name';  // name of descriptive field. If not defined, will use table+'#'+id

    /** If you wish that alias is used for the table when selected, you can define it here.
     * This will help to keep SQL syntax shorter, but will not impact functionality */
    public $table_alias=null;   // Defines alias for the table, can improve readability of queries
    public $entity_code=null;   // @osolete. Use $table

    // {{{ Basic Functionality, query initialization and actual field handling
   
    /** Initialization of ID field, which must always be defined */
    function init(){
        parent::init();

        $this->initQuery();
        if($d=$_GET[$this->name.'_debug']){
            if($d=='query')$this->debug();
        }

        if($this->owner instanceof Field_Reference && $this->owner->owner->relations){
            $this->relations =& $this->owner->owner->relations;
        }

        $this->addField($this->id_field)->system(true);
    }
    /** exception() will automatically add information about current model and will allow to turn on "debug" mode */
    function exception(){
        return call_user_func_array(array('parent',__FUNCTION__), func_get_args())
            ->addThis($this)
            ->addAction('Debug this Model',array($this->name.'_debug'=>'query'));
    }
    /** Initializes base query for this model. 
     * @link http://agiletoolkit.org/doc/modeltable/dsql */
    function initQuery(){
        $this->dsql=$this->api->db->dsql();
        $table=$this->table?:$this->entity_code;
        if(!$table)throw $this->exception('$table property must be defined');
        $this->dsql->table($table,$this->table_alias);
        $this->dsql->default_field=$this->dsql->expr('*,'.
            $this->dsql->bt($this->table_alias?:$table).'.'.
            $this->dsql->bt($this->id_field))
            ;
    }
    /** Turns on debugging mode for this model. All database operations will be outputed */
    function debug(){
        $this->dsql->debug();
        return $this;
    }
    /**
     * Returs list of fields which belong to specific group. You can add fields into groups when you
     * define them and it can be used by the front-end to determine which fields needs to be displayed.
     * 
     * If no group is specified, then all non-system fields are displayed for backwards compatibility.
     */
    function getActualFields($group=undefined){
        $fields=array();
        foreach($this->elements as $el)if($el instanceof Field){
            if($el->system())continue;
            if($el->hidden())continue;
            if($group===undefined || $el->group()==$group ||
                ($group=='visible' && $el->visible()) ||
                ($group=='editable' && $el->editable())
            ){
                $fields[]=$el->short_name;
            }
        }
        return $fields;
    }
    /** Produces Dynamic SQL object configured with table, conditions and joins of this model. Useful for delete or update 
     * statements */
    function dsql(){
        return clone $this->dsql;
    }
    /** Produces Dynamic SQL and adds specified fields, referenced fields and expressions. Useful for select statements */
    function selectQuery($fields=null){
        if($fields===null)$fields=$this->getActualFields();
        $select=$this->dsql;
        $select->del('fields');

        // add system fields into select
        foreach($this->elements as $el)if($el instanceof Field){
            if($el->system() && !in_array($el->short_name,$fields))
                $fields[]=$el->short_name;
        }

        // add actual fields
        foreach($fields as $field){
            $field=$this->hasElement($field);
            if(!$field)continue;

            $field->updateSelectQuery($select);
        }
        return $select;
    }
    /** Returns field which should be used as a title */
    function getTitleField(){
        if($this->title_field && $this->hasElement($this->title_field))return $this->title_field;
        return $this->id_field;
    }
    /** Returns query which selects title field */
    function titleQuery(){
        $query=$this->dsql()->del('fields');
        if($this->title_field && $this->hasElement($this->title_field)){
            $this->getElement($this->title_field)->updateSelectQuery($query);
            return $query;
        }
        return $query->field($query->expr('concat("Record #",'.$query->bt($this->dsql->getField($this->id_field)).')'));
    }
    // }}}

    // {{{ Model_Table supports more than just fields. Expressions, References and Joins can be added

    /** Adds and returns SQL-calculated expression as a read-only field. See Field_Expression class. */
    function addExpression($name,$expression=null){
        return $expr=$this
            ->add('Field_Expression',$name)
            ->set($expression);
    }
    public $relations=array();
    /** Constructs model from multiple tables. Queries will join tables, inserts, updates and deletes will be applied on both tables */
    function join($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null,$relation=null){

        if(!$_foreign_alias)$_foreign_alias='_'.$foreign_table[0];
        $_foreign_alias=$this->_unique($this->relations,$_foreign_alias);

        return $this->relations[$_foreign_alias]=$this->add('SQL_Relation',$_foreign_alias)
            ->set($foreign_table,$master_field, $join_kind,$relation);
    }
    /** Adds a sub-query and manyToOne reference */
    function addReference($name){
        return $this
            ->add('Model_Field_Reference',$name);
    }
    function _removeme_addTitle($name){
        $this->title=$name;
        return $this->addField($name);
    }
    /** Defines one to many association */
    function hasOne($model,$our_field=null,$display_field=null){
        if(!$our_field){
            if(!is_object($model)){
                $tmp=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$model);
                $tmp=new $tmp; // avoid recursion
            }else $tmp=$model;
            $our_field=($tmp->table?:$tmp->entity_code).'_id';
        }
        $r=$this->add('Field_Reference',$our_field);
        if($display_field)$r->display($display_field);
        $r->setModel($model);
        return $r;
    }
    /** Defines many to one association */
    function hasMany($model,$their_field=null,$our_field=null){
        if(!$our_field)$our_field=$this->id_field;
        if(!$their_field)$their_field=($this->table?:$this->entity_code).'_id';
        $rel=$this->add('SQL_Many',$model)
            ->set($model,$their_field,$our_field);
    }
    /** Traverses references. Use field name for hasOne() relations. Use model name for hasMany() */
    function ref($name,$load=true){
        return $this->getElement($name)->ref($load);
    }
    /** @obsolete - return model referenced by a field. Use model name for one-to-many relations */
    function getRef($name,$load=true){
        return $this->ref($name,$load);
    }
    /** Adds a "WHERE" condition, but tries to be smart about where and how the field is defined */
    function addCondition($field,$cond=undefined,$value=undefined){

        if($field instanceof DB_dsql && $cond==undefined && $value==undefined){
            $this->dsql->where($field);
            return $this;
        }

        if(!$field instanceof Field){
            $field=$this->getElement($field);
        }
        if($field->type() == 'boolean'){
            if($value===undefined){
                $cond=$cond===true?'Y':($cond===false?'N':null);
            }else{
                $value=$value===true?'Y':($value===false?'N':null);
            }
        }
        if($field->calculated()){
            // TODO: should we use expression in where?
            $this->dsql->having($field->short_name,$cond,$value);
        }elseif($field->relation){
            $this->dsql->where($field->relation->short_name.'.'.$field->short_name,$cond,$value);
        }elseif($this->relations){
            $this->dsql->where($this->table_alias?:$this->table.'.'.$field->short_name,$cond,$value);
        }else{
            $this->dsql->where($field->short_name,$cond,$value);
        }
        return $this;
    }
    /** Sets an order on the field. Field must be properly defined */
    function setOrder($field,$desc=false){
        if(!$field instanceof Field){
            $field=$this->getElement($field);
        }

        if($field->relation){
            $this->dsql->order($field->relation->short_name.'.'.$field->short_name,$desc);
        }elseif($this->relations){
            $this->dsql->order($this->table_alias?:$this->table.'.'.$field->short_name,$desc);
        }else{
            $this->dsql->order($field->short_name,$desc);
        }

        return $this;
    }
    /** Always keep $field equals to $value for queries and new data */
    function setMasterField($field,$value){
        $this->getElement($field)->defaultValue($value)->system(true)->editable(false);
        return $this->addCondition($field,$value);
    }
    // }}}

    // {{{ Iterator support 

    function rewind(){
        $this->dsql->rewind();
        return $this->next();
    }
    function next(){
        $this->set($x=$this->dsql->next());
        $this->id=@$this->data[$this->id_field];
        return $this;
        //return $this->data = $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    function current(){
        return $this->get();
    }
    function key(){
        return $this->get('id');
    }
    function valid(){
        return $this->loaded();
    }

    // }}}

    // {{{ Multiple ways to load data by a model

    /** Loads all matching data into array of hashes */
    function getRows($fields=null){
        return $this->selectQuery($fields)->do_getAll();
    }
    /** @obsolete same as loaded() - returns if any record is currently loaded. */
    function isInstanceLoaded(){ 
        return $this->loaded(); 
    }
    /** Loads the first matching record from the model */
    function loadAny(){
        return $this->_load(null);
    }
    /** Try to load a matching record for the model. Will not raise exception if no records are found */
    function tryLoadAny(){
        return $this->_load(null,true);
    }
    /** Try to load a record by specified ID. Will not raise exception if record is not fourd */
    function tryLoad($id){
        if(!$id)throw $this->exception('Record ID must be specified, otherwise use loadAny()');
        return $this->_load($id,true);
    }
    /** Loads record specified by ID. If omitted will load first matching record */
    function load($id){
        if(!$id)throw $this->exception('Record ID must be specified, otherwise use loadAny()');
        return $this->_load($id);
    }
    /** Similar to loadAny() but will apply condition before loading. Condition is temporary. Fails if record is not loaded. */
    function loadBy($field,$cond=undefined,$value=undefined){
        $q=clone $this->dsql;
        $this->addCondition($field,$cond,$value);
        $this->loadAny();
        $this->dsql=$q;
        return $this;
    }
    /** Attempt to load using a specified condition, but will not fail if such record is not found */
    function tryLoadBy($field,$cond=undefined,$value=undefined){
        $q=clone $this->dsql;
        $this->addCondition($field,$cond,$value);
        $this->tryloadAny();
        $this->dsql=$q;
        return $this;
    }
    /** Loads data record and return array of that data. Will not affect currently loaded record. */
    function getBy($field,$cond=undefined,$value=undefined){
        $q=clone $this->dsql;
        $data=$this->data;
        $id=$this->id;
        $this->addCondition($field,$cond,$value);
        $this->tryLoadAny();
        $row=$this->get();
        $this->dsql=$q;
        $this->data=$data;
        $this->id=$id;
        return $row;
    }
    /** Internal loading funciton. Do not use. OK to override. */
    protected function _load($id,$ignore_missing=false){
        $load = clone $this->selectQuery();
        $p='';if($this->relations)$p=($this->table_alias?:$this->table).'.';
        if(!is_null($id))$load->where($p.$this->id_field,$id)->limit(1);

        $this->hook('beforeLoad',array($load));


        $load->stmt=null;
        $data = $load->limit(1)->get();
        $this->reset();

        if(!isset($data[0])){
            if($ignore_missing)return $this; else 
                throw $this->exception('Record could not be loaded')
                ->addMoreInfo('model',$this)
                ->addMoreInfo('id',$id)
            ;
        }

        $this->data=$data[0];  // avoid using set() for speed and to avoid field checks
        $this->id=$this->data[$this->id_field];

        $this->hook('afterLoad');

        return $this;
    }
    /** @obsolete Backward-compatible. Will attempt to load but will not fail */
    function loadData($id=null){ 
        if($id)$this->tryLoad($id);
        return $this;
    }

    // }}}

    // {{{ Saving Data

    /** Save model into database and try to load it back as a new model of specified class. Instance of new class is returned */
    function saveAndUnload(){
        $this->_save_as=false;
        $this->save();
        return $this;
    }
    /** Will save model later, when it's being destructed by Garbage Collector */
    function saveLater(){
        $this->_save_later=true;
        return $this;
    }
    function __destruct(){
        if($this->_save_later){
            $this->saveAndUnload();
        }
    }
    function saveAs($model){
        if(is_string($model)){
            if(substr($model,0,strlen('Model'))!='Model'){
                $model=preg_replace('|^(.*/)?(.*)$|','\1Model_\2',$model);
            }
            $model=$this->add($model);
        }
        $this->_save_as=$model;
        return $this->save();
    }
    private $_save_as=null;
    private $_save_later=false;
    /** Save model into database and load it back. If for some reason it won't load, whole operation is undone */
    function save(){
        $this->dsql->owner->beginTransaction();
        $this->hook('beforeSave');

        // decide, insert or modify
        if($this->loaded()){
            $res=$this->modify();
        }else{
            $res=$this->insert();
        }

        $res->hook('afterSave');
        $this->dsql->owner->commit();
        return $res;
    }
    /** Internal function which performs insert of data. Use save() instead. OK to override. */
    private function insert(){
        $insert = $this->dsql();

        // Performs the actual database changes. Throw exception if problem occurs
        foreach($this->elements as $name=>$f)if($f instanceof Field){
            if(!$f->editable() && !$f->system())continue;

            $f->updateInsertQuery($insert);
        }

        $this->hook('beforeInsert',array($insert));
        $id = $insert->do_insert();
        $this->hook('afterInsert',array($id));

        if($this->_save_as===false)return $this->unload();
        if($this->_save_as)$this->unload();
        $o=$this->_save_as?:$this;

        $res=$o->load($id);
        if(!$res)throw $this->exception('Problem');
        return $res;
    }
    /** Internal function which performs modification of existing data. Use save() instead. OK to override. Will return new 
        * object if saveAs() is used */
    private function modify(){
        $modify = $this->dsql();
        $modify->where($this->id_field, $this->id);


        if(!$this->dirty)return $this;
        foreach($this->dirty as $name=>$junk){
            if($el=$this->hasElement($name))if($el instanceof Field){
                $el->updateModifyQuery($modify);
            }
        }

        // Performs the actual database changes. Throw exceptions if problem occurs
        $this->hook('beforeModify',array($modify));
        if($modify->args['set'])$modify->do_update();
        $this->hook('afterModify');

        if($this->_save_as===false)return $this->unload();
        if($this->_save_as)$this->unload();
        $o=$this->_save_as?:$this;

        return $o->load($this->id);
    }
    /** @obsolete. Use set() then save(). */
    function update($data=array()){ // obsolete
        if($data)$this->set($data);
        return $this->save();
    }

    // }}}

    // {{{ Unloading and Deleting data

    /** forget currently loaded record and it's ID. Will not affect database */
    function unload(){
        $this->hook('beforeUnload');
        $this->id=null;
        parent::unload();
        $this->hook('afterUnload');
        return $this;
    }
    /** Deletes record matching the ID */
    function delete($id=null){
        if(!$id && !$this->id)throw $this->exception('Specify ID to delete()');
        $delete=$this->dsql()->where($this->id_field,$id?:$this->id)->delete();

        $delete->owner->beginTransaction();
        $this->hook('beforeDelete',array($delete));
        $delete->execute();
        $this->hook('afterDelete');
        $delete->owner->commit();

        if($this->id==$id)$this->reset();

        return $this;
    }
    /** Deletes all records matching this model. Use with caution. */
    function deleteAll(){

        $delete->owner->beginTransaction();
        $this->hook('beforeDeleteAll',array($delete));
        $delete->execute();
        $this->hook('afterDeleteAll');
        $delete->owner->commit();
        $this->reset();

        return $this;
    }

    // }}}
}
