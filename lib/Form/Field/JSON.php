<?php
/**
 * Undocumented.
 */
class Form_Field_JSON extends Form_Field_Line
{
    public function normalize()
    {
        $this->set(json_decode($this->get(), true));
    }

    public function getInput($attr = array())
    {
        return parent::getInput(array_merge(array(
                'value' => json_encode($this->value),
            ), $attr));
    }
}
