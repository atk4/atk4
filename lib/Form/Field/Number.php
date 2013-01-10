<?php
class Form_Field_Number extends Form_Field_Line {
    function normalize(){
        $v=$this->get();

        // get rid of  TODO

        $this->set($v);
    }
}
