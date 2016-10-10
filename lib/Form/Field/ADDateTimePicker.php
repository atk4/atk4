<?php

/**
 * Implements a drop-down date without all the
 * messiness, assuming that the native values
 * always use DateTime class.
 */
class Form_Field_ADDateTimePicker extends Form_Field_ADDatePicker
{
    public $format = 'd/m/Y H:i:s';

    function init() {
        parent::init();
        $this->js(true)->univ()->dateTimePickerFix($this->name.'_t');
    }

    function getInput($attr = []) {
        if ($this->value && !$this->value instanceof \DateTime) {
            throw new \atk4\core\Exception([
                "Value is not a DateTime. Use compatible format.",
                'field'=>$this->short_name
            ]);
        }
        if ($this->value) {
            $value = $this->value->format($this->format);
        } else {
            $value = '';
        }

        return parent::getInput($attr).$this->getTag('input',
            ['id'=>$this->name.'_t', 'value'=>$value, 'type'=>'hidden']
        );
    }
}
