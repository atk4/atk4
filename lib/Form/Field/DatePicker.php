<?php
/**
 * Text input with Javascript Date picker
 * It draws date in locale format (taken from $config['locale']['date'] setting) and stores it in
 * MySQL acceptable date format (YYYY-MM-DD).
 */
class Form_Field_DatePicker extends Form_Field_Line
{
    public $options = array();
    public function init()
    {
        parent::init();

        $this->addCalendarIcon();
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
                    'dateFormat' => $this->app->getConfig('locale/date_js', 'dd/mm/yy'),
                    ), $this->options));

        return parent::getInput(array_merge(
            array(
                'value' => $this->value
                    ? (date($this->app->getConfig('locale/date', 'd/m/Y'), strtotime($this->value)))
                    : '',
            ),
            $attr
        ));
    }
    public function set($value)
    {
        // value can be valid date format, as in config['locale']['date']
        if (!$value) {
            return parent::set(null);
        }
        if (is_int($value)) {
            return parent::set(date('Y-m-d', $value));
        }
        @list($d, $m, $y) = explode('/', $value);
        if ($y) {
            $value = implode('/', array($m, $d, $y));
        } elseif ($m) {
            $value = implode('/', array($m, $d));
        }
        $value = date('Y-m-d', strtotime($value));

        return parent::set($value);
    }
    public function get()
    {
        $value = parent::get();
        // date cannot be empty string
        if ($value == '') {
            return;
        }

        return $value;
    }
}
