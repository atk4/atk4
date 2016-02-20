<?php
/**
 * Undocumented
 */
class Mongo_Field extends Field
{
    public function init()
    {
        parent::init();
        $this->type('reference_id');
    }
}
