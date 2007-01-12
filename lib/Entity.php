<?php
class Entity {
    private $fields=array();
    private $limit=array();

    function addField($type,$name,$descr=null){
        $this->fields[$name]=array(
                'type'=>$type,
                'descr'=>$descr
                );
        return $this;
    }
    function noDb(){
        // Make last field not in database
    }
    function useWith($obj,$limit=array()){
        $this->limit=$limit;
        if($obj instanceof Grid)return $this->initializeGrid($obj);
        if($obj instanceof Form)return $this->initializeForm($obj);
        throw new BaseException("Entity can't initialize object of class ".get_class($obj));
    }
    function initializeForm($form){
        throw new BaseException("Entity use with forms is not yet implemented");
    }
    function initializeGrid($grid){
        foreach($this->fields as $name=>$field){
            if(
                    !in_array($name,$this->limit) &&
                    !in_array('*',$this->limit))continue;
            if(
                    in_array('-'.$name,$this->limit))continue;

            switch($field['type']){
                default:
                    $grid->addColumn('text',$name,$field['descr']);
                    $grid->dq->field($name);
            }
        }
        $grid->dq->table($this->table);
    }
    function setTable($table){
        $this->table=$table;
        return $this;
    }
    function setID($id){
        $this->id=$id;
        return $this;
    }
    function showIf($junk){
        return $this;
    }
}
