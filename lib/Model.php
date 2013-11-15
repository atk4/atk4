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
class Model extends AbstractModel implements ArrayAccess,Iterator,Serializable {

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


    // Curretly loaded record
    public $data=array();
    public $dirty=array();

    public $actual_fields=false;// Array of fields which will be used in further select operations. If not defined, all fields will be used.

    protected $_save_as=null;
    protected $_save_later=false;

    // {{{ Basic functionality, field definitions, set(), get() and related methods
    function init(){
        parent::init();

        if(method_exists($this,'defineFields'))
            throw $this->exception('model->defineField() is obsolete. Change to init()','Obsolete')
            ->addMoreInfo('class',get_class($this));
    }
    function __clone(){
        parent::__clone();
        foreach($this->elements as $key=>$el)if(is_object($el)){
            $this->elements[$key]=clone $el;
            $this->elements[$key]->owner=$this;
        }
    }
    /** Creates field definition object containing field meta-information such as caption, type
     * validation rules, default value etc */
    function addField($name){
        return $this
            ->add($this->field_class,$name);
    }
    /** Set value of the field. If $this->strict_fields, will throw exception for non-existant fields. Can also accept array */
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

        if ($value !== undefined 
            && (
                // if data[$name] is not initialized at all (for example, in model using array controller)
                (is_array($this->data) && !array_key_exists($name, $this->data))
                // value as object
                || is_object($value)
                || is_object($this->data[$name])
                // value as array
                || is_array($value)
                || is_array($this->data[$name])
                // if one and only one value is NULL
                || (is_null($value) xor is_null($this->data[$name]))
                // values converted to string loosly differ
                // need special treatment of [false] value because (string)false === "" not "0"
                || ($value===false ? '0' : (string)$value) // this is not nice
                   !=
                   ($this->data[$name]===false ? '0' : (string)$this->data[$name])
            )
        ) {
            $this->data[$name]=$value;
            $this->setDirty($name);
        }
        return $this;
    }
    /** Return value of the field. If unspecified will return array of all fields.  */
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
                ->addMoreInfo('field',$name);

            return null;
        }
        return $this->data[$name];
    }
    /**
     * Returns list of fieldnames or field objects which belong to specific group
     * 
     * You can add fields into groups when you define them and it can be used by
     * the front-end to determine which fields needs to be displayed.
     * 
     * If no group is specified, then all non-system fieldnames (or fields) are
     * returned for backwards compatibility.
     * 
     * You can pass multiple groups as CSV and add "-" prefix to groupname to
     * exclude all fields from this specific group in result set.
     * 
     * @param string $group      Name of field group or CSV of them
     * @param bool   $as_objects If set true, then return array of field objects
     * @return array
     */
    function getActualFields($group = undefined, $as_objects = false)
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
                    $fields[] = $as_objects ? $el : $el->short_name;
                }
            }
        }

        return $fields;
    }
    /** Returns field which should be used as a title */
    function getTitleField(){
        if($this->title_field && $this->hasElement($this->title_field))return $this->title_field;
        return $this->id_field;
    }
    /**
     * Default set of fields which will be included into further queries
     * 
     * @param array $fields Array of fieldnames
     * @return $this
     */
    function setActualFields(array $fields)
    {
        $this->actual_fields = $fields;
        return $this;
    }
    /**
     * When fields are changed, they are marked dirty.
     * Only dirty fields are saved when save() is called
     * 
     * @param string $name Name of field
     * @return $this
     */
    function setDirty($name)
    {
        $this->dirty[$name] = true;
        return $this;
    }
    function isDirty($name){
        return $this->dirty[$name] || 
            (!$this->loaded() && $this->getElement($name)->has_default_value);
    }
    /**
     * Returns true if the records has been loaded successfully
     * 
     * @return boolean
     */
    function loaded()
    {
        return !is_null($this->id);
    }
    /**
     * Forget loaded data
     * 
     * @return $this
     */
    function unload()
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
    function reset()
    {
        return $this->unload();
    }
    // }}}

    // {{{ ArrayAccess support 
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
    // }}}

    /// {{{ Operation with external Data Controllers

    /** Associates appropriate controller and loads data such as 'Array' for Controller_Data_Array class */
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
    /** Cache controller is used to attempt and load data a little faster then the primary controller */
    function addCache($controller, $table=null, $priority=5){
        $controller=$this->api->normalizeClassName($controller,'Data');
        return $this->setController($controller)
            ->addHooks($this,$priority)
            ->setSource($this,$table);
    }
    /** Attempt to load record with specified ID. If this fails, exception is thrown */
    function load($id){
        if($this->loaded())$this->unload();
        $this->hook('beforeLoad',array($id));
        if(!$this->loaded())$this->controller->load($this,$id);
        if(!$this->loaded())throw $this->exception('Record with specified id was not found');
        $this->hook('afterLoad');
        return $this;
    }
    /** Saves record with current controller. If no argument is specified, uses $this->id. Specifying "false" will create 
     * record with new ID. */
    function save($id=undefined){
        if($this->id_field && $id!==undefined && $id!==null){
            $this->data[$this->id_field]=$id;
        }
        if($id!==undefined)$this->id=$id;


        $this->hook('beforeSave',array($this->id));

        $is_update=$this->loaded();
        if($is_update){
            $this->hook('beforeUpdate');
        }else{
            $this->hook('beforeInsert');
        }

        $this->id=$this->controller->save($this,$this->id);

        if($is_update){
            $this->hook('afterUpdate');
        }else{
            $this->hook('afterInsert');
        }

        if($this->loaded())$this->hook('afterSave',array($this->id));
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
    /** Deletes record associated with specified $id. If not specified, currently loaded record is deleted (and unloaded) */
    function delete($id=null){
        if($id===null)$id=$this->id;
        if($this->loaded() && $this->id == $id)$this->unload();   // record we are about to delete is loaded, unload it.
        $this->hook('beforeDelete',array($id));
        $this->controller->delete($this,$id);
        $this->hook('afterDelete',array($id));
        return $this;
    }
    /** Deletes all records associated with this model. */
    function deleteAll(){
        if($this->loaded())$this->unload();
        $this->hook('beforeDeleteAll');
        $this->controller->deleteAll($this);
        $this->hook('afterDeleteAll');
        return $this;
    }
    /** Adds a new condition for this model */
    function addCondition($field,$operator=UNDEFINED,$value=UNDEFINED){
        $this->controller->addCondition($this,$field,$operator,$value);
        return $this;
    }
    // }}}

    // {{{ Load Wrappers

    /* Attempt to load record with specified ID. If this fails, no error is produced */
    function tryLoad($id=null){
        if($this->loaded())$this->unload();
        $this->hook('beforeLoad',array($id));
        if(!$this->loaded())$this->controller->tryLoad($this,$id);
        if(!$this->loaded())return $this;
        $this->hook('afterLoad');
        return $this;
    }
    function loadAny(){
        if($this->loaded())$this->unload();
        if(!$this->loaded())$this->controller->loadAny($this);
        if(!$this->loaded())return $this;
        $this->hook('afterLoad');
        return $this;
    }
    function tryLoadAny(){
        if($this->loaded())$this->unload();
        if(!$this->loaded())$this->controller->tryLoadAny($this);
        if(!$this->loaded())return $this;
        $this->hook('afterLoad');
        return $this;
    }
    function tryLoadBy($field,$cond=undefined,$value=undefined){
        if($this->loaded())$this->unload();
        $this->hook('beforeLoadBy',array($field,$cond,$value));
        if(!$this->loaded())$this->controller->tryLoadBy($this,$field,$cond,$value);
        if(!$this->loaded())return $this;
        $this->hook('afterLoad');
        return $this;
    }
    function loadBy($field,$cond=undefined,$value=undefined){
        if($this->loaded())$this->unload();
        $this->hook('beforeLoadBy',array($field,$cond,$value));
        if(!$this->loaded())$this->controller->loadBy($this,$field,$cond,$value);
        if(!$this->loaded())return $this;
        $this->hook('afterLoad');
        return $this;
    }
    /** Unloads then loads current record back. Use this if you have added new fields */
    function reload(){
        return $this->load($this->id);
    }
    // }}}

    // {{{ Ordering and limiting support
    function setLimit($count,$offset=null){
        if($this->controller && $this->controller->hasMethod('setLimit'))
            $this->controller->setLimit($this,$count,$offset);
        return $this;
    }
    function setOrder($field,$desc=null){
        if($this->controller && $this->controller->hasMethod('setOrder'))
            $this->controller->setOrder($this,$field,$desc);
        return $this;
    }
    /**
     * Count records of model
     * 
     * @param string $alias Optional alias of count result
     * 
     * @return integer
     */
    function count($alias = null) {
        if($this->controller && $this->controller->hasMethod('count')) {
            return $this->controller->count($this, $alias);
        } else {
            throw $this->exception('Model do not have controller or count() method not implemented in controller')
                ->addMoreInfo('controller',$this->controller?$this->controller->short_name:'none');
        }
    }
    // }}}

    // {{{ Iterator support 
    function rewind(){
        $this->reset();
        $this->controller->rewind($this);
        if($this->loaded())$this->hook('afterLoad');
    }
    function next(){
        $this->controller->next($this);
        if($this->loaded())$this->hook('afterLoad');
        return $this;
    }
    function current(){
        return $this->get();
    }
    function key(){
        return (string)$this->id;
    }
    function valid(){
        return $this->loaded();
    }

    function getRows($fields=null){
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

    // }}}


    // TODO: worry about cloning!
    function newField($name){
        return $this->addField($name); 
    }
    function hasField($name){
        return $this->hasElement($name);
    }
    function getField($f){
        return $this->getElement($f);
    }

    // Reference traversal for regular models
    public $_references=array();

    /* defines relation between models. You can traverse the reference using ref() */
    function hasOne($model, $our_field = undefined, $field_class = undefined){
        if ($field_class === undefined) {
            $field_class = $this->field_class;
        }

        // if our_field is not specified, let's try to guess it from other model's table
        if($our_field===undefined){
            // determine the actual class of the other model
            if(!is_object($model)){
                $tmp=$this->api->normalizeClassName($model,'Model');
                $tmp=new $tmp; // avoid recursion
            }else $tmp=$model;
            $our_field=($tmp->table).'_id';
        }

        $this->_references[$our_field]=$model;

        if($our_field !== null && $our_field!=='_id' && !$this->hasElement($our_field)){
            $f=$this->add($field_class,$our_field);
            $f->setModel($model);
            return $f;
        }

        return null; // no field added
    }
    /* defines relation for non-sql model. You can traverse the reference using ref() */
    function hasMany($model,$their_field=undefined,$our_field=undefined,$reference=null){
        $class=$this->api->normalizeClassName($model,'Model');
        if(is_null($reference))$reference=$model;
        $this->_references[$reference]=array($class,$their_field,$our_field);
        return null;
    }
    /*
     * How references work:
     *
     * $this->hasMany('Chapter'); // hasMany('Section'), hasOne('Picture');
     * $this->hasOne('Author');   // hasMany('Book'), hasOne('Person','father_id'), hasOne('Person','mother_id')
     *
     * $this->ref('Chapter');
     *   1. Creates Model_Chapter
     *   2. Model_Chapter -> addCondition() // meaning the traversed model must support them!
     *   3. Returns
     *
     * $this->ref('Chapter/Section');
     *   1. $b=Creates Model_Chapter
     *   2. Calls $c=Model_Chapter->_ref('Section'); which only returns model, no binding
     *   3. Decisions:
     *   hasMany
     *      a. $b is loaded(). $c->addCondition();
     *      b. $b and $c are both SQL. $c->join($b);
     *      c. load all id's from $b. $c->addCondition(field,ids);
     *   hasOne()
     *      a. $b is loaded(). $c->load($b[field]);
     *      b. $b and $c are both SQL. $c->join($b);
     *      c. load all [field] values, $c->addCondition('id',ids);
     *
     *  Book/Chapter/Section both SQL:
     *    $book->load(5);
     *    $book->ref('Chapter/Section');  // get all sections
     *      $c=$b->_ref('Chapter/Section')
     *      $s=$c->_ref('Section');
     *      if($s and $c sql){
     *        $s->addCondition('chapter_id',$c->getElement('id'))
     *      }

    /* For a current model, will resolve the reference, initialize the related model and call _refBind. If this
     * is a deep traversing, then it will also specify a field_out to acquire expression, which will be passed
     * into the further model and so on. 
     *
     * if the submodel's ref() will return 
     */
    function ref($ref){
        if(!$ref)return $this;

        list($ref,$rest)=explode('/',$ref,2);

        if(!isset($this->_references[$ref])){
            throw $this->exception('Reference is not defined')
                ->addMoreInfo('model',$this)
                ->addMoreInfo('ref',$ref);
        }

        $class=$this->_references[$ref];
        if(is_array($class)){
            return $this->_ref(
                $rest,
                $class[0],
                $class[1] && $class[1]!=UNDEFINED ? $class[1] : $this->table.'_id',
                $class[2] && $class[2]!=UNDEFINED ? $this[$class[2]] : $this->id 
            );
        } else {
            $id=$this->get($ref);
            $m=$this->_ref(
                $rest,
                $class,
                null,
                $id
            );
            if($id){
                $m->load($id);
            }else{
                $that=$this;
                $m->addHook('afterSave',function($m,$id)use($that,$ref){
                    $that[$ref]=$id;
                    $that->save();
                });
            }
            return $m;
        }
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
    /* Join Binding
     * ============
     *
     * SQL generally treat Joins better, because they can create an execution plan and they don't need to wait for the
     * first subquery to complete before starting working on the next query. 
     *
     * Join binding exists as an extension in SQL_Model::_ref(). It will iterate through array of models and load
     * them into array until it hits non-SQL model (then selects field_out) or reaches the end of chain. In either
     * case it will then back-step to the start of the chain gradually joining each table and skipping tables which
     * have field_in same as field_out.
     *

    /* Subselect Binding
     * =================
     *
     * Binding conditions when traversing. The model must apply field=expression, however this might work differently
     * depending on the type of, the second argument and the refBind implementation. 
     *
     * If the model cannot embed this type of expression into field condition, it must call $expression->get(), fetch
     * all the IDs and then use them instead. This insures intercompatibility between different model implementation.
     *
     * If model is using controller, it will attempt to seek controller's help for applying a condition.
     *
     * If field_out is specified, then the output should be the expression for the next join containing a set of
     * values from the field_out.
     *
     * SQL: select field_out from table where field_in in (expression)
     * Generic: foreach(expression->get() as $item){ $res[]=$m->loadBy($field_in,$item[id_field)->get($field_out) };
     *
     * If field_out is not specified, then the condition must be applied on a current model and the current model
     * must be returned with the condition applied. This model bubbles up and is returned through a top-most 
     * ref / refSQL.
     *
     * Shortcuts
     * ---------
     * if field_in and field_out are the same, simply return expression
     *
     * Book -< Chapter -< Section
     * select * from section where chapter_id in (select id from chapter where book_id=5)
     *
     */
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

    function db(){
        return $this->_table[$this->controller->short_name]['db'];
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
}
