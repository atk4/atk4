<?php
/**
 * Undocumented.
 */
class Form_Field_Slider extends Form_Field_Number
{
    public $min = 0;
    public $max = 10;
    public $step = 1;
    public $left = 'Min';
    public $right = 'Max';

    public function setStep($n)
    {
        $this->step = $n;

        return $this;
    }
    public function setLabels($left, $right)
    {
        $this->left = $left;
        $this->right = $right;

        return $this;
    }
    public function getInput()
    {
        $s = $this->name.'_slider';
        $this->js(true)->_selector('#'.$s)->slider(array(
                    'min' => $this->min,
                    'max' => $this->max,
                    'step' => $this->step,
                    'value' => $this->js()->val(),
                    'change' => $this->js()->_enclose()->val(
                        $this->js()->_selector('#'.$s)->slider('value')
                    )->change(),
                ));
        $this->setAttr('style', 'display: none');

        return '<div class="atk-cells"><div class="atk-cell atk-align-left">'.
            $this->left.'</div>'.
            '<div class="atk-cell atk-align-right">'.$this->right.'</div>'.
            '</div>'.
            ''.parent::getInput().'<div id="'.$s.'"></div>'.
            '';
    }
}
