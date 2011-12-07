<?php
class Model_Array extends Model {
    public $arary_data=array();
    public $end_of_array=false;

    function init(){
        parent::init();
        $this->addField('id')->type('int')->system(true);
        $this->addField('name');
    }
    function setSource($data){
        $this->array_data=$data;
        return $this;
    }
    function setAssoc($data){
        $this->array_data=array();
        foreach($data as $id=>$name){
            $this->array_data[]=array('id'=>$id,'name'=>$name);
        }
        return $this;
    }
    function rewind(){
        $this->set(reset($this->array_data));
    }
    function next(){
        $this->set(next($this->array_data));
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
    function getActualFields(){
        return array();
    }
}
