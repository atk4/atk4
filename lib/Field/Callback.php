<?php
/**
 * Undocumented.
 */
class Field_Callback extends Field
{
    public $callback = null;
    public $initialized = false;
    
    public function init()
    {
        parent::init();
        $this->editable(false);
    }
    public function set($callback)
    {
        $this->callback = $callback;

        return $this;
    }
    public function updateSelectQuery($select)
    {
        $this->initialized = true;
        $this->owner->addHook('afterLoad', $this);
    }
    public function afterLoad($m)
    {
        $result = call_user_func($this->callback, $this->owner, $this);
        $this->owner->set($this->short_name, $result);

        return $this;
    }
    public function updateInsertQuery($insert)
    {
        return $this;
    }
    public function updateModifyQuery($insert)
    {
        return $this;
    }
}
