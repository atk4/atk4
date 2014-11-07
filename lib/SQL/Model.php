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
class SQL_Model extends Model implements Serializable {

    /** Master DSQL record which will be cloned by other operations. For low level use only. Use $this->dsql() when in doubt. */
    protected $dsql;
    public $field_class='Field';

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
    }
    function addField($name,$actual_field=null){
        if ($this->hasElement($name)) {
            if($name == $this->id_field) return $this->getElement($name);
            throw $this->exception('Field with this name is already defined')
            ->addMoreInfo('field',$name);
        }
        if($name=='deleted' && isset($this->api->compat)){
            return $this->add('Field_Deleted',$name)->enum(array('Y','N'));
        }

        // $f=parent::addField($name);
        $f = $this->add($this->field_class,$name);
        //

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
        if($enabled===true) {
            $this->debug = $enabled;
            if($this->dsql)$this->dsql->debug($enabled);
        } else  parent::debug($enabled);
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
        // parent::hasOne($model,null);
        // model, our_field
        $this->_references[null]=$model;


        if(!$our_field){
            if(!is_object($model)){
                $tmp=$this->api->normalizeClassName($model,'Model');
                $tmp=new $tmp; // avoid recursion
            }else $tmp=$model;
            $our_field=($tmp->table).'_id';
        }

        $r=$this->add('Field_Reference',array('name'=>$our_field,'dereferenced_field'=>$as_field));
        $r->setModel($model,$display_field);
        $r->system(true)->editable(true);
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
        if ($field->type() == 'boolean') {
            $value = $field->getBooleanValue($value);
        }

        if ($cond === '=') {
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
        return $this;
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
            throw $this->exception('No matching records found',null,404);
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
        $this->hook('beforeInsert',array(&$insert));
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
        // parent::unload();

        if ($this->_save_later) {
            $this->_save_later=false;
            $this->saveAndUnload();
        }
        if ($this->loaded()) {
            $this->hook('beforeUnload');
        }
        $this->data = $this->dirty = array();
        $this->id = null;
        $this->hook('afterUnload');
        //

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



    // Override all methods to keep back-compatible
    function set($name,$value=undefined){
        if(is_array($name)){
            foreach($name as $key=>$val)$this->set($key,$val);
            return $this;
        }
        if($name===false || $name===null){
            return $this->reset();
        }

        // Verify if such a filed exists
        if($this->strict_fields && !$this->hasElement($name))throw $this->exception('No such field','Logic')
            ->addMoreInfo('name',$name);

        if($value!==undefined
            && (
                is_object($value)
                || is_object($this->data[$name])
                || is_array($value)
                || is_array($this->data[$name])
                || (string)$value!=(string)$this->data[$name] // this is not nice..
                || $value !== $this->data[$name] // considers case where value = false and data[$name] = null
                || !isset($this->data[$name]) // considers case where data[$name] is not initialized at all (for example in model using array controller)
            )
        ) {
            $this->data[$name]=$value;
            $this->setDirty($name);
        }
        return $this;
    }

    function get($name=null){
        if($name===null)return $this->data;

        $f=$this->hasElement($name);

        if($this->strict_fields && !$f)
            throw $this->exception('No such field','Logic')->addMoreInfo('field',$name);

        // See if we have data for the field
        if(!$this->loaded() && !isset($this->data[$name])){ // && !$this->hasElement($name))

            if($f && $f->has_default_value)return $f->defaultValue();


            if($this->strict_fields)throw $this->exception('Model field was not loaded')
                ->addMoreInfo('id',$this->id)
                ->addMoreinfo('field',$name);

            return null;
        }
        return $this->data[$name];
    }

function getActualFields($group = undefined)
    {
        if($group===undefined && $this->actual_fields) {
            return $this->actual_fields;
        }

        $fields = array();

        if (strpos($group, ',')!==false) {
            $groups = explode(',', $group);

            foreach($groups as $group) {
                if($group[0]=='-') {
                    $el = $this->getActualFields(substr($group, 1));
                    $fields = array_diff($fields, $el);
                } else {
                    $el = $this->getActualFields($group);
                    $fields = array_merge($fields, $el);
                }
            }
        }

        foreach($this->elements as $el) {
            if($el instanceof Field && !$el->hidden()) {
                if( $group===undefined ||
                    $el->group()==$group ||
                    (strtolower($group=='visible') && $el->visible()) ||
                    (strtolower($group=='editable') && $el->editable())
                ) {
                    $fields[] = $el->short_name;
                }
            }
        }

        return $fields;
    }



function setActualFields(array $fields)
    {
        $this->actual_fields = $fields;
        return $this;
    }

function setDirty($name)
    {
        $this->dirty[$name] = true;
        return $this;
    }
    function isDirty($name){
        return $this->dirty[$name] ||
            (!$this->loaded() && $this->getElement($name)->has_default_value);
    }
    function reset()
    {
        return $this->unload();
    }

    function offsetExists($name){
        return $this->hasElement($name);
    }
    function offsetGet($name){
        return $this->get($name);
    }
    function offsetSet($name,$val){
        $this->set($name,$val);
    }
    function offsetUnset($name){
        unset($this->dirty[$name]);
    }

function setSource($controller, $table=null, $id=null){
        if(is_string($controller)){
            $controller=$this->api->normalizeClassName($controller,'Data');
        } elseif(!$controller instanceof Controller_Data){
            throw $this->exception('Inappropriate Controller. Must extend Controller_Data');
        }
        $this->controller=$this->setController($controller);

        $this->controller->setSource($this,$table);

        if($id)$this->load($id);
        return $this;
    }
function addCache($controller, $table=null, $priority=5){
        $controller=$this->api->normalizeClassName($controller,'Data');
        return $this->setController($controller)
            ->addHooks($this,$priority)
            ->setSource($this,$table);
    }

function each($callable)
    {
        if (!($this instanceof Iterator)) {
            throw $this->exception('Calling each() on non-iterative model');
        }

        if (is_string($callable)) {
            foreach ($this as $value) {
                $this->$callable();
            }
            return $this;
        }

        foreach ($this as $value) {
            if (call_user_func($callable, $this) === false) {
                break;
            }
        }
        return $this;
    }

function newField($name){
        return $this->addField($name);
    }
    function hasField($name){
        return $this->hasElement($name);
    }
    function getField($f){
        return $this->getElement($f);
    }
function _ref($ref,$class,$field,$val){
        $m=$this
            ->add($this->api->normalizeClassName($class,'Model'))
            ->ref($ref);

        // For one to many relation, create condition, otherwise do nothing,
        // as load will follow
        if($field){
            $m->addCondition($field,$val);
        }
        return $m;
    }
    function _refBind($field_in,$expression,$field_out=null){

        if($this->controller)return $this->controller->refBind($this,$field,$expression);

        list($myref,$rest)=explode('/',$ref,2);

        if(!$this->_references[$myref])throw $this->exception('No such relation')
            ->addMoreInfo('ref',$myref)
            ->addMoreInfo('rest',$rest);
        // Determine and populate related model

        if(is_array($this->_references[$myref])){
            $m=$this->_references[$myref][0];
        }else{
            $m=$this->_references[$myref];
        }
        $m=$this->add($m);
        if($rest)$m=$m->_ref($rest);
        $this->_refGlue();


        if(!isset($this->_references[$ref]))throw $this->exception('Unable to traverse, no reference defined by this name')
            ->addMoreInfo('name',$ref);

        $r=$this->_references[$ref];

        if(is_array($r)){
            list($m,$our_field,$their_field)=$r;

            if(is_string($m)){
                $m=$this->add($m);
            }else{
                $m=$m->newInstance();
            }

            return $m->addCondition($their_field,$this[$our_field]);
        }


        if(is_string($m)){
            $m=$this->add($m);
        }else{
            $m=$m->newInstance();
        }
        return $m->load($this[$our_field]);
    }



    function serialize() {
        return serialize(array(
            'id'=>$this->id,
            'data'=>$this->data
        ));
    }

    function unserialize($data) {
        $data=unserialize($data);
        $this->id=$data['id'];
        $this->data=$data['data'];
    }





    // TESTS


    function atk4_test_AddField() {
        $model = $this->add(get_class($this));
        $fieldName = 'field_name';
        $field = $model->addField($fieldName);
        $this->assertTrue($field instanceof $model->field_class, 'Field is not a valid instance');
        $this->assertTrue(isset($model->elements[$fieldName]), 'Field not append to model\'s element');
    }
    function atk4_test_AddTwiceField() {
        $model = $this->add(get_class($this));
        $fieldName = 'field_name';
        $model->addField($fieldName);
        $e = $this->assertThrowException('BaseException',$model,'addField',array($fieldName));
        $this->assertEquals($fieldName, $e->more_info['field']);
    }
    function atk4_test_Set() {
        $model = $this->add(get_class($this));
        $fieldName = 'field_name';
        $fieldValue = 'field_value';
        $model->addField($fieldName);
        $model->set($fieldName, $fieldValue);
        $this->assertEquals($fieldValue, $model->data[$fieldName], 'Model set different value');
        $this->assertTrue($model->isDirty($fieldName), 'Field isn\'t set to dirty');
    }
    function atk4_test_SetWithArray() {
        $model = $this->add(get_class($this));
        $model->set(array('field1' => 'value1', 'field2' => 'value2'));
        $this->assertEquals('value1', $model->data['field1'], 'Model set different value');
        $this->assertEquals('value2', $model->data['field2'], 'Model set different value');
        $this->assertTrue($model->isDirty('field1'), 'Field isn\'t set to dirty');
        $this->assertTrue($model->isDirty('field2'), 'Field isn\'t set to dirty');
    }
    function atk4_test_SetInexistentField() {
        $model = $this->add(get_class($this), array('strict_fields' => true));
        $e = $this->assertThrowException('Exception_Logic', $model, 'set', array('inexistentField', 'value'));
        $this->assertEquals('inexistentField', $e->more_info['name']);
    }


    // TODO doesn't work as expected
    /*function atk4_test_SetToSameValue() {
        $model = $this->add(get_class($this));
        $model->data['field1'] = 'value1';
        $model->set('field1', 'value1');
        $this->assertFalse($model->isDirty('field1'), 'Model field is dirty');
    }*/

    // TODO doesn't work as expected. Throwing Exception_Logic: No such field
    /*function atk4_test_SetNullFieldStrict() {
        $model = $this->add(get_class($this), array('strict_fields' => true));
        $model->set(array('field1' => null, 'field2' => 2, 'field3' => 3));
        $this->assertTrue(array_key_exists('field1', $model->data), 'Model not set');
        $this->assertEquals(null, $model->data['field1']);
    }*/

    // TODO doesn't throw expected exception :(
    /*function atk4_test_SetOnlyKey() {
        $model = $this->add(get_class($this));
        $e = $this->assertThrowException('BaseException', $model, 'set', array('field1'));
        $this->assertEquals('inexistentField', $e->more_info['name']);
    }*/

    function atk4_test_Get() {
        $model = $this->add(get_class($this));
        $model->data['field1'] = 'value1';
        $this->assertEquals('value1', $model->get('field1'));
    }

    function atk4_test_GetInesistentFieldNoStrict() {
        $model = $this->add(get_class($this));
        $value = $model->get('inexistentField');
        $this->assertEquals(null, $value);
    }

    function atk4_test_GetInesistentFieldStrict() {
        $model = $this->add(get_class($this), array('strict_fields' => true));
        $e = $this->assertThrowException('Exception_Logic', $model, 'get', array('inexistentField'));
        $this->assertEquals('inexistentField', $e->more_info['field']);
    }

    // TODO throws Exception_Logic: No such field which seems to be wrong
    /*function atk4_test_GetNullFieldStrict() {
        $model = $this->add(get_class($this), array('strict_fields' => true));
        $model->set(array('field1' => null, 'field2' => 2, 'field3' => 3));
        $this->assertEquals(null, $model->get('field1'));
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_GetCalculated() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $expr = $model->addExpression('concatenate',
            function($model, $data) {
                return $data['field1'] . ' ' . $data['field2'];
            });
        $model->loadAny();
        $expected = array(
            'field1' => 'value1.2',
            'field2' => 'value2.2',
            'field3' => 'value3.2',
            'concatenate' => 'value1.2 value2.2',
        );
        $this->assertEquals($expected, $model->get());
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_GetOnlyCalculated() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $expr = $model->addExpression('concatenate',
            function($model, $data) {
                return $data['field1'] . ' ' . $data['field2'];
            });
        $model->loadAny();
        $this->assertEquals('value1.2 value2.2', $model->get('concatenate'));
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_GetDefaultValue() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->getElement('field2')->defaultValue('defaultValue');
        $this->assertEquals('defaultValue', $model->get('field2'));
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_GetNoDefaultValueStrictMode() {
        $model = $this->add(get_class($this), array('strict_fields' => true));
        $model->setControllerSource(self::$exampleData);
        $this->assertThrowException('BaseException', $model, 'get', array('field2'));
    }*/

    // TODO Exception: Expected true on isset
    /*function atk4_test_ArrayAccess() {
        $model = $this->add(get_class($this));
        $model->set('field1', 'value1111');
        $this->assertEquals('value1111', $model['field1']);
        $model['field2'] = 'value2222';
        $this->assertEquals('value2222', $model->data['field2']);
        $this->assertTrue(isset($model['field2']), 'Expected true on isset');
        $this->assertFalse(isset($model['field3']), 'Expected false on isset');
        $model->getElement('field3')->defaultValue('defaultValue');
        $this->assertTrue(isset($model['field3']), 'Expected true on isset');
        $model->set('field3', 'value3333');
        $this->assertEquals('value3333', $model['field3']);
        unset($model['field3']);
        $this->assertEquals('defaultValue', $model->get('field3'));
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_GetTitleField() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $this->assertEquals('field3', $model->getTitleField());
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_GetTitleFieldNull() {
        $model = $this->add(get_class($this), array('title_field' => null));
        $model->setControllerSource(self::$exampleData);
        $this->assertEquals('field1', $model->getTitleField());
    }*/


    function atk4_test_getActualFields() {
        if (!isset($this->expected_fields)) {
            echo "            INFO :: Set \$this->expected_fields in your model to use this test.\n";
            return;
        }
        $model = $this->add(get_class($this));
        $actualFields = $model->getActualFields();
        $this->assertEquals($this->expected_fields, $actualFields);
    }

    // TODO seems like this test shouldn't work but it work for some strange reason
    function atk4_test_GetSetActualFields() {
        $model = $this->add(get_class($this));
        $expected = array('field1', 'field3');
        $model->setActualFields($expected);
        $actualFields = $model->getActualFields();
        $this->assertEquals($expected, $actualFields);
    }

    // TODO ErrorException: Runtime Notice: [/vendor/atk4/atk4/lib/SQL/<b>Model.php</b>:957]Argument 1 passed to SQL_Model::setActualFields() must be of the type array, none given, called in /vendor/atk4/atk4/lib/SQL/Model.php on line 1305 and defined
    /*function atk4_test_SetAllActualFields() {
        $model = $this->add(get_class($this));
        $model->setActualFields();
        $actualFields = $model->getActualFields();
        $expected = array('field1', 'field2', 'field3');
        $this->assertEquals($expected, $actualFields);
    }*/

    // TODO ErrorException: Runtime Notice: [/vendor/atk4/atk4/lib/SQL/<b>Model.php</b>:957]Argument 1 passed to SQL_Model::setActualFields() must be of the type array, string given, called in /vendor/atk4/atk4/lib/SQL/Model.php on line 1314 and defined
    /*function atk4_test_GroupActualFields() {
        $model = $this->add(get_class($this));
        $model->setActualFields('group1');
        $actualFields = $model->getActualFields();
        $expected = array('field1', 'field2');
        $this->assertEquals($expected, $actualFields);
    }*/

    // TODO ErrorException: Runtime Notice: [/vendor/atk4/atk4/lib/SQL/<b>Model.php</b>:957]Argument 1 passed to SQL_Model::setActualFields() must be of the type array, string given, called in /vendor/atk4/atk4/lib/SQL/Model.php on line 1324 and defined
    /*function atk4_test_CommaSeparatedGroupActualFields() {
        $model = $this->add(get_class($this));
        $model->addField('field4')->group('group3');
        $model->setActualFields('group1,group3');
        $actualFields = $model->getActualFields();
        $expected = array('field1', 'field2', 'field4');
        $this->assertEquals($expected, $actualFields);
    }*/

    // TODO ErrorException: Runtime Notice: [/vendor/atk4/atk4/lib/SQL/<b>Model.php</b>:957]Argument 1 passed to SQL_Model::setActualFields() must be of the type array, string given, called in /vendor/atk4/atk4/lib/SQL/Model.php on line 1334 and defined
    /*function atk4_test_ExpludeGroupActualFields() {
        $model = $this->add(get_class($this));
        $model->addField('field4')->group('group3');
        $model->setActualFields('all,-group3');
        $actualFields = $model->getActualFields();
        $expected = array('field1', 'field2', 'field3');
        $this->assertEquals($expected, $actualFields);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_SetSource() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $this->assertTrue($model->controller instanceof Controller_Data, 'Controller wrong type');
    }*/

    function atk4_test_SetSourceInvalidController() {
        $model = $this->add(get_class($this));
        $this->assertThrowException('BaseException', $model, 'setSource', array($this, self::$exampleData));
    }

    function atk4_test_SetControllerSource() {
        $model = $this->add(get_class($this));
        $model->controller = null;
        $this->assertThrowException('BaseException', $model, 'setControllerSource', array(array()));
    }

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_Load() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->load('value1.1');
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertEquals('value1.1', $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadFail() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->controller->foundOnLoad = false;
        $this->assertThrowException('BaseException', $model, 'load', array('inexistentValue'));
        $this->assertFalse($model->loaded(), 'Model must be not loaded');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadHooks() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $tmp = 0;
        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
        $model->load('value1.1');
        $this->assertEquals(2, $tmp);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadHooksFail() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->controller->foundOnLoad = false;
        $tmp = 0;
        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
        $this->assertThrowException('BaseException', $model, 'load', array('inexistentValue'));
        $this->assertEquals(1, $tmp);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadLoadedModel() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->id = 'value1.2';
        $model->load('value1.1');
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertEquals('value1.1', $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->load('value1.2');
        $this->assertEquals('value1.2', $model->get('field1'));
        $this->assertFalse($model->isDirty('field1'), 'Load don\'t erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadFailDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->controller->foundOnLoad = false;
        $model->tryLoad('value1.2');
        $this->assertThrowException('BaseException', $model, 'load', array('inexistentValue'));
        $this->assertTrue($model->isDirty('field1'), 'Load don\'t erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoad() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->tryLoad('value1.1');
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertEquals('value1.1', $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadFail() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->controller->foundOnLoad = false;
        $model->tryLoad('inexistentId');
        $this->assertFalse($model->loaded(), 'Model must be not loaded');
        $this->assertEquals(null, $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadLoadedModel() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->id = 'value1.2';
        $model->tryLoad('value1.1');
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertEquals('value1.1', $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadLoadedModelFail() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->id = 'value1.2';
        $model->controller->foundOnLoad = false;
        $model->tryLoad('inexistentId');
        $this->assertFalse($model->loaded(), 'Model must be not loaded');
        $this->assertEquals(null, $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadHook() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $tmp = 0;
        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
        $model->tryLoad('value1.1');
        $this->assertEquals(2, $tmp);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadHookFail() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->controller->foundOnLoad = false;
        $tmp = 0;
        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
        $model->tryLoad('inexistentId');
        $this->assertEquals(1, $tmp);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->tryLoad('value1.2');
        $this->assertEquals('value1.2', $model->get('field1'));
        $this->assertFalse($model->isDirty('field1'), 'Load don\'t erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadFailDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->controller->foundOnLoad = false;
        $model->tryLoad('value1.2');
        $this->assertEquals('value11.11', $model->get('field1'));
        $this->assertTrue($model->isDirty('field1'), 'Load erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadAny() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->loadAny();
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertNotEmpty($model->id);
    }*/

    // TODO Exception_PathFinder: File not found App_CLI->locatePath("php", "Controller/Data/Foo.php")
    /*function atk4_test_LoadAnyFail() {
        $model = $this->add(get_class($this));
        $model->setSource('Foo', array());
        $model->controller->foundOnLoad = false;
        $this->assertThrowException('BaseException', $model, 'loadAny');
        $this->assertEquals(null, $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadAnyLoadedModel() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->id = 'value1.2';
        $model->loadAny();
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertNotEmpty($model->id);
    }*/

    // TODO Exception_PathFinder: File not found App_CLI->locatePath("php", "Controller/Data/Foo.php")
    /*function atk4_test_LoadAnyLoadedModelFail() {
        $model = $this->add(get_class($this));
        $model->setSource('Foo', array());
        $model->controller->foundOnLoad = false;
        $model->id = 'value1.2';
        $this->assertThrowException('BaseException', $model, 'loadAny');
        $this->assertEquals(null, $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadAnyHooks() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $tmp = array();
        $self = $this;
        $model->addHook('beforeLoad',
            function() use (&$tmp, $self) {
                $args = func_get_args();
                $self->assertEquals('loadAny', $args[1]);
                $self->assertEquals(array(), $args[2]);
                $tmp[] = 'beforeLoad';
            });
        $model->addHook('afterLoad',
            function() use (&$tmp, $self) {
                $args = func_get_args();
                $self->assertEquals(1, count($args));
                $tmp[] = 'afterLoad';
            });
        $model->loadAny();
        $this->assertEquals(array('beforeLoad', 'afterLoad'), $tmp);
    }*/

    // TODO Exception_PathFinder: File not found App_CLI->locatePath("php", "Controller/Data/Foo.php")
    /*function atk4_test_LoadAnyHooksFail() {
        $model = $this->add(get_class($this));
        $model->setSource('Foo', array());
        $model->controller->foundOnLoad = false;
        $tmp = array();
        $self = $this;
        $model->addHook('beforeLoad',
            function() use (&$tmp, $self) {
                $args = func_get_args();
                $self->assertEquals('loadAny', $args[1]);
                $self->assertEquals(array(), $args[2]);
                $tmp[] = 'beforeLoad';
            });
        $model->addHook('afterLoad',
            function() use (&$tmp, $self) {
                $args = func_get_args();
                $self->assertEquals(1, count($args));
                $tmp[] = 'afterLoad';
            });
        $this->assertThrowException('BaseException', $model, 'loadAny');
        $this->assertEquals(array('beforeLoad'), $tmp);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadAnyDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->loadAny();
        $this->assertEquals('value1.2', $model->get('field1'));
        $this->assertFalse($model->isDirty('field1'), 'Load don\'t erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadAnyFailDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->controller->foundOnLoad = false;
        $this->assertThrowException('BaseException', $model, 'loadAny');
        $this->assertTrue($model->isDirty('field1'), 'Load erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadAny() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->tryLoadAny();
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertNotEmpty($model->id);
    }*/

    // TODO Exception_PathFinder: File not found App_CLI->locatePath("php", "Controller/Data/Foo.php")
    /*function atk4_test_TryLoadAnyFail() {
        $model = $this->add(get_class($this));
        $model->setSource('Foo', array());
        $model->controller->foundOnLoad = false;
        $model->tryLoadAny();
        $this->assertEquals(null, $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadAnyLoadedModel() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->id = 'value1.2';
        $model->tryLoadAny();
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertNotEmpty($model->id);
    }*/

    // TODO Exception_PathFinder: File not found App_CLI->locatePath("php", "Controller/Data/Foo.php")
    /*function atk4_test_TryLoadAnyLoadedModelFail() {
        $model = $this->add(get_class($this));
        $model->setSource('Foo', array());
        $model->controller->foundOnLoad = false;
        $model->id = 'value1.2';
        $model->tryLoadAny();
        $this->assertEquals(null, $model->id);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadAnyHooks() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $tmp = 0;
        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
        $model->tryLoadAny();
        $this->assertEquals(2, $tmp);
    }*/

    // TODO Exception_PathFinder: File not found App_CLI->locatePath("php", "Controller/Data/Foo.php")
    /*function atk4_test_TryLoadAnyHooksFail() {
        $model = $this->add(get_class($this));
        $model->setSource('Foo', array());
        $model->controller->foundOnLoad = false;
        $tmp = 0;
        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
        $model->tryLoadAny();
        $this->assertEquals(1, $tmp);
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadAnyDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->tryLoadAny();
        $this->assertEquals('value1.2', $model->get('field1'));
        $this->assertFalse($model->isDirty('field1'), 'Load don\'t erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_TryLoadAnyFailDirty() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->set('field1', 'value11.11');
        $model->controller->foundOnLoad = false;
        $model->tryLoadAny();
        $this->assertTrue($model->isDirty('field1'), 'Load erase dirty array');
    }*/

    // TODO throws BaseException: Call setControllerData before
    /*function atk4_test_LoadBy() {
        $model = $this->add(get_class($this));
        $model->setControllerSource(self::$exampleData);
        $model->loadBy('field2', '=', 'value2.1');
        $this->assertTrue($model->loaded(), 'Model must be loaded');
        $this->assertNotEmpty($model->id);
    }*/

//    function atk4_test_LoadByFail() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', array());
//        $model->controller->foundOnLoad = false;
//        $this->assertThrowException('BaseException', $model, 'loadBy', array('field1', '=', 'inexisten'));
//        $this->assertEquals(null, $model->id);
//    }
//    function atk4_test_LoadByLoadedModel() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->id = 'value1.2';
//        $model->loadBy('field2', '=', 'value2.1');
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//        $this->assertNotEmpty($model->id);
//    }
//    function atk4_test_LoadByLoadedModelFail() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', array());
//        $model->controller->foundOnLoad = false;
//        $model->id = 'value1.2';
//        $this->assertThrowException('BaseException', $model, 'loadBy', array('field1', '=', 'inexisten'));
//        $this->assertEquals(null, $model->id);
//        $this->assertFalse($model->loaded(), 'Model must be not loaded');
//    }
//    function atk4_test_LoadByHooks() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $tmp = 0;
//        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
//        $model->loadBy('field2', '=', 'value2.1');
//        $this->assertEquals(2, $tmp);
//    }
//    function atk4_test_LoadByHooksFail() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', array());
//        $model->controller->foundOnLoad = false;
//        $tmp = 0;
//        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
//        $this->assertThrowException('BaseException', $model, 'loadBy', array('field1', '=', 'inexisten'));
//        $this->assertEquals(1, $tmp);
//    }
//    function atk4_test_LoadByDirty() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->set('field1', 'value11.11');
//        $model->loadBy('field2', '=', 'value2.1');
//        $this->assertEquals('value1.2', $model->get('field1'));
//        $this->assertFalse($model->isDirty('field1'), 'Load don\'t erase dirty array');
//    }
//    function atk4_test_LoadByFailDirty() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->set('field1', 'value11.11');
//        $model->controller->foundOnLoad = false;
//        $this->assertThrowException('BaseException', $model, 'loadBy', array('field1', '=', 'inexisten'));
//        $this->assertTrue($model->isDirty('field1'), 'Load erase dirty array');
//    }
//    function atk4_test_TryLoadBy() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->tryLoadBy('field2', '=', 'value2.1');
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//        $this->assertNotEmpty($model->id);
//    }
//    function atk4_test_TryLoadByFail() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', array());
//        $model->controller->foundOnLoad = false;
//        $model->tryLoadBy('field1', '=', 'inexisten');
//        $this->assertEquals(null, $model->id);
//        $this->assertFalse($model->loaded(), 'Model must be not loaded');
//    }
//    function atk4_test_TryLoadByLoadedModel() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->id = 'value1.2';
//        $model->tryLoadBy('field2', '=', 'value2.1');
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//        $this->assertNotEmpty($model->id);
//    }
//    function atk4_test_TryLoadByLoadedModelFail() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', array());
//        $model->controller->foundOnLoad = false;
//        $model->id = 'value1.2';
//        $model->tryLoadBy('field1', '=', 'inexisten');
//        $this->assertEquals(null, $model->id);
//        $this->assertFalse($model->loaded(), 'Model must be not loaded');
//    }
//    function atk4_test_TryLoadByArgument() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->tryLoadBy('field2', 'value2.2');
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//        $this->assertEquals('value2.2', $model->get('field2'));
//    }
//    function atk4_test_TryLoadByHooks() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $tmp = 0;
//        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
//        $model->tryLoadBy('field2', '=', 'value2.1');
//        $this->assertEquals(2, $tmp);
//    }
//    function atk4_test_TryLoadByHooksFail() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', array());
//        $model->controller->foundOnLoad = false;
//        $tmp = 0;
//        $model->addHook('beforeLoad,afterLoad', function() use (&$tmp) { $tmp += 1; });
//        $model->tryLoadBy('field2', '=', 'value2.1');
//        $this->assertEquals(1, $tmp);
//    }
//    function atk4_test_TryLoadByDirty() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->set('field1', 'value11.11');
//        $model->tryLoadBy('field2', '=', 'value2.1');
//        $this->assertEquals('value1.2', $model->get('field1'));
//        $this->assertFalse($model->isDirty('field1'), 'Load don\'t erase dirty array');
//    }
//    function atk4_test_TryLoadByFailDirty() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->set('field1', 'value11.11');
//        $model->controller->foundOnLoad = false;
//        $model->tryLoadBy('field2', '=', 'value2.1');
//        $this->assertTrue($model->isDirty('field1'), 'Load erase dirty array');
//    }
//    function atk4_test_Unload() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', self::$exampleData, 'value1.1');
//        $model->unload();
//        $this->assertFalse($model->loaded(), 'Model is already loaded');
//        $this->assertEquals(null, $model->id, 'Id is already set');
//    }
//    function atk4_test_UnloadHooks() {
//        $model = $this->add(get_class($this));
//        $model->setSource('Foo', self::$exampleData, 'value1.1');
//        $tmp = 0;
//        $model->addHook('beforeUnload,afterUnload', function() use (&$tmp) { $tmp += 1; });
//        $model->unload();
//
//        $this->assertEquals(2, $tmp);
//    }
//    function atk4_test_UnloadHooksNotLoaded() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $tmp = 0;
//        $model->addHook('beforeUnload,afterUnload', function() use (&$tmp) { $tmp += 1; });
//        $model->unload();
//
//        $this->assertEquals(1, $tmp);
//    }
//    function atk4_test_Insert() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $fields = array(
//            'field1' => 'newValue1',
//            'field2' => 'newValue2',
//        );
//        $model->set($fields)->save('newValue1');
//        $this->assertEquals($fields, $model->get());
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//        $this->assertEmpty($model->dirty);
//    }
//    function atk4_test_Update() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        $fields = array(
//            'field2' => 'newValue2',
//        );
//        $model->set($fields)->save('newValue1');
//        $this->assertEquals($fields['field2'], $model->get('field2'));
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//        $this->assertEmpty($model->dirty);
//    }
//    function atk4_test_SaveAndUnload() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        $fields = array(
//            'field2' => 'newValue2',
//        );
//        $model->set($fields)->saveAndUnload('newValue1');
//        $this->assertEmpty($model->data);
//        $this->assertFalse($model->loaded(), 'Model must be unloaded');
//        $this->assertEmpty($model->dirty);
//    }
//    function atk4_test_InsertHook() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $tmp = 0;
//        $model->addHook('beforeSave,beforeUpdate,beforeInsert,afterUpdate,afterInsert,afterSave', function() use (&$tmp) { $tmp += 1; });
//        $tmp1 = 0;
//        $model->addHook('beforeSave,beforeInsert,afterInsert,afterSave', function() use (&$tmp1) { $tmp1 += 1; });
//        $fields = array(
//            'field2' => 'newValue2',
//        );
//        $model->set($fields)->save();
//        $this->assertEquals(4, $tmp);
//        $this->assertEquals(4, $tmp1);
//    }
//    function atk4_test_UpdateHook() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $tmp = 0;
//        $model->addHook('beforeSave,beforeUpdate,beforeInsert,afterUpdate,afterInsert,afterSave', function() use (&$tmp) { $tmp += 1; });
//        $tmp1 = 0;
//        $model->addHook('beforeSave,beforeUpdate,afterUpdate,afterSave', function() use (&$tmp1) { $tmp1 += 1; });
//
//        $fields = array(
//            'field2' => 'newValue2',
//        );
//        $model->set($fields)->save();
//        $this->assertEquals(4, $tmp);
//    }
//    function atk4_test_Delete() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->delete('value1.1');
//        $this->assertFalse($model->loaded(), 'Model must be not loaded');
//    }
//    function atk4_test_DeleteModelLoaded() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        $model->delete();
//        $this->assertFalse($model->loaded(), 'Model must be not loaded');
//    }
//    function atk4_test_DeleteModelLoadedWithId() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        $this->assertThrowException('BaseException', $model, 'delete', array('value1.2'));
//        $this->assertTrue($model->loaded(), 'Model must not loaded');
//    }
//    function atk4_test_DeleteModelLoadedWithSameId() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        $model->delete('value1.1');
//        $this->assertFalse($model->loaded(), 'Model must be not loaded');
//    }
//    function atk4_test_DeleteNothing() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $this->assertThrowException('BaseException', $model, 'delete');
//        $this->assertFalse($model->loaded(), 'Model must be not loaded');
//    }
//    function atk4_test_DeleteAll() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $tmp = array();
//        $model->addHook('beforeDeleteAll',
//            function() use (&$tmp) {
//                $tmp[] = 'beforeDeleteAll';
//            });
//        $model->addHook('afterDeleteAll',
//            function() use (&$tmp) {
//                $tmp[] = 'afterDeleteAll';
//            });
//        $model->deleteAll();
//        $this->assertEquals(array('beforeDeleteAll', 'afterDeleteAll'), $tmp);
//    }
//    function atk4_test_DeleteAllLoaded() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        $tmp = array();
//        $model->addHook('beforeDeleteAll',
//            function() use (&$tmp) {
//                $tmp[] = 'beforeDeleteAll';
//            });
//        $model->addHook('afterDeleteAll',
//            function() use (&$tmp) {
//                $tmp[] = 'afterDeleteAll';
//            });
//        $model->deleteAll();
//        $this->assertEquals(array('beforeDeleteAll', 'afterDeleteAll'), $tmp);
//    }
//    function atk4_test_Reload() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        $oldValue = $model->get('field1');
//        $model->set('field1', 'value1111');
//        $model->reload();
//        $this->assertEquals($oldValue, $model->get('field1'));
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//    }
//    function atk4_test_ReloadUnloadedModel() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $this->assertThrowException('BaseException', $model, 'reload');
//    }
//    function atk4_test_Iterable() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        foreach($model as $n => $row) {
//            $this->assertTrue($model->loaded(), 'Model must be loaded');
//            $this->assertEquals($row, $model->data);
//            $this->assertEquals(self::$exampleData[$n], $row);
//        }
//        $this->assertEquals(4, $model->controller->next);
//        $this->assertEquals(1, $model->controller->rewind);
//    }
//    function atk4_test_IterableLoaded() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->load('value1.1');
//        foreach($model as $n => $row) {
//            $this->assertTrue($model->loaded(), 'Model must be loaded');
//            $this->assertEquals($row, $model->data);
//            $this->assertEquals(self::$exampleData[$n], $row);
//        }
//        $this->assertEquals(4, $model->controller->next);
//        $this->assertEquals(1, $model->controller->rewind);
//    }
//    function atk4_test_IterableHook() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $tmp = array();
//        $self = $this;
//        $model->addHook('beforeLoad',
//            function() use (&$tmp, $self) {
//                $args = func_get_args();
//                $self->assertEquals('iterating', $args[1]);
//                $self->assertEquals(null, $args[2]);
//                $tmp[] = 'beforeLoad';
//            });
//        $model->addHook('afterLoad',
//            function() use (&$tmp, $self) {
//                $args = func_get_args();
//                $self->assertEquals(1, count($args));
//                $tmp[] = 'afterLoad';
//            });
//        foreach($model as $n => $row) { }
//        $this->assertEquals(7, count($tmp));
//        $this->assertEquals('beforeLoad', $tmp[0]);
//        $this->assertEquals('afterLoad', $tmp[1]);
//        $this->assertEquals('beforeLoad', $tmp[2]);
//        $this->assertEquals('afterLoad', $tmp[3]);
//        $this->assertEquals('beforeLoad', $tmp[4]);
//        $this->assertEquals('afterLoad', $tmp[5]);
//        $this->assertEquals('beforeLoad', $tmp[6]);
//    }
//    function atk4_test_AddCondition() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->addCondition('field1', 'value1.1');
//        $this->assertTrue(isset($model->conditions[0]), 'Model doesn\'t store condition');
//        $this->assertEquals(1, count($model->conditions));
//        $this->assertEquals(array('field1', '=', 'value1.1'), $model->conditions[0]);
//    }
//    function atk4_test_AddConditionArray() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->addCondition(array(
//            array('field1', 'value1.1'),
//            array('field1', '>', 'value2.1')));
//        $this->assertTrue(isset($model->conditions[0][1]), 'Model doesn\'t store condition');
//        $this->assertEquals(2, count($model->conditions));
//        $this->assertEquals(array('field1', '=', 'value1.1'), $model->conditions[0]);
//        $this->assertEquals(array('field1', '>', 'value2.1'), $model->conditions[1]);
//    }
//    function atk4_test_AddConditionOnlyKey() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $this->assertThrowException('BaseException', $model, 'addCondition', array('field1'));
//        $this->assertEquals(0, count($model->conditions));
//    }
//    function atk4_test_AddConditionUnsupported() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->controller->supportConditions = false;
//        $this->assertThrowException('Exception_NotImplemented', $model, 'addCondition', array('field1', 'value1.1'));
//    }
//    function atk4_test_AddConditionOperatorUnsupport() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $e = $this->assertThrowException('Exception_NotImplemented', $model, 'addCondition', array('field1', 'unsupportOperator', 'value1.1'));
//        $this->assertEquals('Unsupport operator', $e->getMessage());
//        $this->assertEquals('unsupportOperator', $e->more_info['operator']);
//    }
//    function atk4_test_SetLimit() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->setLimit(3, 45);
//        $this->assertEquals(array(3, 45), $model->limit);
//    }
//    function atk4_test_DefaultLimit() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $this->assertEquals(array(null, null), $model->limit);
//    }
//    function atk4_test_SetLimitUnsupported() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->controller->supportLimit = false;
//        $this->assertThrowException('Exception_NotImplemented', $model, 'setLimit', array(4));
//    }
//    function atk4_test_SetOrder() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->setOrder('field1', 'desc');
//        $this->assertEquals(array('field1', 'desc'), $model->order);
//    }
//    function atk4_test_DefaultOrder() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $this->assertEquals(array(null, null), $model->order);
//    }
//    function atk4_test_SetOrderUnsupported() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->controller->supportOrder = false;
//        $this->assertThrowException('Exception_NotImplemented', $model, 'setOrder', array('field1'));
//    }
//    function atk4_test_Count() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $count = $model->count();
//        $this->assertEquals(3, $count);
//    }
//    function atk4_test_CountBuiltin() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $count = count($model);
//        $this->assertEquals(3, $count);
//    }
//    function atk4_test_CountUnsupported() {
//        $model = $this->add(get_class($this));
//        $model->setControllerData('Empty');
//        $this->assertThrowException('Exception_NotImplemented', $model, 'count');
//    }
//    function atk4_test_EachWithString() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->each('eachFunction');
//        $this->assertEquals(3, count($model->eachArguments));
//        foreach ($model->eachArguments as $arg) {
//            $this->assertEmpty($arg);
//        }
//    }
//    function atk4_test_EachWithCallable() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->each(array($model, 'eachFunction'));
//        $this->assertEquals(3, count($model->eachArguments));
//        foreach ($model->eachArguments as $arg) {
//            $this->assertEquals(1, count($arg));
//            $this->assertEquals($model, $arg[0]);
//        }
//    }
//    function atk4_test_EachWithCallableBreak() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->stopAt = 3;
//        $model->each(array($model, 'eachFunction'));
//        $this->assertEquals(2, count($model->eachArguments));
//        foreach ($model->eachArguments as $arg) {
//            $this->assertEquals(1, count($arg));
//            $this->assertEquals($model, $arg[0]);
//        }
//    }
//    function atk4_test_HasMany() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->hasMany(get_class($this));
//        $expected = array(
//            get_class($this) => array(
//                get_class($this),
//                UNDEFINED,
//                UNDEFINED,
//            ));
//        $this->assertEquals($expected, $model->_references);
//    }
//    function atk4_test_HasManyAllParamters() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->hasMany(get_class($this), 'other_field', 'my_field', 'reference_name');
//        $expected = array(
//            'reference_name' => array(
//                get_class($this),
//                'other_field',
//                'my_field',
//            ));
//        $this->assertEquals($expected, $model->_references);
//    }
//    function atk4_test_HasManyRefNotLoaded() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->hasMany(get_class($this), 'field1', 'field2', 'parent');
//        $referencedModel = $model->ref('parent');
//        $this->assertTrue($referencedModel instanceof Model_TestModel, 'Return a wrong model type');
//        $expected = array(
//            array('field1', '=', null)
//        );
//        $this->assertEquals($expected, $referencedModel->conditions);
//    }
//    function atk4_test_HasManyRefLoaded() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->hasMany(get_class($this), 'field1', 'field2', 'parent');
//        $model->load('value2.1');
//        $referencedModel = $model->ref('parent');
//        $this->assertTrue($referencedModel instanceof Model_TestModel, 'Return a wrong model type');
//        $expected = array(
//            array('field1', '=', $model->get('field2'))
//        );
//        $this->assertEquals($expected, $referencedModel->conditions);
//    }
//    function atk4_test_HasOne() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->hasOne(get_class($this));
//        $expected = array(
//            'model_testmodel_id' => get_class($model)
//        );
//        $this->assertEquals($expected, $model->_references);
//    }
//    function atk4_test_HasOneAllParameter() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $model->hasOne(get_class($this), 'field2');
//        $expected = array(
//            'field2' => get_class($model)
//        );
//        $this->assertEquals($expected, $model->_references);
//    }
//    function atk4_test_HasOneNewField() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $field = $model->hasOne(get_class($this), 'field4');
//        $this->assertTrue($model->getElement('field4') instanceof Field_Base, 'Model doesn\'t create new field');
//        $this->assertTrue(is_string($field->getModel()), 'Model field hasn\'t model');
//    }
//    function atk4_test_HasOneRefNoLoaded() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $field = $model->hasOne(get_class($this), 'parent_id');
//        $referencedModel = $model->ref('parent_id');
//        $this->assertTrue($referencedModel instanceof Model_TestModel, 'Returned a wrong model type');
//        $this->assertFalse($referencedModel->loaded(), 'Expected a unloaded model');
//    }
//    function atk4_test_HasOneRefNoLoaded2() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $field = $model->hasOne(get_class($this), 'parent_id');
//        $model->set('parent_id', 'value1.3');
//        $data = self::$exampleData;
//        $model->addHook('beforeRefLoad', function($model, $refModel, $id) use ($data) {
//            $refModel->setControllerSource($data);
//        });
//        $referencedModel = $model->ref('parent_id');
//        $this->assertTrue($referencedModel instanceof Model_TestModel, 'Returned a wrong model type');
//        $this->assertTrue($referencedModel->loaded(), 'Expected a unloaded model');
//        $this->assertEquals('value1.3', $referencedModel->id);
//    }
//    function atk4_test_RefFail() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $e = $this->assertThrowException('BaseException', $model, 'ref', array('UnexistentField'));
//        $this->assertEquals($model, $e->more_info['model']);
//        $this->assertEquals('UnexistentField', $e->more_info['ref']);
//        $this->assertFalse($model->hasElement('UnexistentField'), 'Model have UnexistentField');
//    }
//    function atk4_test_HasOneForeignField() {
//        $model = $this->add(get_class($this));
//        $data = self::$exampleData;
//        $data[0]['parent_id'] = 'value1.2';
//        $data[1]['parent_id'] = 'value1.3';
//        $data[2]['parent_id'] = 'value1.1';
//        $model->setControllerSource($data);
//        $hasOneField = $model->hasOne(get_class($this), 'parent_id');
//        $hasOneField->addHook('beforeForeignLoad', function($field, $model, $id) use ($data) {
//            $model->setControllerSource($data);
//        });
//        $model->load('value1.1');
//        $this->assertEquals($data[0]['parent_id'], $model->get('parent_id'));
//        $this->assertEquals($data[1]['field3'], $model->get('parent'));
//    }
//    function atk4_test_GetRows() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $rows = $model->getRows();
//        foreach($model as $n => $row) {
//            $this->assertEquals($row, $rows[$n]);
//        }
//    }
//    function atk4_test_GetRowsWithCalculated() {
//        $model = $this->add('Model_ComplexModel');
//        $rows = $model->getRows();
//        $expected = Model_ComplexModel::$exampleSource;
//        foreach($model as $n => $row) {
//            $this->assertTrue(isset($rows[$n]['parent']), 'Calculated field not valued');
//            unset($rows[$n]['parent']);
//            $this->assertEquals($expected[$n], $rows[$n]);
//        }
//    }
//    function atk4_test_GetRowsWithArgument() {
//        $model = $this->add(get_class($this));
//        $model->setControllerSource(self::$exampleData);
//        $rows = $model->getRows(array('field1', 'field2'));
//        foreach($rows as $row) {
//            $this->assertTrue(isset($row['field1']), 'Expected a valued field1');
//            $this->assertTrue(isset($row['field2']), 'Expected a valued field2');
//            $this->assertFalse(array_key_exists('field3', $row), 'field3 must be not returned');
//        }
//    }
//    function atk4_test_Clone() {
//        $model = $this->add('Model_ComplexModel');
//        $model->addCondition('id', '>', 4);
//        $model->setOrder('title');
//        $model->setLimit(2, 4);
//        $clonedModel = clone $model;
//        $model->addField('field4');
//        $model->addCondition('id', '>', 6);
//        $model->setOrder('description');
//        $model->setLimit(1, 3);
//        $this->assertEquals($model->owner, $clonedModel->owner);
//        $this->assertEquals(1, count($clonedModel->conditions));
//        $this->assertEquals(array('title', null), $clonedModel->order);
//        $this->assertEquals(array(2, 4), $clonedModel->limit);
//        $this->assertFalse($clonedModel->hasElement('field4'), 'Copied a field addded after the cloning...');
//        $this->assertEquals(array_keys($model->_expressions), array_keys($clonedModel->_expressions));
//    }
//    function atk4_test_Reset() {
//        $model = $this->add('Model_ComplexModel');
//        $model->addCondition('id', '>', 4);
//        $model->setOrder('title');
//        $model->setLimit(2, 4);
//        $model->loadAny();
//        $model->reset();
//        $this->assertEmpty($model->conditions);
//        $this->assertEmpty($model->order);
//        $this->assertEmpty($model->limit);
//        $this->assertEmpty($model->data);
//        $this->assertEmpty($model->_table);
//        $this->assertEmpty($model->id);
//    }
//    function atk4_test_ComplexExample() {
//        $model = $this->add('Model_ComplexModel');
//        $model->load(1);
//        $this->assertTrue($model->loaded(), 'Model must be loaded');
//        $this->assertEquals(6, $model->get('parent_id'));
//        $this->assertEquals('Title6', $model->get('parent'));
//        $parentModel = $model->ref('parent_id');
//        $this->assertTrue($parentModel->loaded(), 'Model must be loaded');
//        $this->assertEquals(6, $parentModel->id);
//        $this->assertEquals($model->get('parent_id'), $parentModel->id);
//        $this->assertEquals('Title6', $parentModel->get('title'));
//        $this->assertEquals(5, $parentModel->get('parent_id'));
//        $this->assertEquals('Title5', $parentModel->get('parent'));
//        $gFatherModel = $model->ref('parent_id/parent_id');
//        $this->assertTrue($gFatherModel->loaded(), 'Model must be loaded');
//        $this->assertEquals(5, $gFatherModel->id);
//        $this->assertEquals($parentModel->get('parent_id'), $gFatherModel->id);
//        $this->assertEquals('Title5', $gFatherModel->get('title'));
//        $this->assertEquals(4, $gFatherModel->get('parent_id'));
//        $this->assertEquals('Title4', $gFatherModel->get('parent'));
//        $childModel = $gFatherModel->ref('Model_ComplexModel');
//        $this->assertFalse($childModel->loaded(), 'Model must be not loaded');
//        // The Controller_DataFoo controller don't really load the real row
//        $childModel->loadAny();
//        $this->assertEquals(2, $childModel->id);
//        $expected = array(
//            array('parent_id', '=', $gFatherModel->id)
//        );
//        $this->assertEquals($expected, $childModel->conditions);
//        $this->assertThrowException('BaseException', $gFatherModel, 'ref', array('Model_ComplexModel/Model_ComplexModel'));
//    }
    static private $exampleData = array(
        array('field1' => 'value1.1', 'field2' => 'value2.1', 'field3' => 'value3.1'),
        array('field1' => 'value1.2', 'field2' => 'value2.2', 'field3' => 'value3.2'),
        array('field1' => 'value1.3', 'field2' => 'value2.3', 'field3' => 'value3.3'),
    );

}
