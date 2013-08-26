<?php
class page_mongo2 extends page_mongo1 {
    function init(){

        try{ 
        $this->mom=$this->add('Model_Mother')
            ->set('name','Mother of all')
            ->save();
        }catch(MongoConnectionException $e){
            // divert until later!
        }
        parent::init();
    }
    function prepare(){
        if(!@$this->mom){
            $this->skipTests('Probably another Mongo connection failure');
        }
        return array($this->mom->ref('Children'));
    }

    /*
    function test_final(){
        $x=array();
        foreach ($this->mom->ref('Children') as $row){
            $x[]=$row['name'];
        }
        return join(' ',$x);
    }
     */
}

class Model_Mother extends Model_Person {
    function init(){
        parent::init();

        $this->addCondition('sex','F');
        $this->hasMany('Child',null,null,'Children');
    }
}

class Model_Child extends Model_Person {
    function init(){
        parent::init();
        $this->hasOne('Mother');
        $this->hasMany('Books');
    }
}
