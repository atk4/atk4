<?php
class Form_Field_Readonly extends Form_Field
{
    function init()
    {
        parent::init();
        $this->setNoSave();
    }

    function getInput($attr=array())
    {
        // get value
        if (isset($this->value_list[$this->value])) {
            $s = $this->value_list[$this->value];
        } else {
            $s = $this->value;
        }
        // create output
        $output = $this->getTag('div', array_merge(
                array(
                    'class' => 'atk-form-field-readonly',
                    'name' => $this->name,
                    'data-shortname' => $this->short_name,
                    'id' => $this->name,
                ),
                $attr,
                $this->attr
            ));
        $output .= (strlen($s)>0 ? nl2br($s) : '&nbsp;');
        $output .= $this->getTag('/div');
        return $output;
    }
    function loadPOST()
    {
        // do nothing because this is readonly field
    }
    function setValueList($list)
    {
        $this->value_list = $list;
        return $this;
    }
}
