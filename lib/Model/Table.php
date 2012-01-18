<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implementation of a Relational SQL-backed Model
 * @link http://agiletoolkit.org/doc/model/table
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
    public $table_alias=null;

    // {{{ Basic Functionality, query initialization and actual field handling
    /** Initialization of ID field, which must always be defined */
    function init(){
        parent::init();

        $this->initQuery();
        if($d=$_GET[$this->name.'_debug']){
            if($d=='query')$this->debug();
        }

        $this->addField($this->id_field)->system(true);
    }
    function exception(){
        return call_user_func_array(array('parent',__FUNCTION__), func_get_args())
            ->addThis($this)
            ->addAction('Debug this Model',array($this->name.'_debug'=>'query'));
    }
    /** Initializes base query for this model */
    function initQuery(){
        $this->dsql=$this->api->db->dsql();
        $table=$this->table?:$this->entity_code;
        if(!$table)throw $this->exception('$table property must be defined');
        $this->dsql->table($table,$this->table_alias);
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
        $select=$this->dsql();

        // add system fields into select
        foreach($this->elements as $el)if($el instanceof Model_Field)
            if($el->system() && !in_array($el->short_name,$fields))
                $fields[]=$el->short_name;

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
        $query=$this->dsql();
        if($this->title_field && $this->hasElement($this->title_field))return $query->field($this->title_field);
        return $query->field($query->expr('concat("Record #",'.$query->bt($this->id_field).')'));
    }
    // }}}

    // {{{ Model_Table supports more than just fields. Expressions, References and Relations can be added
    /** Adds and returns SQL-calculated expression as a read-only field. See Field_Expression class. */
    function addExpression($name,$expression=null){
        $expr=$this
            ->add('Model_Field_Expression',$name)
            ->set($expression);
    }
    /** Constructs JOIN between model tables. Creating and deleting records might affect both tables. See manual. */
	function hasOne($foreign_table, $master_table=null, $join_kind=null, $_foreign_alias=null){
        return $this
            ->add('Model_Field_Join',$name);
    }
    /** Adds a sub-query and manyToOne reference */
    function addReference($name){
        return $this
            ->add('Model_Field_Reference',$name);
    }
    function addTitle($name){
        $this->title=$name;
        return $this->addField($name);
    }
    /** Defines one to many association */
    function hasMany($model,$their_field=null,$our_feld=null){
        $rel=$this
            ->add('Model_Relation_Many');
        // TODO;
        //$this->addMethod('');
    }

    function addCondition($field,$value){
        $this->dsql->where($field,$value);
        return $this;
    }
    function setMasterField($field,$value){
        $this->dsql
            ->where($field,$value)
            ->set($field,$value);
        return $this;
    }


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


    function getRows($fields){
        return $this->selectQuery($fields)->do_getAll();
    }

    function loaded(){
        return(!is_null($this->id));
    }
    function isInstanceLoaded(){ return $this->loaded(); }
    /** Loads record specified by ID. If omitted will load first matching record */
    function load($id=null){
        $load = $this->dsql();
        if(!is_null($id))$load->where($this->id_field,$id);
        $data = $load->limit(1)->do_getAll();
        $this->reset();
        if(!isset($data[0]))throw $this->exception('Record could not be loaded')
            ->addMoreInfo('model',$this)
            ->addMoreInfo('id',$id)
            ;
        $this->data=$data[0];  // avoid using set() for speed and to avoid field checks

        $this->id=$this->data[$this->id_field];

        $this->hook('afterLoad');

        return $this;
    }
    function loadData($id=null){ return $this->load($id); }
    function reset(){
        $this->id=null;
        parent::reset();
    }
    function save(){
        $this->hook('beforeSave');

        // decide, insert or modify
        if($this->loaded()){
            $this->modify();
        }else{
            $this->insert();
        }

        $this->hook('afterSave');
        return $this;
    }
    function insert(){
        $insert = $this->dsql();
        foreach($this->elements as $name=>$f)if($f instanceof Model_Field){
            if(!$f->editable())continue;


            $insert->set($name, $this->get($name));
        }

        $id = $insert->do_insert();
        $this->load($id);
        return $this;
    }
    function modify(){
        $modify = $this->dsql();
        $modify->where($this->id_field, $this->id);

        if(!$this->dirty)return $this;

        foreach($this->dirty as $name=>$junk){
            if($el=$this->hasElement($name))if($el instanceof Model_Field){
                $modify->set($name,$this->get($name));
            }
        }

        $modify->do_update();
        $this->load($this->id);
        return $this;
    }
    function update($data=array()){ // obsolete
        if($data)$this->set($data);
        return $this->save();
    }
    function delete($id){
        if(!$id)throw $this->exception('Specify ID to delete()');

        $this->dsql()->where($this->id_field,$id)->do_delete();
        if($this->id==$id)$this->reset();

        return $this;
    }
}
