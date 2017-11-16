<?php
/**
 * Note: This class extends Form_Field_ValueList not Form_Field, because it
 *       have to nicely replace Form_Field_ValueList based fields too.
 */
class Form_Field_Readonly extends Form_Field_ValueList
{
    public function init()
    {
        parent::init();
        $this->disable();
    }

    public function getInput($attr = array())
    {
        $v = $this->value;

        // this works nicely for Form_Field_ValueList based fields like DropDown
        if (is_scalar($v)) {
            // get value from model if form field is based on model
            if ($this->model) {
                // ignore errors, because this is just a readonly field after all :)
                $this->model->tryLoad($v);
                if ($this->model->loaded()) {
                    $v = $this->model->get($this->model->title_field);
                }
            } elseif (isset($this->value_list[$v])) {
                // get value from value list
                $v = $this->value_list[$v];
            }
        }

        // create output
        $output = $this->getTag('div', array_merge(
            array(
                'id' => $this->name,
                'name' => $this->name,
                'data-shortname' => $this->short_name,
                'class' => 'atk-form-field-readonly',
            ),
            $attr,
            $this->attr
        ));

        // fix for DateTime values for Agile Data
        if ($v instanceof DateTime) {
            $v = date($this->app->getConfig('locale/date', 'd/m/Y'), $v->format('U'));
        }

        $output .= (strlen($v) > 0 ? nl2br($v) : '&nbsp;');
        $output .= $this->getTag('/div');

        return $output;
    }
    public function loadPOST()
    {
        // do nothing because this is readonly field
    }
    public function validate()
    {
        // always valid because this is readonly field
        return true;
    }
    public function validateValidItem()
    {
        // always valid because this is readonly field
        return true;
    }
}
