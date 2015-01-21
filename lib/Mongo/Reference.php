<?php
class Mongo_Reference extends Mongo_Field {
    function init(){
        parent::init();


        $this->type('reference_id');

        $this->owner->addField(str_replace('_id','',$this->short_name))
            ->editable(false);

    }
}
