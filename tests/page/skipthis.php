<?php
class page_skipthis extends Page_Tester {
    public $proper_responses=array(
        "Test_one"=>'one',
        "Test_two"=>'two',
        "Test_three"=>'three'
    );

    function prepare(){
    }
    function test_one(){
        return 'one';
    }
    function test_two(){
        $this->skipTests('Testing tester for successful test skipping');
        return 'two';
    }
    function test_three(){
        return 'three';
    }
}
