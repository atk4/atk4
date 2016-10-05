<?php

/**
 * Implements a drop-down date without all the
 * messiness, assuming that the native values
 * always use DateTime class.
 */
class Form_Field_ADDatePicker extends Form_Field_Line
{
    public $options = array();

    public $format = 'd/m/Y';
    public $format_js = 'dd/mm/yy';

    public function init()
    {
        parent::init();

        $this->addCalendarIcon();

        $this->getFormatFromConfig();

    }
    function getFormatFromConfig()
    {
        $this->format = $this->app->getConfig('locale/date', $this->format);
        $this->format_js = $this->app->getConfig('locale/date_js', $this->format_js);
    }

    public function addCalendarIcon()
    {
        $this->addButton('', array('options' => array('text' => false)))
            ->setHtml('')
            ->setIcon('calendar')
            ->js('click', $this->js()->datepicker('show'));
        $this->js('focus', $this->js()->datepicker('show'));
    }
    public function getInput($attr = array())
    {
        // $this->value contains date in MySQL format
        // we need it in locale format

        $this->js(true)->datepicker(array_merge(array(
                    'duration' => 0,
                    'showOn' => 'none',
                    'changeMonth' => true,
                    'changeYear' => true,
                    'dateFormat' => $this->format_js
                    ), $this->options));

        return parent::getInput(array_merge(
            array(
                'value' => $this->value
                    ? $this->value->format($this->format)
                    : '',
            ),
            $attr
        ));
    }

    public function loadPOST()
    {
        if (isset($_POST[$this->name])) {
            $f = \DateTime::createFromFormat($this->format, $_POST[$this->name]);
            $this->set($f ?: "");
        }
    }
}
