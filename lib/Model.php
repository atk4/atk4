<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Model class improves how you interact with the structured data. Extend
 * this class (or :php:class:`Model_SQL`) to define the structure of your
 * own models, then use object instances to interact with individual records.
 **/
class Model extends AbstractModel implements ArrayAccess, Iterator, Countable
{
    const DOC='model/core';

    public $default_exception='BaseException';

    /**
     * The class prefix used by addField.
     *
     * For some models you might want to use a more powerful field class
     */
    public $field_class='Field_Base';

    /**
     * If true, model will now allow to set values for non-existant fields
     */
    public $strict_fields=false;

    /**
     * Caption is used on buttons of CRUD and other views which operate with this model.
     * If not defined, will use class name. Use singular such as "Purchase".
     * This label is localized.
     */
    public $caption=null;

    /**
     * Contains name of table, session key, collection or file, depending on
     * data controller you are using.
     */
    public $table=null;

    /**
     * Defines aleas for the table, for drivers such as SQL to make your
     * queries prettier. Not really required.
     */
    public $table_alias=null;


    /**
     * Controllers store some custom information in here under key equal to
     * their name. This allows us to use single controller object with
     * multiple objects.
     */
    public $_table=array();

    /**
     * Contains identifier of currently loaded record or null. Changed by load() and reset()
     */
    public $id=null;

    /**
     * The actual ID field of the table might not always be "id". For Mongo this is equal
     * to "_id".
     */
    public $id_field='id';

    /**
     * Name of descriptive field. If not defined, will use table+'#'+id. This
     * field will be used to refer to field where possible. This can
     * be a calculated field or a callback.
     */
    public $title_field='name';

    /**
     * Contains connections with other models which are related to this one
     * through hasOne / hasMany.
     */
    public $_references = array();

    /**
     * Expression
     *
     * TODO: not sure we need to distinguish expressions at this level
     */
    public $_expressions = array();

    /**
     * Contains list of applied conditions. This is because controllers
     * would not store applied conditions, so we need to keep track of them.
     *
     * Please resist the urge to remove conditions from the model - as this
     * is a very dangerous concept. Refer to documentation on conditions
     * for more information.
     */
    public $conditions = array();

    /**
     * Applied limit on the model, first entry is count, second is offset
     */
    public $limit = array(null, null);

    /**
     * Contains requested order definition
     *
     * TODO: must support multiple touples for multiple field sorting
     */
    public $order = array();

    /**
     * More internal classes which are used by hasOne, and addExpression
     */
    protected $defaultHasOneFieldClass = 'Field_HasOne';
    protected $defaultExpressionFieldClass = 'Field_Callback';

    /**
     * Curretly loaded record data. This information is embedded in the
     * model, so you can re-use same model for loading multiple records.
     */
    public $data=array();

    /**
     * Associative list of fields which have been changed since loading.
     * Model will only save fields which have changed.
     */
    public $dirty=array();

    /**
     * Some data controllers make it possible to query selective set of
     * fields. This way the query could be faster and only relevant data
     * is loaded.
     */
    public $actual_fields=false;

    /**
     * Internal use. Used by save().
     */
    protected $_save_as=null;
    protected $_save_later=false;

    // {{{ Basic functionality, field definitions, set(), get() and related methods
    public function __clone()
    {
        parent::__clone();
        foreach ($this->elements as $key => $el) {
            if (is_object($el)) {
                $this->elements[$key] = clone $el;
                $this->elements[$key]->owner=$this;
            }
        }
        foreach ($this->_expressions as $key => $el) {
            $this->_expressions[$key] = clone $el;
            $this->_expressions[$key]->owner = $this;
        }
    }

    public function init()
    {
        parent::init();

        $this->addField($this->id_field)->system(true);
    }

    /**
     * Creates field definition object containing field meta-information such as caption, type
     * validation rules, default value etc
     */
    public function addField($name, $alias = null)
    {
        return $this->add($this->field_class, $name)
            ->actual($alias);
    }

    /**
     * Add expression: the value of those field will calculate when you use get method
     */
    public function addExpression($name, $expression, $field_class = UNDEFINED)
    {
        if ($field_class === UNDEFINED) {
            $field_class = $this->defaultExpressionFieldClass;
        }
        $field = $this->add($field_class, $name)
            ->setExpression($expression);
        $this->_expressions[$name] = $field;

        return $field;
    }

    /**
     * Set value of fields. If only a single argument is specified
     * and it is a hash, will use keys as property names and set values
     * accordingly.
     *
     * @param $name array or string
     * @param $value null or value
     * @return mix
     */
    public function set($name, $value = UNDEFINED)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->set($key, $val);
            }

            return $this;
        } elseif ($value === UNDEFINED) {
            throw $this->exception('You must define the second argument');
        }

        if ($this->strict_fields && !$this->hasElement($name)) {
            throw $this->exception('No such field', 'Logic')
                ->addMoreInfo('field', $name);
        }

        if (($value !== $this->data[$name]) || (is_null($value) && !array_key_exists($name, $this->data))) {
            $this->data[$name] = $value;
            $this->setDirty($name);
        }

        if($name === $this->id_field)$this->id=$value;

        return $this;
    }

    /**
     * Get the value of a model field. If field $name is not specified, then
     * returns associative hash containing all field data
     */
    public function get($name = null)
    {
        if ($name === null) {
            $data = $this->data;
            foreach ($this->_expressions as $key => $field) {
                $data[$key] = $field->getValue($this, $this->data);
            }
            foreach ($this->elements as $key => $field) {
                if ($field instanceof Field || $field instanceof Field_Base) {
                    if ($field->defaultValue() !== null && !isset($data[$key])) {
                        $data[$key] = $field->defaultValue();
                    }
                }
            }

            return $data;
        }

        $f = $this->hasElement($name);
        if ($this->strict_fields && !$f) {
            throw $this->exception('No such field', 'Logic')
                ->addMoreInfo('field', $name);
        }

        if ($f instanceof Field_Calculated) {
            return $f->getValue($this, $this->data);
        }

        // See if we have data for the field
        if (!$this->loaded() && !@array_key_exists($name, $this->data)) {
            if ($f && $f->has_default_value) {
                return $f->defaultValue();
            }

            if ($this->strict_fields) {
                throw $this->exception('Model field was not loaded')
                    ->addMoreInfo('id', $this->id)
                    ->addMoreInfo('field', $name);
            }

            return null;
        }

        return $this->data[$name];
    }

    /**
     * Return all fields that belongs to the specified group.
     * You can use the subtractions group also ("all,-group1,-group2")
     *
     * @param string
     * @return array
     */
    public function getGroupField($group = 'all')
    {
        $toExclude = array();
        $toAdd = array();
        if (strpos($group, ',')!==false) {
            foreach (explode(',', $group) as $g) {
                if ($g[0]==='-') {
                    $toExclude[] = substr($g, 1);
                } else {
                    $toAdd[] = $g;
                }
            }
        } else {
            $toAdd = array($group);
        }

        $fields = array();
        foreach ($this->elements as $el) {
            if (!($el instanceof $this->field_class)) {
                continue;
            }
            $elGroup = $el->group();
            if (
                !in_array($elGroup, $toExclude)
                && (
                    in_array('all', $toAdd)
                    || in_array($elGroup, $toAdd)
                )
            ) {
                $fields[] = $el->short_name;
            }
        }

        return $fields;
    }

    /**
     * Set the actual fields of this model.
     * You can use an array to set the list of fields or a string to
     * use the grouping
     *
     * @param $group string or array
     * @return array the actual fields
     */
    public function setActualFields($group = UNDEFINED)
    {
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
     * Get the actual fields
     *
     * @return array the actual fields
     */
    public function getActualFields()
    {
        if ($this->actual_fields === false) {
            $this->actual_fields = $this->getGroupField();
        }

        return $this->actual_fields;
    }
    /**
     * Return the title field
     *
     * @return string the title field
     */
    public function getTitleField()
    {
        if ($this->title_field && $this->hasElement($this->title_field)) {
            return $this->title_field;
        }

        return $this->id_field;
    }
    /**
     * Set a field as dirty. This is used to force insert/update a field
     *
     * @param $name
     * @return $this
     */
    public function setDirty($name)
    {
        $this->dirty[$name] = true;

        return $this;
    }
    /**
     * Return true if the field is set
     *
     * @param $name
     * @return bool
     */
    public function isDirty($name)
    {
        return $this->dirty[$name] ||
            (!$this->loaded() && $this->getElement($name)->has_default_value);
    }
    // }}}

    // {{{ ArrayAccess support
    public function offsetExists($name)
    {
        return $this->get($name);
    }
    public function offsetGet($name)
    {
        return $this->get($name);
    }
    public function offsetSet($name, $val)
    {
        $this->set($name, $val);
    }
    public function offsetUnset($name)
    {
        unset($this->dirty[$name]);
        unset($this->data[$name]);
    }
    // }}}

    /// {{{ Operation with external Data Controllers
    /**
     * Set the controller data of this model
     *
     * @param $controller
     * @return $this
     */
    public function setControllerData($controller)
    {
        if (is_string($controller)) {
            $controller = $this->api->normalizeClassName($controller, 'Data');
        } elseif (!$controller instanceof Controller_Data) {
            throw $this->exception('Inappropriate Controller. Must extend Controller_Data');
        }
        $this->controller = $this->setController($controller);

        return $this->controller;
    }

    /**
     * Set the source to the controller data.
     * The $table argument depends on the controller data implementation
     *
     * @param $table
     * @return $this
     */
    public function setControllerSource($table = null)
    {
        if (is_null($this->controller)) {
            throw $this->exception('Call setControllerData before');
        }
        $this->controller->setSource($this, $table);
    }

    /**
     * Associate model with a specified data source. The controller could be
     * either a string (postfix for Controller_Data_..) or a class. One data
     * controller may be used with multiple models.
     * If the $table argument is not specified then :php:attr:`Model::table`
     * will be used to find out name of the table / collection
     */
    public function setSource($controller, $table = null)
    {
        $this->setControllerData($controller);
        $this->setControllerSource($table);

        return $this;
    }

    /** Cache controller is used to attempt and load data a little faster then the primary controller */
    public function addCache($controller, $table = null, $priority = 5)
    {
        $controller=$this->api->normalizeClassName($controller, 'Data');

        return $this->setController($controller)
            ->addHooks($this, $priority)
            ->setSource($this, $table);
    }

    /** Returns if certain feature is supported by model */
    public function supports($feature)
    {
        if(!$this->controller)return false;

        $s='support'.$feature;

        return $this->controller->$s;
    }
    // }}}

    // {{{ LOAD METHODS
    /**
     * Like tryLoad method but if the record not found, an exception is thrown
     *
     * @param $id
     * @return $this
     */
    public function load($id)
    {
        if (!$this->controller) {
            throw $this->exception('Unable to load model, setSource() must be set');
        }

        if ($this->loaded()) {
            $this->unload();
        }
        $this->hook('beforeLoad', array('load', array($id)));

        $this->controller->loadById($this, $id);

        $this->endLoad();

        return $this;
    }
    /**
     * Ask the controller data to load the model by a given $id
     *
     * @param $id
     * @return $this
     */
    public function tryLoad($id)
    {
        try {
            $this->load($id);
        } catch (Exception_NotFound $e) {
        }

        return $this;
    }
    /**
     * Like tryLoadAny method but if the record not found, an exception is thrown
     *
     * @return $this
     */
    public function loadAny()
    {
        if ($this->loaded()) {
            $this->unload();
        }
        $this->hook('beforeLoad', array('loadAny', array()));

        $this->controller->loadByConditions($this);

        $this->endLoad();

        return $this;
    }
    /**
     * Retrive the first record that matching the conditions
     *
     * @return $this
     */
    public function tryLoadAny()
    {
        try {
            $this->loadAny();
        } catch (Exception_NotFound $e) {
        }

        return $this;
    }
    /**
     * Like tryLoadBy method but if the record not found, an exception is thrown
     *
     * @param $field
     * @param $cond
     * @param $value
     * @return $this
     */
    public function loadBy($field, $cond, $value = UNDEFINED)
    {
        if ($field==$this->id_field && $value===UNDEFINED) {
            return $this->load($cond);
        }
        if ($field==$this->id_field && cond==='=') {
            return $this->load($value);
        }

        if ($this->loaded()) {
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
    /**
     * Adding temporally condition to the model to retrive the first record
     * The condition specified is removed after the controller data call
     *
     * @param $field
     * @param $cond
     * @param $value
     * @return $this
     */
    public function tryLoadBy($field, $cond = UNDEFINED, $value = UNDEFINED)
    {
        try {
            $this->loadBy($field, $cond, $value);
        } catch (Exception_NotFound $e) {
        }

        return $this;

    }
    private function endLoad()
    {
        if ($this->loaded()) {
            $this->hook('afterLoad');
            $this->dirty = array();
        }
    }
    // END LOAD METHODS }}}

    /**
     * Returns true if the model is loaded
     *
     * @return boolean
     */
    public function loaded()
    {
        return !is_null($this->id);
    }

    /**
     * Forget loaded data
     *
     * @return $this
     */
    public function unload()
    {
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
    public function reset()
    {
        $ret = $this->unload();
        $this->_table = array();
        $this->conditions = array();
        $this->order = array();
        $this->limit = array();

        return $ret;
    }

    /**
     * Saves record with current controller. Ues $this->id as primary key. If
     * not set, new record iscreated.
     */
    public function save()
    {
        $this->hook('beforeSave', array($this->id));

        $is_update = $this->loaded();
        if ($is_update) {

            $source=array();
            foreach ($this->dirty as $name => $junk) {
                $source[$name]=$this->get($name);
            }

            // No save needed, nothing was changed
            if(!$source)return $this;

            $this->hook('beforeUpdate', array(&$source));
        } else {

            // Collect all data of a new record
            $source = $this->get();

            $this->hook('beforeInsert', array(&$source));
        }

        if ($this->controller) {
            $this->id = $this->controller->save($this, $this->id, $source);
        }

        if ($is_update) {
            $this->hook('afterUpdate');
        } else {
            $this->hook('afterInsert');
        }

        if ($this->loaded()) {
            $this->dirty = array();
            $this->hook('afterSave', array($this->id));
        }

        return $this;
    }

    /**
     * Save model and don't try to load it back
     */
    public function saveAndUnload($id = undefined)
    {
        // TODO: See dc032a9ae75341fb7f4ed6c4de61ca224ec0e5e6. Need to
        // revert and make sure save() is not re-loading the record.
        // (performance)
        $this->save($id);
        $this->unload();

        return $this;
    }
    /** Will save model later, when it's being destructed by Garbage Collector */
    public function saveLater()
    {
        $this->_save_later=true;
        $this->api->addHook('saveDelayedModels', $this);

        return $this;
    }
    public function saveDelayedModels()
    {
        if ($this->_save_later && $this->dirty) {
            $this->saveAndUnload();
            $this->_save_later=false;
        }
    }
    public function __destruct()
    {
        $this->saveDelayedModels();
    }

    /**
     * Delete a record. If the model is loaded, delete the current id.
     * If not loaded, load model through the $id parameter and delete
     */
    public function delete($id = null)
    {
        if ($this->loaded() && !is_null($id) && ($id !== $this->id)) {
            throw $this->exception('Unable to determine which record to delete');
        }
        if (!is_null($id) && (!$this->loaded() || ($this->loaded() && $id !== $this->id))) {
            $this->load($id);
        }
        if (!$this->loaded()) {
            throw $this->exception('Unable to determine which record to delete');
        }
        $id = $this->id;

        $this->hook('beforeDelete', array($id));
        $this->controller->delete($this, $id);
        $this->hook('afterDelete', array($id));

        $this->unload();

        return $this;
    }

    /**
     * Deletes all records associated with this model.
     */
    public function deleteAll()
    {
        if ($this->loaded()) {
            $this->unload();
        }
        $this->hook('beforeDeleteAll');
        $this->controller->deleteAll($this);
        $this->hook('afterDeleteAll');

        return $this;
    }

    /**
     * Unloads then loads current record back. Use this if you have added new fields
     */
    public function reload()
    {
        return $this->load($this->id);
    }

    // {{{ Ordering and limiting support
    /** Adds a new condition for this model */
    public function addCondition($field, $operator = UNDEFINED, $value = UNDEFINED)
    {
        if (!$this->controller) {
            throw $this->exception('Controller for this model is not set', 'NotImplemented');

        }
        if (!@$this->controller->supportConditions) {
            throw $this->exception('The controller doesn\'t support conditions', 'NotImplemented')
                ->addMoreInfo('controller',$this->controller);
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
    public function setLimit($count, $offset = null)
    {
        if (!$this->controller->supportLimit) {
            throw $this->exception('The controller doesn\'t support limit', 'NotImplemented');
        }
        $this->limit = array($count, $offset);

        return $this;
    }
    public function setOrder($field, $desc = null)
    {
        if (!$this->controller->supportOrder) {
            throw $this->exception('The controller doesn\'t support order', 'NotImplemented');
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

        $this->order[] = array($field, $desc);

        return $this;
    }
    /**
     * Count records of model
     *
     * @param string $alias Optional alias of count result
     *
     * @return integer
     */
    public function count($alias = null)
    {
        if ($this->controller && $this->controller->hasMethod('count')) {
            return $this->controller->count($this, $alias);
        }
        throw $this->exception('The controller doesn\'t support count', 'NotImplemented')
            ->addMoreInfo('controller', $this->controller ? $this->controller->short_name : 'none');
    }
    // }}}

    // {{{ Iterator support
    public $_cursor=null;
    public function rewind()
    {
        $this->unload();
        $this->_cursor = $this->controller->prefetchAll($this);
        $this->next();
    }
    public function next()
    {
        $this->hook('beforeLoad', array('iterating'));
        $this->controller->loadCurrent($this, $this->_cursor);
        if ($this->loaded()) {
            $this->hook('afterLoad');
        }

        return $this;
    }
    public function current()
    {
        return $this;
    }
    public function key()
    {
        return $this->id;
    }
    public function valid()
    {
        return $this->loaded();
    }

    public function getRows($fields = null)
    {
        $result=array();
        foreach ($this as $row) {
            if (is_null($fields)) {
                $result[]=$row;
            } else {
                $tmp=array();
                foreach ($fields as $field) {
                    $tmp[$field]=$row[$field];
                }
                $result[]=$tmp;
            }
        }

        return $result;
    }
    // }}}


    public function hasField($name)
    {
        return $this->hasElement($name);
    }
    public function getField($f)
    {
        return $this->getElement($f);
    }

    // {{{ Relational methods
    public function hasOne($model, $our_field = UNDEFINED, $field_class = UNDEFINED)
    {
        if ($field_class === UNDEFINED) {
            $field_class = $this->defaultHasOneFieldClass;
        }

        if ($our_field === UNDEFINED) {
            // guess our field
            $tmp = $this->api->normalizeClassName($model, 'Model');
            $tmp = new $tmp(); // avoid recursion
            $refFieldName = ( $tmp->table ?: strtolower(get_class($this)) ) . '_id';
        } else {
            $refFieldName = $our_field;
        }
        $displayFieldName = preg_replace('/_id$/', '', $our_field);

        $field = $this->addField($refFieldName);

        if (!@$this->controller->supportExpressions) {
            $expr = $this->addExpression($displayFieldName, $model, $field_class)
                ->setModel($model)
                ->setForeignFieldName($refFieldName);
            $this->_references[$refFieldName] = $model;

            return $expr;
        }

        return $field;

    }
    public function hasMany($model, $their_field = UNDEFINED, $our_field = UNDEFINED, $reference_name = null)
    {
        $class = $this->api->normalizeClassName($model, 'Model');
        if (is_null($reference_name)) {
            $reference_name=$model;
        }
        $this->_references[$reference_name] = array($class, $their_field, $our_field);

        return null;
    }
    /** Defines contained model for field */
    function containsOne($field, $model) {
        if(is_array($field) && $field[0]) {
            $field['name'] = $field[0];
            unset($field[0]);
        }
        if($e = $this->hasElement(is_string($field)?$field:$field['name']))$e->destroy();
        $this->add('Relation_ContainsOne', $field)
            ->setModel($model);
    }
    /** Defines multiple contained models for field */
    function containsMany($field, $model) {
        if(is_array($field) && $field[0]) {
            $field['name'] = $field[0];
            unset($field[0]);
        }
        if($e = $this->hasElement(is_string($field)?$field:$field['name']))$e->destroy();
        $this->add('Relation_ContainsMany', $field)
            ->setModel($model);
    }
    /**
     * Traverses reference of relation
     *
     * @param  [type] $ref1 [description]
     * @return [type]       [description]
     */
    public function ref($ref1)
    {
        list($ref,$rest)=explode('/', $ref1, 2);

        if (!isset($this->_references[$ref])) {

            $e = $this->hasElement($ref);
            if($e && $e->hasMethod('ref')){
                return $e->ref();
            }else{
                throw $this->exception('Reference is not defined')
                    ->addMoreInfo('model', $this)
                    ->addMoreInfo('ref', $ref);
            }
        }

        $class = $this->_references[$ref];
        if (is_array($class)) { // hasMany
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
            if ($id) {
                $this->hook('beforeRefLoad', array($m, $id));
                $m->load($id);
            }
        }
        if ($rest) {
            $m = $m->ref($rest);
        }

        return $m;
    }
    private function _ref($class, $field, $val)
    {
        $m = $this->add($this->api->normalizeClassName($class, 'Model'));

        if ($field) { // HasMany
            if($m->supportConditions){
                $m->addCondition($field, $val);
            }else{
                throw $this->exception('Related model does not supprot conditions');
            }
        }

        return $m;
    }
    // }}}

    public function debug($debug = true)
    {
        //if($this->controller)$this->controller->debug($debug);
        return parent::debug($debug);
    }
}
