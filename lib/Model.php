<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implementation of a Generic Model. 
 * @link http://agiletoolkit.org/doc/model
 *
 * Model has fields which you add with addField() and access through get() and set()
 * You can also load and save model through different storage controllers.
 *
 * This model is designed to work with linear, non-SQL resources, if you are looking
 * to have support for joins, ordering, advanced SQL syntax, look into Model_Table
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
 * $pc=$this->add('Model_PageCache')->setSource('Memcached');
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
class Model extends AbstractModel implements ArrayAccess,Iterator {

    public $default_exception='Exception';

    /** The class prefix used by addField */
    public $field_class='Field';

    /** If true, model will now allow to set values for non-existant fields */
    public $strict_fields=false;

    /** Contains information about table/file/bucket/array used by Controller to determine source */
    public $table;

    /** Contains identifier of currently loaded record or null. Use load() and reset() */
    public $id=null;     // currently loaded record

    // Curretly loaded record
    public $data=array();
    public $dirty=array();

    public $actual_fields=false;// Array of fields which will be used in further select operations. If not defined, all fields will be used.


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

        if($value!==undefined && (
            is_null($value)!=is_null($this->data[$name]) || 
            is_object($value) ||
            is_object($this->data[$name]) || 
            (string)$value!=(string)$this->data[$name]
        )){
            $this->data[$name]=$value;
            $this->setDirty($name);
        }
        return $this;
    }
    /** Return value of the field. If unspecified will return array of all fields.  */
    function get($name=null){
        if($name===null)return $this->data;
        if($this->strict_fields && !$this->hasElement($name))
            throw $this->exception('No such field','Logic')->addMoreInfo('field',$name);
        if(!isset($this->data[$name]) && !$this->hasElement($name))
            throw $this->exception('Model field was not loaded')
            ->addMoreInfo('id',$this->id)
            ->addMoreinfo('field',$name);
        if(@!array_key_exists($name,$this->data)){
            return $this->getElement($name)->defaultValue();
        }
        return $this->data[$name];
    }
    /**
     * Returs list of fields which belong to specific group. You can add fields into groups when you
     * define them and it can be used by the front-end to determine which fields needs to be displayed.
     * 
     * If no group is specified, then all non-system fields are displayed for backwards compatibility.
     */
    function getActualFields($group=undefined){
        if($group===undefined && $this->actual_fields)return $this->actual_fields;
        $fields=array();
        foreach($this->elements as $el)if($el instanceof Field){
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
    /** Default set of fields which will be included into further queries */
    function setActualFields(array $fields){
        $this->actual_fields=$fields;
        return $this;
    }
    /** When fields are changed, they are marked dirty. Only dirty fields are saved when save() is called */
    function setDirty($name){
        $this->dirty[$name]=true;
    }
    /** Returns if the records has been loaded successfully */
    function loaded(){
        return !is_null($this->id);
    }
    /** Forget loaded data */
    function unload(){
        $this->hook('beforeUnload');
        $this->data=$this->dirty=array();
        $this->id=null;
        $this->hook('afterUnload');
        return $this;
    }
    function reset(){
        return $this->unload();
    }
    // }}}

    /// {{{ Operation with external Data Controllers
    /** Associates appropriate controller and loads data such as 'Array' for Controller_Data_Array class */
    function setSource($controller, $table=null, $id=null){
        if(is_string($controller))$controller='Data_'.$controller;
        $this->controller=$this->setController($controller);

        $this->controller->setSource($this,$table);

        if($id)$this->load($id);
        return $this;
    }
    /** Attempt to load record with specified ID. If this fails, no error is produced */
    function load($id=null){
        $this->hook('beforeLoad',$id);
        $res=$this->controller->load($this,$id);
        $this->hook('afterLoad');
        return $res;
    }
    /** Saves record with current controller. If no argument is specified, uses $this->id. Specifying "false" will create 
     * record with new ID. Returns ID of saved record */
    function save($id=null){
        $this->hook('beforeSave',$id);
        $res=$this->controller->save($this,$id);
        $this->hook('afterSave');
        return $res;
    }
    /** Deletes record associated with specified $id. If not specified, currently loaded record is deleted (and unloaded) */
    function delete($id=null){
        $this->hook('beforeDelete',$id);
        $res=$this->controller->delete($this,$id?:$this->id);
        $this->hook('afterDelete');
        return $res;
    }
    /// }}}

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

    // {{{ Iterator support 
    function rewind(){
        $this->reset();
        $this->controller->rewind($this);
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
        return $this->id;
    }
    function valid(){
        return $this->loaded();
    }
    // }}}


    // TODO: worry about cloning!
    function newField($name){
        return $this->addField($name); 
    }
    function hasField($name){
        return $this->hasElement($name);
    }
    function getEntityCode(){
        return $this->table?:$this->entity_code;
    }
    function getField($f){
        return $this->getElement($f);
    }
}
