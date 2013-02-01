<?php // vim:ts=4:sw=4:et:fdm=marker
/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Class implementing field of relational database. One object is created
 * for every field in a model. Essentially this object is responsible for
 * storing information about meta-information and assisting model in
 * query creation where particular field is included.
 *
 * @link http://agiletoolkit.org/doc/model
 */

class Field extends AbstractModel
{
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
    public $theModel=null;

    public $relation=null;
    public $actual_field=null;

    /**
     * Implementation of generic setter-getter method which supports "UNDEFINED"
     * constant. This method is used by all other sette-getters
     *
     * @param string $type  Corresponds to the name of property of a field
     * @param mixed  $value New value for a property.
     *
     * @return mixed new or current pperty (if value is undefined)
     */
    function setterGetter($type, $value = UNDEFINED)
    {
        if ($value === UNDEFINED) {
            return $this->$type;
        }
        $this->$type=$value;
        return $this;
    }

    /**
     * Sets the value of the field. Identical to $model[$fieldname]=$value
     *
     * @param mixed $value new value
     *
     * @return Field $this
     */
    function set($value = null)
    {
        $this->owner->set($this->short_name, $value);
        return $this;
    }

    /**
     * Get the value of the field of a loaded model. If model is not loaded
     * will return default value instead
     *
     * @return mixed current value of a field
     */
    function get()
    {
        if ($this->owner->loaded()
            || isset($this->owner->data[$this->short_name])
        ) {
            return $this->owner->get($this->short_name);
        }
        return $this->defaultValue();
    }

    /**
     * If field is accidentally converted to string, provide some
     * descriptive information.
     *
     * @return string descriptive
     */
    function __toString()
    {
        return get_class($this). " ['".$this->short_name."']".' of '. $this->owner;
    }

    /**
     * Logical type of model field. This universal type is recognized by
     * view controllers (such as Controller_MVCForm, Controller_MVCGrid to
     * convert into supported field types.
     *
     * @param string $t new value
     *
     * @return string current value if $t=UNDEFINED
     */
    function type($t = UNDEFINED)
    {
        return $this->setterGetter('type', $t);
    }

    /**
     * Sets field caption which will be used by forms, grids and other view
     * elements as a label. The caption will be localized through api->_
     *
     * @param string $t new value
     *
     * @return string current value if $t=UNDEFINED
     */
    function caption($t = UNDEFINED)
    {
        if ($t===UNDEFINED && !$this->caption) {
            return ucwords(strtr(
                preg_replace('/_id$/', '', $this->short_name),
                '_',
                ' '
            ));
        }
        return $this->setterGetter('caption', $t);
    }

    /**
     * While you may use visible(), editable() to include or exclude fields
     * from appearing in certain scenarios, you can also define a group which
     * you then can display instead of listing all fields manually inside
     * setModel(). Read more about Actual Fields.
     *
     * @param string $t new value
     *
     * @return string current value if $t=UNDEFINED
     */
    function group($t = UNDEFINED)
    {
        return $this->setterGetter('group', $t);
    }

    /**
     * Read only setting will affect the way how field is presented by views.
     * While model field is still writable directly, the Form will not try to
     * change the value of this field
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function readonly($t = UNDEFINED)
    {
        return $this->setterGetter('readonly', $t);
    }

    /**
     * Asterisk will be displayed by the form (if field is include in "actual"
     * fields. This property will not affect the direct use of the field inside
     * model. If you would like that your model complains about empty fields,
     * you should edit beforeSave hook.
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function mandatory($t = UNDEFINED)
    {
        return $this->setterGetter('mandatory', $t);
    }

    /**
     * obsolete
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function required($t = UNDEFINED)
    {
        throw $this->exception('required() is obsolete, use mandatory()');
    }

    /**
     * Set editable to false, if you want to exclude field from forms
     * or other means of editing data. This does not affect the actual model
     * values
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function editable($t = UNDEFINED)
    {
        return $this->setterGetter('editable', $t);
    }

    /**
     * Configures the behavior of Form to disable tag stripping form user input.
     * By default all tags are stripped, setting this property to true will
     * no longer strip tags.
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function allowHTML($t = UNDEFINED)
    {
        return $this->setterGetter('allowHTML', $t);
    }

    /**
     * Setting searchable(true) will instruct Filter and similar views that
     * it should be possible to perform search by this field.
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function searchable($t = UNDEFINED)
    {
        return $this->setterGetter('searchable', $t);
    }

    /**
     * Will instruct Grid and similar views that the sorting controls must be
     * enabled for this field.
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function sortable($t = UNDEFINED)
    {
        return $this->setterGetter('sortable', $t);
    }

    /**
     * Normally views will attempt to pick most suitable way to present field.
     * For example, type='date' will be presented with DatePicker field in form.
     * You might be using add-ons or might have created your own field class.
     * If you would like to use it to present the field, use display(). If you
     * specify string it will be used by all views, otherwise specify it as
     * associtive array: 
     * 
     *     $field->display(array('form'=>'line','grid'=>'button'));
     *
     * @param mixed $t new value
     *
     * @return mixed current value if $t=UNDEFINED
     */
    function display($t = UNDEFINED)
    {
        return $this->setterGetter('display', $t);
    }

    /**
     * In most cases $model['field'] would match "field" inside a database. In
     * some cases, however, you would want to use different database field. This
     * can happen when you join multiple tables and 'field' appears in multiple
     * tables. 
     *
     * You can specify actual field when you declare a field within a model:
     *
     *     $model->addField('modelfield','dbfield');
     *
     * If you are unable to use addField (such as using custom field class),
     * you can use actual() modifier:
     *
     *     $model->add('filestore/File','modelfield')->actual('dbfield');
     *
     * Another potential use is if your database structure does not match
     * model convention:
     *
     *     $model->hasOne('Book')->actual('IDBOOK');
     *
     * @param string $t new value
     *
     * @return string current value if $t=UNDEFINED
     */
    function actual($t = UNDEFINED)
    {
        return $this->setterGetter('actual_field', $t);
    }

    /**
     * Marking field as system will cause it to always be loaded, even if
     * it's not requested through Actual Fields. It will also hide the field
     * making it dissapear from Grids and Forms. A good examples of system
     * fields are "id" or "created_dts".
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function system($t = UNDEFINED)
    {
        if ($t===true) {
            $this->editable(false)->visible(false);
        }
        return $this->setterGetter('system', $t);
    }

    /**
     * Hide field. Not sure!
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function hidden($t = UNDEFINED)
    {
        return $this->setterGetter('hidden', $t);
    }

    /**
     * This will provide a HTML settings on a field for maximum field size.
     * The length of a field will not be enforced by this setting.
     *
     * @param int $t new value
     *
     * @return int current value if $t=UNDEFINED
     */
    function length($t = UNDEFINED)
    {
        return $this->setterGetter('length', $t);
    }

    /**
     * Default Value is used inside forms when you present them without loaded
     * data. This does not change how model works, which will simply avoid
     * including unchanged field into insert/update queries.
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function defaultValue($t = UNDEFINED)
    {
        return $this->setterGetter('defaultValue', $t);
    }

    /**
     * Controls field appearance in Grid or similar views
     *
     * @param boolean $t new value
     *
     * @return boolean current value if $t=UNDEFINED
     */
    function visible($t = UNDEFINED)
    {
        return $this->setterGetter('visible', $t);
    }

    /**
     * Supplies a list data for multi-value fields (selects, radio buttons,
     * checkbox area, autocomplete). You may also use enum(). This setting
     * is typically used with a static falues (Male / Female), if your field
     * values could be described through a different model, use setModel()
     * or better yet - hasOne()
     *
     * @param array $t Array( id => val )
     *
     * @return array current value if $t=UNDEFINED
     */
    function listData($t = UNDEFINED)
    {
        return $this->setterGetter('listData', $t);
    }

    /**
     * What to display when nothing is selected or entered? This will be 
     * displayed on a drop-down when no value is selected: ("Choose ..")
     * if you are using this setting with a text field it will set a
     * placeholder HTML property.
     *
     * @param string $t new value
     *
     * @return string current value if $t=UNDEFINED
     */
    function emptyText($t = UNDEFINED)
    {
        return $this->setterGetter('emptyText', $t);
    }

    /**
     * Will execute setModel() on a field. Some fields will change their
     * behaviour with this. The value is a string (either Model_Book or Book)
     * but you might be able to use object also.
     *
     * I suggest to use $model->hasOne($model) instead of setModel($model)
     *
     * @param string $t new value
     *
     * @return string current value if $t=UNDEFINED
     */
    function setModel($t = UNDEFINED)
    {
        return $this->setterGetter('theModel', $t);
    }

    /**
     * Returns current model. This is different than other setters getters,
     * but it's done to keep consistency with the rest of Agile Toolkit
     *
     * @return string current associated model Class
     */
    function getModel()
    {
        return $this->theModel;
    }

    /**
     * Same as listData()
     *
     * @param array $t Array( id => val )
     *
     * @return array current value if $t=UNDEFINED
     */
    function setValueList($t)
    {
        return $this->listData($t);
    }

    /**
     * Similar to listData() but accepts array of values instead of hash:
     *
     *     listData(array(1=>'Male', 2=>'Female'));
     *     enum(array('male','female'));
     *
     * The value will be stored in database and also displayed to user.
     *
     *
     * @param array $t Array( id => val )
     *
     * @return array current value if $t=UNDEFINED
     */
    function enum($t){ return $this->listData(array_combine($t,$t)); }
        /** Binds the field to a relation (returned by join() function) */
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
    /** Modify update query to set value of this field */
    function updateModifyQuery($modify){
        if($this->relation)$modify=$this->relation->dsql;

        $modify->set($this->actual_field?:$this->short_name,
            $this->getSQL()
        );
        return $this;
    }
    /** Converts true/false into boolean representation according to the "enum" */
    function getBooleanValue($value){
        if($value===null)return null;
        if($this->listData){
            reset($this->listData);
            list($junk,$yes_value)=each($this->listData);
            @list($junk,$no_value)=each($this->listData);
            if($no_value===null)$no_value='';
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
        return $q->bt($this->relation?$this->relation->short_name:$q->main_table).'.'.$q->bt($this->actual_field?:$this->short_name);
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
