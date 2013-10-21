<?php
/**
 * This class is to improve convenience of using the mongo controller
 */
class Mongo_Model extends Model {
    public $id_field='_id';

    /** 
     * In most Mongo databases the _id field will store values of type MongoID.
     * If you are not following this pattern then set this property to "false"
     */
    public $id_is_object=true;
    function init(){


        parent::init();

        $this->setSource('Mongo');

        $this->addField('_id')->system(true);
    }
    function hasOne($model,$our_field=undefined,$field_class='Mongo_Reference'){
        return parent::hasOne($model,$our_field,$field_class);
    }
}
