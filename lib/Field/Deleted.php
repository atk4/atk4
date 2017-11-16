<?php
/**
 * Implementation of soft delete
 */
class Field_Deleted extends Field
{
    public function init()
    {
        parent::init();
        $this->defaultValue(false);
        $this->type('boolean');
        $this->owner->addCondition($this->short_name, false);
    }
}
