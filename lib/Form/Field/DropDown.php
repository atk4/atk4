<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Form_Field_DropDown extends Form_Field_ValueList {

    public $default_selected_key = '';

    /**
     * Sets default selected key from exisiting list of key => value pair
     *
     * @param string/integer $key {ass UNDEFINED to use default value empty_text/value
     *
     * @return $this
     */
    function setDefaultSelectedKey($key = UNDEFINED)
    {
        if ($this->value_list && isset($this->value_list[$key])) {
            $this->default_selected_key = $key;
        }
        return $this;
    }

    function getInput($attr=array()){
        $multi = isset($this->attr['multiple']);
        $output=$this->getTag('select',array_merge(array(
                        'name'=>$this->name . ($multi?'[]':''),
                        'data-shortname'=>$this->short_name,
                        'id'=>$this->name,
                        ),
                    $attr,
                    $this->attr)
                );

        foreach($this->getValueList() as $value=>$descr){
            // Check if a separator is not needed identified with _separator<
            $output.=
                $this->getOption($value)
                .htmlspecialchars($descr)
                .$this->getTag('/option');
        }
        $output.=$this->getTag('/select');
        return $output;
    }
    function getOption($value){
        $selected = $value == $this->value;
        if ($this->default_selected_key && $value == $this->default_selected_key) {
            $selected = $this->default_selected_key;
        }
        return $this->getTag('option',array(
                    'value'=>$value,
                    'selected'=>$selected
                    ));
    }
}
