<?php
class Model_AgileToolkit_Licenses extends Model {
    function init(){
        parent::init();

        $this->addField('domain');
        $this->addField('type')->enum(array('agpl','closed-source'));
        $this->addField('fingerprint');
    }
}
