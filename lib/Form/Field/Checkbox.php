<?php
/**
 * Undocumented.
 */
class Form_Field_Checkbox extends Form_Field
{
    public $true_value = true;
    public $false_value = false;
    public function init()
    {
        parent::init();
        $this->default_value = '';
    }
    public function setValues($true, $false)
    {
        $this->true_value = $true;
        $this->false_value = $false;

        return $this;
    }
    public function setValueList($list)
    {

        // Model must convert it properly
        //return $this;

        /* otherwise type("boolean")->enum(array("Y","N")) won't work */
        if (count($list) != 2) {
            throw $this->exception('Invalid value list for Checkbox');
        }
        $this->setValues(array_shift($list), array_shift($list));

        return $this;
    }
    public function getInput($attr = array())
    {
        $this->template->trySet('field_caption', '');
        $this->template->tryDel('label_container');

        $label = '&nbsp;<label for="'.$this->name.'">'.$this->caption.'</label>';

        return parent::getInput(array_merge(
            array(
                'type' => 'checkbox',
                'value' => '1',
                'checked' => (boolean) ($this->true_value == $this->value),
                 ),
            $attr
        )).$label;
    }
    public function loadPOST()
    {
        if (isset($_POST[$this->name])) {
            $this->set($this->true_value);
        } else {
            $this->set($this->false_value);
        }
    }
}
