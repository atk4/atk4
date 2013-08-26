<?php
class page_mongo1 extends Page_Tester {
    public $proper_responses=array(
        "Test_load"=>'John',
        "Test_loadby"=>'John',
        "Test_loadsave"=>'1',
        "Test_notfound"=>'false'
    );

    function prepare(){
        try{ 
            return array($this->add('Model_Person'));
        }catch(MongoConnectionException $e){
            $this->skipTests('Mongo failed to connect');
        }
    }
    function test_load($m){
        $m['name']='John';
        $id = $m->save()->id;


        $m->load($id);
        $m->load($id);
        return $m['name'];
    }

    function test_loadby($m){
        $m['name']='Peter';
        $m->save();

        $m->loadBy('name','Peter');
        $m->loadBy('name','John');

        return $m['name'];
    }

    function test_loadsave($m){
        $m['name']='Kate';
        $m['sex']='F';
        $id = $m->save()->id;

        $m->load($id);
        $m['name']='Katja';
        $m->save();

        $m->unload();
        return $m->loadBy('name','Katja')->loaded();
    }

    function test_notfound($m){
        $id = $m->loadBy('name','Peter')->id;

        $m->addCondition('name','John');

        return json_encode($m->tryLoad($id)->loaded());
    }
}


class Model_Person extends Mongo_Model {
    public $table='person';
    function init(){
        parent::init();

        $this->addField('name');
        $this->addField('sex')->enum(array('M','F'))->defaultValue('M');
    }
}
