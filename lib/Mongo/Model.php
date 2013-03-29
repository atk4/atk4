<?php
/**
 * This class is to improve convenience of using the mongo controller
 */
class Mongo_Model extends Model {
    public $id_field='_id';
    function init(){


        parent::init();

        $this->setSource('Mongo',$this->table);

        $this->addField('id')->system(true)->visible(true);
    }
}
