<?php
class Model_Table extends Model implements Iterator {
    public $dsql;   // Master SQL 

    public $table_alias=null;

    function init(){
        parent::init();

        $this->initQuery();
        if($d=$_GET[$this->name.'_debug']){
            if($d=='query')$this->debug();
        }

        $this->addField('id')->system(true);
    }
    function exception(){
        return call_user_func_array(array('parent',__FUNCTION__), func_get_args())
            ->addAction('Debug this Model',array($this->name.'_debug'=>'query'));
    }
    function initQuery(){
        $this->dsql=$this->api->db->dsql();
        $this->dsql->table($this->entity_code,$this->table_alias);
        $this->dsql->paramBase(is_null($this->table_alias)?$this->entity_code:$this->table_alias);
    }
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
        foreach($this->elements as $el)if($el instanceof Model_Field){
            if($el->system())continue;
            if($el->hidden())continue;
            $fields[]=$el->short_name;
        }
        return $fields;
    }

    function dsql(){
        return clone $this->dsql;
    }
    function selectQuery($fields){
        $select=$this->dsql();

        // add system fields into select
        foreach($this->elements as $el)if($el instanceof Model_Field)
            if($el->system() && !in_array($el->short_name,$fields))
                $fields[]=$el->short_name;

        // add fields
        foreach($fields as $field){
            $field=$this->hasElement($field);
            if(!$field)continue;

            $field->updateSelectQuery($select);
        }
        return $select;
    }
    public $title=null;
    function getTitleField(){
        if($this->title)return;
        if($this->hasElement('name'))return 'name';
        return 'id';
    }
    function titleQuery(){
        $title=$this->getTitleField();
        $select=$this->dsql();
        if($title==$this->id_field){
            $select->field($select->expr('concat("Record #",'.$this->bt($this->id_field).')'));
        }else{
            $this->getElement($title)->updateSelectQuery($select);
        }
        return $select;
    }

    function addExpression($name){
        return $this
            ->add('Model_Field_Expression',$name);
    }
    function addReference($name){
        return $this
            ->add('Model_Field_Reference',$name);
    }
    function addTitle($name){
        $this->title=$name;
        return $this->addField($name);
    }

    function addCondition($field,$value){
        $this->dsql->where($field,$value);
        return $this;
    }


    // {{{ Iterator support 
    function rewind(){
        $this->dsql->rewind();
        return $this->next();
    }
    function next(){
        $this->data=$this->dsql->next();
        $this->id=@$this->data[$this->id_field];
        return $this;
        //return $this->data = $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    function current(){
        return $this;
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


    public $id=null;
    public $id_field='id';

    function loaded(){
        return(!is_null($this->id));
    }
    function isInstanceLoaded(){ return $this->loaded(); }
    /** Loads record specified by ID. If omitted will load first matching record */
    function load($id=null){
        $load = $this->dsql();
        if(!is_null($id))$load->where($this->id_field,$id);
        $data = $load -> limit(1)->do_getAll();
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
