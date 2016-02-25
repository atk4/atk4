<?php
/**
 * Undocumented.
 */
class Field_Callback extends Field_Calculated
{
    public function getValue($model, $data)
    {
        return call_user_func_array($this->expression, array($model, $data));
    }
}
