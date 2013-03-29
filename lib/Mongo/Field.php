<?php
class Mongo_Field extends Field {
    function init(){
        parent::init();
        $this->type('reference_id');


    }
}
