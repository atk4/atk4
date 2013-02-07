<?php
class Form_Field_Time extends Form_Field {
    function getInput($attr=array()){
        return parent::getInput(array_merge(array(
                'type'=>'text',
                'value'=>date($this->api->getConfig('locale/time','H:i:s'),
                    strtotime($this->value))
            ),$attr));
    }
}
