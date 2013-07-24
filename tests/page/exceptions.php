<?php
class page_exceptions extends Page_Tester {
    public $proper_responses=array(
            "Test_exception1"=>'Proper Exception',
            "Test_exception2"=>'Exception_ForUser',
            "Test_exception3"=>'Exception_Logic_test',
            "Test_moreinfo1"=>'',
            "Test_htmloutput"=>'Exception_ForUser: Unable to access file (filename=testfile.txt)'
        );
    function prepare(){
        return array();
    }

    function test_exception1(){
        try{
            throw $this->exception('Proper Exception');
        }catch(Exception $e){
            return $e->getMessage();
        }
    }
    function test_exception2(){
        $c=$this->add('Controller');
        $c->default_exception='Exception_ForUser';

        try{
            throw $c->exception('Proper Exception');
        }catch(Exception $e){
            return get_class($e);
        }
    }
    function test_exception3(){
        $c=$this->add('Controller');
        $c->default_exception='Exception_Logic';

        try{
            throw $c->exception('Proper Exception','_test');
        }catch(Exception $e){
            return get_class($e);
        }
    }
    function test_moreinfo1(){
        try{
            throw $this->exception('Proper Exception','Exception_ForUser')
                ->addMoreInfo('foo','bar');
        }catch(Exception $e){
           // return $e->getHTML();
        }
    }
    function test_htmloutput(){
        try {
            throw $this->exception('Unable to access file',null,'13')
                ->addMoreInfo('filename','testfile.txt');
        } catch (Exception $e){
            $result=$e->getText();
            $result=preg_replace('/ in .*$/','',$result);
            return $result;
        }
    }
}
class Exception_Logic_test extends Exception_Logic {}
