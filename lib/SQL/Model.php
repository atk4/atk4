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
/**
 * Implementation of a Relational SQL-backed Model
 * @link http://agiletoolkit.org/doc/modeltable
 *
 * SQL_Model allows you to take advantage of relational SQL database without neglecting
 * powerful functionality of your RDBMS. On top of basic load/save/delete operations, you can
 * pefrorm multi-row operations, traverse relations, or use SQL expressions
 *
 * The $table property of SQL_Model always contains the primary table. The $this->id will
 * always correspond with ID field of that table and when inserting record will always be
 * placed inside primary table first.
 *
 * Use:
 * class Model_User extends SQL_Model {
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
class SQL_Model extends Model {

    /** Master DSQL record which will be cloned by other operations. For low level use only. Use $this->dsql() when in doubt. */
    protected $dsql; 

    /** If you wish that alias is used for the table when selected, you can define it here.
     * This will help to keep SQL syntax shorter, but will not impact functionality */
    public $table_alias=null;   // Defines alias for the table, can improve readability of queries
    public $entity_code=null;   // @osolete. Use $table

    public $relations=array();  // Joins

    // Call $model->debug(true|false) to turn on|off debug mode
    public $debug = false;

    public $db=null;            // Set to use different database connection

    public $fast=null;          // set this to true to speed up model, but sacrifice some of the consistency

    // {{{ Basic Functionality, query initialization and actual field handling
   
    /** Initialization of ID field, which must always be defined */
    function __construct(){
        if($this->entity_code){
            $this->table=$this->entity_code;
            unset($this->entity_code);
        }
    }
    /**
     * {@inheritdoc}
     */
    function init()
    {
        parent::init();

        if(!$this->db)$this->db=$this->api->db;

        if($this->owner instanceof Field_Reference && $this->owner->owner->relations){
            $this->relations =& $this->owner->owner->relations;
        }

        $this->addField($this->id_field)->system(true);
    }
    function addField($name,$actual_field=null){
        if($this->hasElement($name))throw $this->exception('Field with this name is already defined')
            ->addMoreInfo('field',$name);
        if($name=='deleted' && isset($this->api->compat)){
            return $this->add('Field_Deleted',$name)->enum(array('Y','N'));
        }
        $f=parent::addField($name);
        if(!is_null($actual_field))$f->actual($actual_field);
        return $f;
    }
    /** exception() will automatically add information about current model and will allow to turn on "debug" mode */
    function exception(){
        return call_user_func_array(array('parent',__FUNCTION__), func_get_args())
            ->addThis($this)
            ;
    }
    /** Initializes base query for this model. 
     * @link http://agiletoolkit.org/doc/modeltable/dsql */
    function initQuery(){
        if(!$this->table)throw $this->exception('$table property must be defined');
        $this->dsql=$this->db->dsql();
        $this->dsql->debug($this->debug);
        $this->dsql->table($this->table,$this->table_alias);
        $this->dsql->default_field=$this->dsql->expr('*,'.
            $this->dsql->bt($this->table_alias?:$this->table).'.'.
            $this->dsql->bt($this->id_field))
            ;
        $this->dsql->id_field = $this->id_field;
        return $this;
    }
    /** Use this instead of accessing dsql directly. This will initialize $dsql property if it does not exist yet */
    function _dsql(){
        if(!$this->dsql)$this->initQuery();
        return $this->dsql;
    }
    function __clone(){
        if (is_object($this->dsql)){
            $this->dsql=clone $this->dsql;
        }
    }
    /** Produces a close of Dynamic SQL object configured with table, conditions and joins of this model. 
     * Use for statements you are going to execute manually. */
    function dsql(){
        return clone $this->_dsql();
    }
    /** Turns debugging mode on|off for this model. All database operations will be outputed */
    function debug($enabled = true){
        $this->debug = $enabled;
        if($this->dsql)$this->dsql->debug($enabled);
        return $this;
    }
    /** Completes initialization of dsql() by adding fields and expressions. */
    function selectQuery($fields=null){
        /**/$this->api->pr->start('selectQuery/getActualF');

        $actual_fields=$fields?:$this->getActualFields();

        if($this->fast && $this->_selectQuery) {
            return $this->_selectQuery();
        }

        $this->_selectQuery=$select=$this->_dsql()->del('fields');

        /**/$this->api->pr->next('selectQuery/addSystemFields');
        // add system fields into select
        foreach($this->elements as $el)if($el instanceof Field){
            if($el->system() && !in_array($el->short_name,$actual_fields)){
                $actual_fields[]=$el->short_name;
            }
        }
        /**/$this->api->pr->next('selectQuery/updateQuery');

        // add actual fields
        foreach($actual_fields as $field){
            $field=$this->hasElement($field);
            if(!$field)continue;

            $field->updateSelectQuery($select);
        }
        /**/$this->api->pr->stop();
        return $select;
    }
    /** Return query for a specific field. All other fields are ommitted. */
    function fieldQuery($field){
        $query=$this->dsql()->del('fields');
        if(is_string($field))$field=$this->getElement($field);
        $field->updateSelectQuery($query);
        return $query;
    }
    /** Returns query which selects title field */
    function titleQuery(){
        $query=$this->dsql()->del('fields');
        if($this->title_field && $el=$this->hasElement($this->title_field)){
            $el->updateSelectQuery($query);
            return $query;
        }
        return $query->field($query->concat('Record #',$this->getElement($this->id_field)));
    }
    // }}}

    // {{{ SQL_Model supports more than just fields. Expressions, References and Joins can be added

    /** Adds and returns SQL-calculated expression as a read-only field. See Field_Expression class. */
    function addExpression($name,$expression=null){
        return $expr=$this
            ->add('Field_Expression',$name)
            ->set($expression);
    }
    /** Constructs model from multiple tables. Queries will join tables, inserts, updates and deletes will be applied on both tables */
    function join($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null,$relation=null){

        if(!$_foreign_alias)$_foreign_alias='_'.$foreign_table[0];
        $_foreign_alias=$this->_unique($this->relations,$_foreign_alias);

        return $this->relations[$_foreign_alias]=$this->add('SQL_Relation',$_foreign_alias)
            ->set($foreign_table,$master_field, $join_kind,$relation);
    }
    /** Creates weak join between tables. The foreign table may be absent and will not be automatically deleted */
    function leftJoin($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null,$relation=null){
        if(!$join_kind)$join_kind='left';
        $res=$this->join($foreign_table,$master_field,$join_kind,$_foreign_alias,$relation);
        $res->delete_behaviour='ignore';
        return $res;
    }
    /** Defines one to many association */
    function hasOne($model,$our_field=null,$display_field=null,$as_field=null){

        // register reference, but don't create any fields there
        parent::hasOne($model,null);

        if(!$our_field){
            if(!is_object($model)){
                $tmp=$this->api->normalizeClassName($model,'Model');
                $tmp=new $tmp; // avoid recursion
            }else $tmp=$model;
            $our_field=($tmp->table).'_id';
        }

        $r=$this->add('Field_Reference',array('name'=>$our_field,'dereferenced_field'=>$as_field));
        $r->setModel($model,$display_field);
        return $r;
    }
    /** Defines many to one association */
    function hasMany($model,$their_field=null,$our_field=null,$as_field=null){
        if(!$our_field)$our_field=$this->id_field;
        if(!$their_field)$their_field=($this->table).'_id';
        $rel=$this->add('SQL_Many',$as_field?:$model)
            ->set($model,$their_field,$our_field);
        return $rel;
    }
    /** Traverses references. Use field name for hasOne() relations. Use model name for hasMany() */
    function ref($name,$load=null){
        if(!$name)return $this;
        return $this->getElement($name)->ref($load);
    }
    /** Returns Model with SQL join usable for subqueries. */
    function refSQL($name){
        return $this->getElement($name)->refSQL();
    }
    /** @obsolete - return model referenced by a field. Use model name for one-to-many relations */
    function getRef($name,$load=null){
        return $this->ref($name,$load);
    }
    /**
     * Adds "WHERE" condition / conditions in underlying DSQL
     *
     * It tries to be smart about where and how the field is defined.
     *
     * $field can be passed as:
     *      - string (field name in this model)
     *      - Field object
     *      - DSQL expression
     *      - array (see note below)
     *
     * $cond can be passed as:
     *      - string ('=', '>', '<=', etc.)
     *      - value can be passed here, then it's used as $value with condition '='
     *
     * $value can be passed as:
     *      - string, integer, boolean or any other simple data type
     *      - Field object
     *      - DSQL expreession
     *
     * NOTE: $field can be passed as array of conditions. Then all conditions
     *      will be joined with `OR` using DSQLs orExpr method.
     *      For example,
     *          $model->addCondition(array(
     *              array('profit', '=', null),
     *              array('profit', '<', 1000),
     *          ));
     *      will generate "WHERE profit is null OR profit < 1000"
     *
     * EXAMPLES:
     * you can pass [dsql, dsql, dsql ...] and this will be treated
     * as (dsql OR dsql OR dsql) ...
     *
     * you can pass [[field,cond,value], [field,cond,value], ...] and this will
     * be treated as (field=value OR field=value OR ...)
     *
     * BTW, you can mix these too :)
     * [[field,cond,value], dsql, [field,cond,value], ...]
     * will become (field=value OR dsql OR field=value)
     * 
     * Value also can be DSQL expression, so following will work nicely:
     * [dsql,'>',dsql] will become (dsql > dsql)
     * [dsql, dsql] will become (dsql = dsql)
     * [field, cond, dsql] will become (field = dsql)
     *
     * @todo Romans: refactor using parent::conditions (through array)
     *
     * @param mixed $field Field for comparing or array of conditions
     * @param mixed $cond Condition
     * @param mixed $value Value for comparing
     * @param DSQL $dsql DSQL object to which conditions will be added
     *
     * @return this
     */
    function addCondition($field, $cond = undefined, $value = undefined, $dsql = null)
    {
        // by default add condition to models DSQL
        if (! $dsql) {
            $dsql = $this->_dsql();
        }

        // if array passed, then create multiple conditions joined with OR
        if (is_array($field)) {
            $or = $this->dsql()->orExpr();

            foreach ($field as $row) {
                if (! is_array($row)) {
                    $row = array($row);
                }
                // add each condition to OR expression (not models DSQL)
                $f = $row[0];
                $c = array_key_exists(1, $row) ? $row[1] : undefined;
                $v = array_key_exists(2, $row) ? $row[2] : undefined;

                // recursively calls addCondition method, but adds conditions
                // to OR expression not models DSQL object
                $this->addCondition($f, $c, $v, $or);
            }

            // pass generated DSQL expression as "field"
            $field = $or;
            $cond = $value = undefined;
        }
        
        // You may pass DSQL expression as a first argument
        if ($field instanceof DB_dsql) {
            $dsql->where($field, $cond, $value);
            return $this;
        }

        // value should be specified
        if ($cond === undefined && $value === undefined) {
            throw $this->exception('Incorrect condition. Please specify value');
        }

        // get model field object
        if (! $field instanceof Field) {
            $field = $this->getElement($field);
        }

        if ($cond !== undefined && $value === undefined) {
            $value = $cond;
            $cond = '=';
        }
        if ($field->type() == 'boolean' && strtolower($cond) !== 'like') {
            $value = $field->getBooleanValue($value);
        }

        if ($cond === '=' && !is_array($value)) {
            $field->defaultValue($value)->system(true)->editable(false);
        }
        
        $f = $field->actual_field ?: $field->short_name;
        
        if ($field->calculated()) {
            // TODO: should we use expression in where?
            
            $dsql->where($field->getExpr(), $cond, $value);
            //$dsql->having($f, $cond, $value);
            //$field->updateSelectQuery($this->dsql);
        } elseif ($field->relation) {
            $dsql->where($field->relation->short_name . '.' . $f, $cond, $value);
        } elseif ($this->relations) {
            $dsql->where(($this->table_alias ?: $this->table) . '.' . $f, $cond, $value);
        } else {
            $dsql->where(($this->table_alias ?: $this->table) . '.' . $f, $cond, $value);
        }

        return $this;
    }
    /** Sets limit on query */
    function setLimit($count,$offset=null){
        $this->_dsql()->limit($count,$offset);
        return $this;
    }
    /** Sets an order on the field. Field must be properly defined */
    function setOrder($field,$desc=null){

        if(!$field instanceof Field){
            if(is_object($field)){
                $this->_dsql()->order($field,$desc);
                return $this;
            }

            if(is_string($field) && strpos($field,',')!==false){
                $field=explode(',',$field);
            }
            if(is_array($field)){
                if(!is_null($desc))
                    throw $this->exception('If first argument is array, second argument must not be used');

                foreach(array_reverse($field) as $o)$this->setOrder($o);
                return $this;
            }

            if(is_null($desc) && is_string($field) && strpos($field,' ')!==false){
                list($field,$desc)=array_map('trim',explode(' ',trim($field),2));
            }

            $field=$this->getElement($field);
        }

        $this->_dsql()->order($field, $desc);

        return $this;
    }
    /** @depreciated use two-argument addCondition. Always keep $field equals to $value for queries and new data */
    function setMasterField($field,$value){
        return $this->addCondition($field,$value);
    }
    // }}}

    // {{{ Iterator support 

    /* False: finished iterating. True, reset not yet fetched. Object=DSQL */
    protected $_iterating=false;
    function rewind(){
        $this->_iterating=true;
    }
    function _preexec(){
        $this->_iterating=$this->selectQuery();
        $this->hook('beforeLoad',array($this->_iterating));
        return $this->_iterating;
    }
    function next(){
        if($this->_iterating===true){
            $this->_iterating=$this->selectQuery();
            $this->_iterating->rewind();
            $this->hook('beforeLoad',array($this->_iterating));
        }
        $this->_iterating->next();
        $this->data=$this->_iterating->current();

        if($this->data===false){
            $this->unload();
            $this->_iterating=false;
            return;
        }


        $this->id=@$this->data[$this->id_field];
        $this->dirty=array();

        $this->hook('afterLoad');
    }
    function current(){
        return $this->get();
    }
    function key(){
        return $this->id;
    }
    function valid(){
        /*
        if(!$this->_iterating){
            $this->next();
            $this->_iterating=$this->selectQuery();
        }
        */
        if($this->_iterating===true){
            $this->next();
        }
        return $this->loaded();
    }

    // }}}

    // {{{ Multiple ways to load data by a model

    /** Loads all matching data into array of hashes */
    function getRows($fields=null){
        /**/$this->api->pr->start('getRows/selecting');
        $a=$this->selectQuery($fields);
        /**/$this->api->pr->next('getRows/fetching');
        $a=$a->get();
        $this->api->pr->stop();
        return $a;
    }
    /**
     * Returns dynamic query selecting number of entries in the database
     * 
     * @param string $alias Optional alias of count expression
     * 
     * @return DSQL
     */
    function count($alias = null)
    {
        // prepare new query
        $q = $this->dsql()->del('fields')->del('order');

        // add expression field to query
        return $q->field($q->count(), $alias);
    }
    /**
     * Returns dynamic query selecting sum of particular field or fields
     * 
     * @param string|array|Field $field
     * 
     * @return DSQL
     */
    function sum($field)
    {
        // prepare new query
        $q = $this->dsql()->del('fields')->del('order');

        // put field in array if it's not already
        if (!is_array($field)) {
            $field = array($field);
        }

        // add all fields to query
        foreach ($field as $f) {
            if (!is_object($f)) {
                $f = $this->getElement($f);
            }
            $q->field($q->sum($f), $f->short_name);
        }

        // return query
        return $q;
    }
    /** @obsolete same as loaded() - returns if any record is currently loaded. */
    function isInstanceLoaded(){ 
        return $this->loaded(); 
    }
    /** Loads the first matching record from the model */
    function loadAny(){
        try{
            return $this->_load(null);
        }catch(BaseException $e){
            throw $this->exception('No matching records found');
        }
    }
    /** Try to load a matching record for the model. Will not raise exception if no records are found */
    function tryLoadAny(){
        return $this->_load(null,true);
    }
    /** Loads random entry into model */
    function tryLoadRandom(){
        // get ID first
        $id=$this->dsql()->order('rand()')->limit(1)->field($this->getElement($this->id_field))->getOne();
        if($id)$this->load($id);
        return $this;
    }
    function loadRandom(){
        $this->tryLoadRandom();
        if(!$this->loaded())throw $this->exception('Unable to load random entry');
        return $this;
    }
    /** Try to load a record by specified ID. Will not raise exception if record is not found */
    function tryLoad($id){
        if(is_null($id))throw $this->exception('Record ID must be specified, otherwise use loadAny()');
        return $this->_load($id,true);
    }
    /** Loads record specified by ID. */
    function load($id){
        if(is_null($id))throw $this->exception('Record ID must be specified, otherwise use loadAny()');
        return $this->_load($id);
    }
    /** Similar to loadAny() but will apply condition before loading. Condition is temporary. Fails if record is not loaded. */
    function loadBy($field,$cond=undefined,$value=undefined){
        $q=$this->dsql;
        $this->dsql=$this->dsql();
        $this->addCondition($field,$cond,$value);
        $this->loadAny();
        $this->dsql=$q;
        return $this;
    }
    /** Attempt to load using a specified condition, but will not fail if such record is not found */
    function tryLoadBy($field,$cond=undefined,$value=undefined){
        $q=$this->dsql;
        $this->dsql=$this->dsql();
        $this->addCondition($field,$cond,$value);
        $this->tryLoadAny();
        $this->dsql=$q;
        return $this;
    }
    /** Loads data record and return array of that data. Will not affect currently loaded record. */
    function getBy($field,$cond=undefined,$value=undefined){

        $data=$this->data;
        $id=$this->id;

        $this->tryLoadBy($field,$cond,$value);
        $row=$this->data;

        $this->data=$data;
        $this->id=$id;

        return $row;
    }
    /** Internal loading funciton. Do not use. OK to override. */
    protected function _load($id,$ignore_missing=false){
        /**/$this->api->pr->start('load/selectQuery');
        $this->unload();
        $load = $this->selectQuery();
        /**/$this->api->pr->next('load/clone');
        $p='';if($this->relations)$p=($this->table_alias?:$this->table).'.';
        /**/$this->api->pr->next('load/where');
        if(!is_null($id))$load->where($p.$this->id_field,$id);

        /**/$this->api->pr->next('load/beforeLoad');
        $this->hook('beforeLoad',array($load,$id));


        if(!$this->loaded()){
            /**/$this->api->pr->next('load/get');
            $s=$load->stmt;
            $l=$load->args['limit'];
            $load->stmt=null;
            $data = $load->limit(1)->getHash();
            $load->stmt=$s;
            $load->args['limit']=$l;

            if(!is_null($id))array_pop($load->args['where']);    // remove where condition
            /**/$this->api->pr->next('load/ending');
            $this->reset();

            if(@!$data){
                if($ignore_missing)return $this; else {
                    throw $this->exception('Record could not be loaded')
                    ->addMoreInfo('model',$this)
                    ->addMoreInfo('id',$id)
                ;
                }
            }

            $this->data=$data;  // avoid using set() for speed and to avoid field checks
            $this->id=$this->data[$this->id_field];
        }

        $this->hook('afterLoad');
        /**/$this->api->pr->stop();

        return $this;
    }
    /** @obsolete Backward-compatible. Will attempt to load but will not fail */
    function loadData($id=null){ 
        if($id)$this->tryLoad($id);
        return $this;
    }
    // }}}

    // {{{ Saving Data
    /** Save model into database and don't try to load it back */
    function saveAndUnload(){
        $this->_save_as=false;
        $this->save();
        $this->_save_as=null;
        return $this;
    }
    /** Save model into database and try to load it back as a new model of specified class. Instance of new class is returned */
    function saveAs($model){
        if(is_string($model)){
            $model=$this->api->normalizeClassName($model,'Model');
            $model=$this->add($model);
        }
        $this->_save_as=$model;
        $res=$this->save();
        $this->_save_as=null;
        return $res;
    }
    /** Save model into database and load it back. If for some reason it won't load, whole operation is undone */
    function save(){
        $this->_dsql()->owner->beginTransaction();
        $this->hook('beforeSave');

        // decide, insert or modify
        if($this->loaded()){
            $res=$this->modify();
        }else{
            $res=$this->insert();
        }

        $res->hook('afterSave');
        $this->_dsql()->owner->commit();
        return $res;
    }
    /**
     * Internal function which performs insert of data. Use save() instead. OK to override.
     *  Will return new object if saveAs() is used
     */
    private function insert(){

        $insert = $this->dsql();

        // Performs the actual database changes. Throw exception if problem occurs
        foreach($this->elements as $name=>$f)if($f instanceof Field){
            if(!$f->editable() && !$f->system())continue;
            if(!isset($this->dirty[$name]) && $f->defaultValue()===null)continue;

            $f->updateInsertQuery($insert);
        }
        $this->hook('beforeInsert',array($insert));
        //delayed is not supported by INNODB, but what's worse - it shows error.
        //if($this->_save_as===false)$insert->option_insert('delayed');
        $id = $insert->insert();
        if($id==0){
            // no auto-increment column present
            $id=$this->get($this->id_field);

            if($id===null && $this->_save_as!== false){
                throw $this->exception('Please add auto-increment ID column to your table or specify ID manually');
            }
        }
        $res=$this->hook('afterInsert',array($id));
        if($res===false)return $this;

        if($this->_save_as===false){
            return $this->unload();
        }
        if($this->_save_as)$this->unload();
        $o=$this->_save_as?:$this;

        if($this->fast && !$this->_save_as){
            $this[$this->id_field]=$this->id=$id;
            return $this;
        }
        $res=$o->tryLoad($id);
        if(!$res->loaded())throw $this->exception('Saved model did not match conditions. Save aborted.');
        return $res;
    }
    /**
     * Internal function which performs modification of existing data. Use save() instead. OK to override.
     * Will return new object if saveAs() is used
     */
    private function modify(){
        $modify = $this->dsql()->del('where');
        $modify->where($this->getElement($this->id_field), $this->id);

        if(!$this->dirty)return $this;

        foreach($this->dirty as $name=>$junk){
            if($el=$this->hasElement($name))if($el instanceof Field){
                $el->updateModifyQuery($modify);
            }
        }

        // Performs the actual database changes. Throw exceptions if problem occurs
        $this->hook('beforeModify',array($modify));
        if($modify->args['set'])$modify->update();

        if($this->dirty[$this->id_field]){
            $this->id=$this->get($this->id_field);
        }

        $this->hook('afterModify');

        if($this->_save_as===false)return $this->unload();
        $id=$this->id;
        if($this->_save_as)$this->unload();
        $o=$this->_save_as?:$this;

        return $o->load($id);
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
        if ($this->_save_later) {
            $this->_save_later=false;
            $this->saveAndUnload();
        }
        $this->hook('beforeUnload');
        $this->id=null;
        parent::unload();
        $this->hook('afterUnload');
        return $this;
    }
    /** Tries to delete record, but does nothing if not found */
    function tryDelete($id=null){
        if(!is_null($id))$this->tryLoad($id);
        if($this->loaded())$this->delete();
        return $this;
    }
    /** Deletes record matching the ID */
    function delete($id=null){
        if(!is_null($id))$this->load($id);
        if(!$this->loaded())throw $this->exception('Unable to determine which record to delete');

        $tmp=$this->dsql;

        $this->initQuery();
        $delete=$this->dsql->where($this->id_field,$this->id);

        $delete->owner->beginTransaction();
        $this->hook('beforeDelete',array($delete));
        $delete->delete();
        $this->hook('afterDelete');
        $delete->owner->commit();

        $this->dsql=$tmp;
        $this->unload();

        return $this;
    }
    /** Deletes all records matching this model. Use with caution. */
    function deleteAll(){

        $delete=$this->dsql();
        $delete->owner->beginTransaction();
        $this->hook('beforeDeleteAll',array($delete));
        $delete->delete();
        $this->hook('afterDeleteAll');
        $delete->owner->commit();
        $this->reset();

        return $this;
    }

    // }}}
}
