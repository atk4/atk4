<?php
/**
 * This class is to improve convenience of using the mongo controller.
 */
class Mongo_Model extends Model
{
    public $id_field = '_id';
    public function init()
    {
        parent::init();

        $this->setSource('Mongo');

        $this->addField('_id')->system(true);
    }
    public function hasOne($model, $our_field = undefined, $field_class = 'Mongo_Reference')
    {
        return parent::hasOne($model, $our_field, $field_class);
    }
}
