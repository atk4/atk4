<?php


class Model_Auth_User extends Model {
    function init() {
        parent::init();

        $this->addField('myusername');
        $this->addfield('mypassword');


        $this->setSource('Array',array(
            array( 'myusername'=>'jonh', 'mypassword'=>'smith'),
            array( 'myusername'=>'peter', 'mypassword'=>'$2y$10$NquZ/mcgKawWcTpq1i9Oz.aC.n5eD1gC3cEsbCOc3XtSFlP6oNAKG'), // passsword_hash(smith)
        ));
    }
}

class page_auth extends Page_Tester {
    public $myapp;
    function init() {
        $this->myapp=$this->add('ApiCLI');
        return parent::init();
    }
    function test_auth(){
        $a=$this->myapp->add('Auth');

        $a->allow('john','smith');
        return $a->verifyCredentials('john','smith');
    }
    function test_auth2(){
        $a=$this->myapp->add('Auth');

        $a->allow('john','smith');
        return $a->verifyCredentials('john','doe');
    }
    function test_auth3(){
        $a=$this->myapp->add('Auth');

        $a->setModel('Auth_User','myusername','mypassword');
        return(boolean)$a->verifyCredentials('john','doe');
    }
    function test_auth4(){
        $a=$this->myapp->add('Auth');

        $a->setModel('Auth_User','myusername','mypassword');
        return (boolean)$a->verifyCredentials('john','smith');
    }
    function test_auth5(){
        $a=$this->myapp->add('Auth');

        $a->setModel('Auth_User','myusername','mypassword');
        return $a->verifyCredentials('peter','smith');
    }
    function test_auth6(){
        $a=$this->myapp->add('Auth');

        $a->setModel('Auth_User','myusername','mypassword');
        return $a->verifyCredentials('nouser','smith');
    }
}

