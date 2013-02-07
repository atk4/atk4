<?php
class Form_Field_Password extends Form_Field {
    function normalize(){
        // user may have entered spaces accidentally in the password field.
        // Clean them up.
        $this->set(trim($this->get()));
        parent::normalize();
    }
    function getInput($attr=array()){
        return parent::getInput(array_merge(
                    array(
                        'type'=>'password',
                         ),$attr
                    ));
    }
}
