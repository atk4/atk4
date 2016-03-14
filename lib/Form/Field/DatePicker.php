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

        return parent::set($this->convertDate($value, null, 'Y-m-d'));
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

    /**
     * Convert date from one format to another.
     *
     * @param string $date Date in string format
     * @param string $from Optional source format, or null to use locale/date from configuration
     * @param string $to Optional target format, or null to use locale/date from configuration
     *
     * @return string|null
     */
    public function convertDate($date, $from = 'd.m.Y', $to = 'Y-m-d')
    {
        if (!$date) {
            return null;
        }
        if ($from === null) {
            $from = $this->app->getConfig('locale/date', 'Y-m-d');
        }
        if ($to === null) {
            $to = $this->app->getConfig('locale/date', 'Y-m-d');
        }
        
        $date = date_create_from_format($from, (string) $date);
        if ($date === false) {
            throw $this->exception('Date format is not correct');
        }

        return date_format($date, $to);
    }
}
