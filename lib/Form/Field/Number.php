<?php
/**
 * Undocumented.
 */
class Form_Field_Number extends Form_Field_Line
{
    public function setForm($form)
    {
        parent::setForm($form);
        $this->validate('number');
    }
}
