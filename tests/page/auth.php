<?php


class page_auth extends Page_Tester {
    function test_auth(){
        $a=$this->add('Auth');

        $a->allow('john','smith');
        return $a->verifyCredentials('john','smith');
    }
    function test_auth2(){
        $a=$this->add('Auth');

        $a->allow('john','smith');
        return $a->verifyCredentials('john','doe');
    }
    function test_auth3(){
        $a=$this->add('Auth');

      //  $a->setModel('Auth_User');
       // $a->verifyCredentials('john','doe');
    }
}

