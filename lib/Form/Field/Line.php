<?php
/**
 * Undocumented.
 */
class Form_Field_Line extends Form_Field
{
    public function getInput($attr = array())
    {
        return parent::getInput(array_merge(array('type' => 'text'), $attr));
    }
}
