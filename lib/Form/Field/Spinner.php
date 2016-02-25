<?php
/**
 * Undocumented.
 */
class Form_Field_Spinner extends Form_Field_Number
{
    public $min = 0;
    public $max = 10;
    public $step = 1;

    public function setStep($n)
    {
        $this->step = $n;

        return $this;
    }
    public function getInput()
    {
        $s = $this->name.'_spinner';
        $this->js(true)->_selector('#'.$s)->spinner(array(
                    'min' => $this->min,
                    'max' => $this->max,
                    'step' => $this->step,
                    'value' => $this->js()->val(),
                    'change' => $this->js()->_enclose()->val(
                        $this->js()->_selector('#'.$s)->spinner('value')
                    )->change(),
                ));
        $this->setAttr('style', 'display: none');

        return parent::getInput().'<div id="'.$s.'"></div>';
    }
}
