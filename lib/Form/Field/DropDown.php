<?php
/**
 * Undocumented.
 */
class Form_Field_DropDown extends Form_Field_ValueList
{
    public $select_menu_options = array();

    public function getInput($attr = array())
    {
        $this->select_menu_options['change'] = $this->js()->trigger('change')->_enclose();
        $this->js(true)->selectmenu($this->select_menu_options);
        $multi = isset($this->attr['multiple']);
        $output = $this->getTag('select', array_merge(
            array(
                'name' => $this->name.($multi ? '[]' : ''),
                'data-shortname' => $this->short_name,
                'id' => $this->name,
            ),
            $attr,
            $this->attr
        ));

        foreach ($this->getValueList() as $value => $descr) {
            // Check if a separator is not needed identified with _separator<
            $output .=
                $this->getOption($value)
                .$this->app->encodeHtmlChars($descr)
                .$this->getTag('/option');
        }
        $output .= $this->getTag('/select');

        return $output;
    }
    public function getOption($value)
    {
        $selected = false;
        if ($this->value === null || $this->value === '') {
            $selected = $value === '';
        } else {
            $selected = $value == $this->value;
        }

        return $this->getTag('option', array(
                    'value' => $value,
                    'selected' => $selected,
        ));
    }
}
