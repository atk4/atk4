<?php
/**
 * Note: This class extends Form_Field_ValueList not Form_Field, because it
 *       have to nicely replace Form_Field_ValueList based fields too.
 */
class Form_Field_Readonly extends Form_Field_ValueList
{
    function init()
    {
        parent::init();
        $this->disable();
    }

    function getInput($attr = array())
    {
        $v = $this->value;
        
        // this works nicely for Form_Field_ValueList based fields like DropDown
        if ($v || is_scalar($v)) {
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
        $output .= (strlen($v)>0 ? nl2br($v) : '&nbsp;');
        $output .= $this->getTag('/div');
        return $output;
    }
    function loadPOST()
    {
        // do nothing because this is readonly field
    }
    function validate()
    {
        // always valid because this is readonly field
        return true;
    }
}
