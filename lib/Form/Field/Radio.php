<?php
/**
 * Undocumented.
 */
class Form_Field_Radio extends Form_Field_ValueList
{
    public function getInput($attr = array())
    {
        $output = '<div id="'.$this->name.'" class="atk-form-options">';
        foreach ($this->getValueList() as $value => $descr) {
            if ($descr instanceof AbstractView) {
                $descr = $descr->getHTML();
            } else {
                $descr = $this->app->encodeHtmlChars($descr);
            }

            $output .=
                '<div>'.$this->getTag('input', array_merge(
                    array(
                        'id' => $this->name.'_'.$value,
                        'name' => $this->name,
                        'data-shortname' => $this->short_name,
                        'type' => 'radio',
                        'value' => $value,
                        'checked' => $value == $this->value,
                    ),
                    $this->attr,
                    $attr
                ))
                ."<label class='atk-padding-xsmall' for='".$this->name.'_'.$value."'>".$descr.'</label></div>';
        }
        $output .= '</div>';

        return $output;
    }
}
