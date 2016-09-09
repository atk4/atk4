<?php
/**
 * Undocumented.
 */
class Form_Field_Number extends Form_Field_Line
{
    public function performValidation()
    {
        parent::performValidation();

        $v = $this->get();
        if ($v !== null && $v !== '') {
            $this->validate('number');
        }
    }
}
