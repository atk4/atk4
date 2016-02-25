<?php
/**
 * Undocumented.
 */
class Form_Field_Money extends Form_Field_Number
{
    public $digits = 2;
    public function setDigits($n)
    {
        $this->digits = $n;

        return $this;
    }
    public function normalize()
    {
        $v = $this->get();
        // remove non-numbers
        $v = preg_replace('/[^-0-9\.]/', '', $v);
        $this->set($v);
    }
    public function getInput($attr = array())
    {
        return parent::getInput(array_merge(array(
                'value' => round($this->value, $this->digits),
            ), $attr));
    }
}
