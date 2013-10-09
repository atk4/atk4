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
 * Implementation of a Generic Model. 
 * @link http://agiletoolkit.org/doc/model
 *
 * Model has fields which you add with addField() and access through get() and set()
 * You can also load and save model through different storage controllers.
 *
 * This model is designed to work with linear, non-SQL resources, if you are looking
 * to have support for joins, ordering, advanced SQL syntax, look into SQL_Model
 *
 * It's recommended that you create your own model class based on generic model where
 * you define fields, but you may also use instance of generic model.
 *
 * Use:
 * class Model_PageCache extends Model {
 *     function init(){
 *         parent::init();
 *         $this->addField('content')->allowHtml(true);
 *     }
 *     function generateContent(){
 *         //complex computation
 *         // ...
 *         $this->set('content',$content);
 *     }
 * }
 *
 *
 * $pc=$this->add('Model_PageCache')->addCache('Memcached');
 * $pc->load($this->api->page);
 *
 * if(!$pc->loaded()){
 *     $pc->set('page',$this->api->page');
 *     $pc->generateContent();
 *     $pc->save();
 * }
 *
 *
 * @license See http://agiletoolkit.org/about/license
 * 
 **/
class Model extends AbstractModel implements ArrayAccess,Iterator,Countable {

    public $default_exception='BaseException';

    /** The class prefix used by addField */
    public $field_class='Field';

    /** If true, model will now allow to set values for non-existant fields */
    public $strict_fields=false;

    /** Caption is used on buttons of CRUD and other views which operate with this model.
     * If not defined, will use class name. Use singular */
    public $caption=null;

    /** Contains name of table, session key, collection or file, depending on a driver */
    public $table=null;

    /** Controllers store some custom informatio in here under key equal to their name */
    public $_table=array();

    /** Contains identifier of currently loaded record or null. Use load() and reset() */
    public $id=null;     // currently loaded record

    /** The actual ID field of the table might not always be "id" */
    public $id_field='id';   // name of ID field

    public $title_field='name';  // name of descriptive field. If not defined, will use table+'#'+id

    // references
    public $_references = array();
    // expression
    public $_expressions = array();

    public $conditions = array();
    public $limit = array(null, null);
    public $order = array(null, null);

    // Curretly loaded record
    public $data=array();
    public $dirty=array();

    public $actual_fields=false;// Array of fields which will be used in further select operations. If not defined, all fields will be used.

    protected $_save_as=null;
    protected $_save_later=false;

    // {{{ Basic functionality, field definitions, set(), get() and related methods
    function __clone(){
        parent::__clone();
        foreach($this->elements as $key => $el) {
            if(is_object($el)) {
                $this->elements[$key] = clone $el;
                $this->elements[$key]->owner=$this;
            }
        }
        foreach ($this->_expressions as $key => $el) {
            $this->_expressions[$key] = clone $el;
            $this->_expressions[$key]->owner = $this;
        }
    }
    /** Creates field definition object containing field meta-information such as caption, type
     * validation rules, default value etc */
    function addField($name, $alias=null) {
        return $this->add($this->field_class, $name)
            ->actual($alias);
    }
    function addExpression($name, $expression, $field_class=UNDEFINED) {
        if ($field_class === UNDEFINED) {
            $field_class = 'Field_Callback';
        }
        $field = $this->add($field_class, $name)
            ->setExpression($expression);
        $this->_expressions[$name] = $field;
        return $field;
    }
    /** Set value of the field. If $this->strict_fields, will throw exception for non-existant fields. Can also accept array */
    function set($name,$value=UNDEFINED) {
        if(is_array($name)) {
            foreach($name as $key=>$val) {
                $this->set($key,$val);
            }
            return $this;
        } elseif ($value === UNDEFINED) {
            throw $this->exception('You must define the second argument');
        }

        if ($this->strict_fields && !$this->hasElement($name)) {
            throw $this->exception('No such field','Logic')
                ->addMoreInfo('field',$name);
        }

        if (($value !== $this->data[$name]) || (is_null($value) && !array_key_exists($name, $this->data))) {
            $this->data[$name] = $value;
            $this->setDirty($name);
        }
        return $this;
    }
    /** Return value of the field. If unspecified will return array of all fields.  */
    function get($name=null) {
        if($name === null) {
            $data = $this->data;
            foreach ($this->_expressions as $key => $field) {
                $data[$key] = $field->getValue($this, $this->data);
            }
            return $data;
        }

        $f = $this->hasElement($name);
        if($this->strict_fields && !$f) {
            throw $this->exception('No such field','Logic')
                ->addMoreInfo('field',$name);
        }

        if ($f instanceof Field_Calculated) {
            return $f->getValue($this, $this->data);
        }

        // See if we have data for the field
        if(!$this->loaded() && !array_key_exists($name, $this->data)) {
            if($f && $f->has_default_value) {
                return $f->defaultValue();
            }

            if($this->strict_fields) {
                throw $this->exception('Model field was not loaded')
                    ->addMoreInfo('id',$this->id)
                    ->addMoreInfo('field',$name);
            }
            return null;
        }
        return $this->data[$name];
    }

    function getGroupField($group='all') {
        $toExclude = array();
        $toAdd = array();
        if (strpos($group, ',')!==false) {
            foreach(explode(',', $group) as $g) {
                if($g[0]==='-') {
                    $toExclude[] = substr($g, 1);
                } else {
                    $toAdd[] = $g;
                }
            }
        } else {
            $toAdd = array($group);
        }

        $fields = array();
        foreach($this->elements as $el) {
            if(!($el instanceof Field)) {
                continue;
            }
            $elGroup = $el->group();
            if (!in_array($elGroup, $toExclude) && 
                    (in_array('all', $toAdd) || 
                    in_array($elGroup, $toAdd)))
            {
                $fields[] = $el->short_name;
            }
        }

        return $fields;
    }

    /**
     * Default set of fields which will be included into further queries
     * 
     * @param array $fields Array of fieldnames
     * @return $this
     */
    function setActualFields($group=UNDEFINED) {
        if (is_array($group)) {
            $this->actual_fields = $group;
            return $this;
        }
        if ($group === UNDEFINED) {
            $group = 'all';
        }
        $this->actual_fields = $this->getGroupField($group);
        return $this;
    }
    /**
     * Returns list of fieldnames set by setActualFiels
     * 
     * @return array
     */
    function getActualFields() {
        if ($this->actual_fields === false) {
            $this->actual_fields = $this->getGroupField();
        }
        return $this->actual_fields;
    }
    /** Returns field which should be used as a title */
    function getTitleField(){
        if($this->title_field && $this->hasElement($this->title_field)) {
            return $this->title_field;
        }
        return $this->id_field;
    }
    /**
     * When fields are changed, they are marked dirty.
     * Only dirty fields are saved when save() is called
     * 
     * @param string $name Name of field
     * @return $this
     */
    function setDirty($name) {
        $this->dirty[$name] = true;
        return $this;
    }
    function isDirty($name) {
        return $this->dirty[$name] || 
            (!$this->loaded() && $this->getElement($name)->has_default_value);
    }
    // }}}

    // {{{ ArrayAccess support 
    function offsetExists($name) {
        return $this->get($name);
    }
    function offsetGet($name){
        return $this->get($name);
    }
    function offsetSet($name,$val){
        $this->set($name,$val);
    }
    function offsetUnset($name){
        unset($this->dirty[$name]);
        unset($this->data[$name]);
    }
    // }}}

    /// {{{ Operation with external Data Controllers
    function setControllerData($controller) {
        if(is_string($controller)) {
            $controller=$this->api->normalizeClassName($controller,'Data');
        } elseif(!$controller instanceof Controller_Data) {
            throw $this->exception('Inappropriate Controller. Must extend Controller_Data');
        }
        $this->controller=$this->setController($controller);
        return $this->controller;
    }

    /*
     * $table is managed by Controller_Data. It's depends on Controller_Data implementation
     */
    function setControllerSource($table=null) {
        if (is_null($this->controller)) {
            throw $this->exception('Call setControllerData before');
        }
        $this->controller->setSource($this, $table);
    }

    /** Associates appropriate controller and loads data such as 'Array' for Controller_Data_Array class */
    function setSource($controller, $table=null, $id=null) {
        $this->setControllerData($controller);
        $this->setControllerSource($table);

        if($id) {
            $this->load($id);
        }
        return $this;
    }
    /** Cache controller is used to attempt and load data a little faster then the primary controller */
    function addCache($controller, $table=null, $priority=5){
        $controller=$this->api->normalizeClassName($controller,'Data');
        return $this->setController($controller)
            ->addHooks($this,$priority)
            ->setSource($this,$table);
    }
    // }}}

    // {{{ LOAD METHODS
    function load($id) {
        $this->tryLoad($id);
        if(!$this->loaded()) {
            throw $this->exception('Record with specified id was not found');
        }
        return $this;
    }
    function tryLoad($id) {
        if($this->loaded()) {
            $this->unload();
        }
        $this->hook('beforeLoad',array('load', array($id)));

        $this->controller->loadById($this,$id);

        $this->endLoad();
        return $this;
    }
    function loadAny() {
        $this->tryLoadAny();
        if(!$this->loaded()) {
            throw $this->exception('Record not found');
        }
        return $this;
    }
    function tryLoadAny() {
        if($this->loaded()) {
            $this->unload();
        }
        $this->hook('beforeLoad', array('loadAny', array()));

        $this->controller->loadByConditions($this);

        $this->endLoad();
        return $this;
    }
    function loadBy($field, $cond, $value=UNDEFINED) {
        $this->tryLoadBy($field, $cond, $value);
        if(!$this->loaded()) {
            throw $this->exception('Record not found');
        }
        return $this;
    }
    function tryLoadBy($field, $cond=UNDEFINED, $value=UNDEFINED) {
        if($this->loaded()) {
            $this->unload();
        }
        $this->hook('beforeLoad', array('loadBy', array($field, $cond, $value)));

        $conditions = $this->conditions;
        $this->addCondition($field, $cond, $value);
        $this->controller->loadByConditions($this);
        $this->conditions = $conditions;

        $this->endLoad();
        return $this;
    }
    private function endLoad() {
        if($this->loaded()) {
            $this->hook('afterLoad');
            $this->dirty = array();
        }
    }
    // END LOAD METHODS }}}

    /**
     * Returns true if the records has been loaded successfully
     * 
     * @return boolean
     */
    function loaded() {
        return !is_null($this->id);
    }

    /**
     * Forget loaded data
     * 
     * @return $this
     */
    function unload() {
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
        return $this;
    }
    /**
     * Same as unload() method
     * 
     * @return $this
     */
    function reset() {
        $ret = $this->unload();
        $this->_table = array();
        $this->conditions = array();
        $this->order = array();
        $this->limit = array();
        return $ret;
    }

    /** Saves record with current controller. If no argument is specified, uses $this->id. Specifying "false" will create 
     * record with new ID. */
    function save() {
        $this->hook('beforeSave', array($this->id));

        $is_update = $this->loaded();
        if($is_update) {
            $this->hook('beforeUpdate');
        } else {
            $this->hook('beforeInsert');
        }

        $source = $this->get();
        // remove calculated fields
        // ...
        $this->id = $this->controller->save($this, $this->id, $source);

        if($is_update) {
            $this->hook('afterUpdate');
        } else {
            $this->hook('afterInsert');
        }

        if($this->loaded()) {
            $this->dirty = array();
            $this->hook('afterSave', array($this->id));
        }
        return $this;
    }

    /** Save model and don't try to load it back */
    function saveAndUnload($id=undefined){
        // TODO: See dc032a9ae75341fb7f4ed6c4de61ca224ec0e5e6. Need to 
        // revert and make sure save() is not re-loading the record.
        // (performance)
        $this->save($id);
        $this->unload();
        return $this;
    }
    /** Will save model later, when it's being destructed by Garbage Collector */
    function saveLater(){
        $this->_save_later=true;
        $this->api->addHook('saveDelayedModels',$this);
        return $this;
    }
    function saveDelayedModels(){
        if($this->_save_later && $this->dirty){
            $this->saveAndUnload();
            $this->_save_later=false;
        }
    }
    function __destruct(){
        $this->saveDelayedModels();
    }
    
    /*
     * Delete a record. If the model is loaded, delete the current id. 
     * If not loaded, load model through the $id parameter and delete
     */
    function delete($id=null) {
        if ($this->loaded() && !is_null($id) && ($id !== $this->id)) {
            throw $this->exception('Unable to determine which record to delete');
        }
        if(!is_null($id) && (!$this->loaded() || ($this->loaded() && $id !== $this->id))) {
            $this->load($id);
        }
        if(!$this->loaded()) {
            throw $this->exception('Unable to determine which record to delete');
        }
        $id = $this->id;

        $this->hook('beforeDelete',array($id));
        $this->controller->delete($this, $id);
        $this->hook('afterDelete',array($id));

        $this->unload();
        return $this;
    }
    /** Deletes all records associated with this model. */
    function deleteAll(){
        if($this->loaded()) {
            $this->unload();
        }
        $this->hook('beforeDeleteAll');
        $this->controller->deleteAll($this);
        $this->hook('afterDeleteAll');
        return $this;
    }

    /** Unloads then loads current record back. Use this if you have added new fields */
    function reload(){
        return $this->load($this->id);
    }

    // {{{ Ordering and limiting support
    /** Adds a new condition for this model */
    function addCondition($field, $operator=UNDEFINED, $value=UNDEFINED) {
        if (!$this->controller->supportConditions) {
            throw $this->exception('The controller doesn\'t support conditions', 'NotImplemented');
        }
        if (is_array($field)) {
            foreach ($field as $value) {
                $this->addCondition($value[0], $value[1], count($value) === 2 ? UNDEFINED : $value[2]);
            }
            return $this;
        } elseif (($operator === UNDEFINED) && (!is_object($field))) { // controller can handle objects
            throw $this->exception('You must define the second argument');
        }
        if ($value === UNDEFINED) {
            $value = $operator;
            $operator = '=';
        }
        $supportOperators = $this->controller->supportOperators;
        if ($supportOperators !== 'all' && ( is_null($supportOperators) || (!isset($supportOperators[$operator])))) {
            throw $this->exception('Unsupport operator', 'NotImplemented')
                ->addMoreInfo('operator', $operator);
        }

        $this->conditions[] = array($field, $operator, $value);
        return $this;
    }
    function setLimit($count, $offset=null) {
        if(!$this->controller->supportLimit) {
            throw $this->exception('The controller doesn\'t support limit', 'NotImplemented');
        }
        $this->limit = array($count, $offset);
        return $this;
    }
    function setOrder($field, $desc=null) {
        if(!$this->controller->supportOrder) {
            throw $this->exception('The controller doesn\'t support order', 'NotImplemented');
        }
        $this->order = array($field, $desc);
        return $this;
    }
    /**
     * Count records of model
     * 
     * @param string $alias Optional alias of count result
     * 
     * @return integer
     */
    function count($alias=null) {
        if($this->controller && $this->controller->hasMethod('count')) {
            return $this->controller->count($this, $alias);
        } 
        throw $this->exception('The controller doesn\'t support count', 'NotImplemented')
            ->addMoreInfo('controller', $this->controller ? $this->controller->short_name : 'none');
    }
    // }}}

    // {{{ Iterator support 
    function rewind() {
        $this->unload();
        $this->controller->prefetchAll($this);
        $this->next();
    }
    function next() {
        $this->hook('beforeLoad', array('iterating'));
        $this->controller->loadCurrent($this);
        if($this->loaded()) {
            $this->hook('afterLoad');
        }
        return $this;
    }
    function current() {
        return $this->get();
    }
    function key() {
        return $this->id;
    }
    function valid(){
        return $this->loaded();
    }

    function getRows($fields=null) {
        $result=array();
        foreach($this as $row){
            if (is_null($fields)) {
                $result[]=$row;
            } else {
                $tmp=array();
                foreach($fields as $field){
                    $tmp[$field]=$row[$field];
                }
                $result[]=$tmp;
            }
        }
        return $result;
    }

    /**
     * A handy shortcut for foreach(){ .. } code. Make your callable return
     * "false" if you would like to break the loop.
     *
     * @param callable $callable will be executed for each member
     *
     * @return AbstractObject $this
     */
    function each($callable) {
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
    // }}}


    // TODO: worry about cloning!
    function newField($name) {
        return $this->addField($name); 
    }
    function hasField($name) {
        return $this->hasElement($name);
    }
    function getField($f) {
        return $this->getElement($f);
    }


    // {{{ Relational methods
    function hasOne($model, $our_field=UNDEFINED, $field_class='Field_HasOne') {
        $tmp = $this->api->normalizeClassName($model, 'Model');
        $tmp = new $tmp; // avoid recursion

        if ($our_field === UNDEFINED) {
            $refFieldName = ( $tmp->table ? : strtolower(get_class($this)) ) . '_id';
        } else {
            $refFieldName = $our_field;
        }
        $displayFieldName = preg_replace('/_id$/', '', $our_field);
        
        if (!$this->hasElement($refFieldName)) {
            $this->addField($refFieldName);
        }
        $expr = $this->addExpression($displayFieldName, $model, $field_class)
            ->setModel($model)
            ->setForeignFieldName($refFieldName);
        $this->_references[$refFieldName] = $model;
        return $expr;
    }
    function hasMany($model, $their_field=UNDEFINED, $our_field=UNDEFINED, $reference_name=null) {
        $class = $this->api->normalizeClassName($model, 'Model');
        if(is_null($reference_name)) {
            $reference_name=$model;
        }
        $this->_references[$reference_name] = array($class, $their_field, $our_field);
        return null;
    }
    function ref($ref1) {
        list($ref,$rest)=explode('/', $ref1, 2);

        if(!isset($this->_references[$ref])) {
            throw $this->exception('Reference is not defined')
                ->addMoreInfo('model',$this)
                ->addMoreInfo('ref',$ref);
        }

        $class = $this->_references[$ref];
        if(is_array($class)) { // hasMany
            if ($rest) {
                throw $this->exception('Cannot traverse multiple references');
            }
            $m = $this->_ref(
                $class[0],
                $class[1] && $class[1]!=UNDEFINED ? $class[1] : $this->table.'_id',
                $class[2] && $class[2]!=UNDEFINED ? $this[$class[2]] : $this->id 
            );
        } else { // hasOne
            $id = $this->get($ref);
            $m = $this->_ref(
                $class,
                null,
                $id
            );
            if($id) {
                $this->hook('beforeRefLoad', array($m, $id));
                $m->load($id);
            }
        }
        if ($rest) {
            $m = $m->ref($rest);
        }
        return $m;
    }
    private function _ref($class,$field,$val) {
        $m = $this->add($this->api->normalizeClassName($class, 'Model'));

        // HasMany
        if($field) {
            $m->addCondition($field,$val);
        }
        return $m;
    }
    // }}}
}
