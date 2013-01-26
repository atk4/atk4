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
class Form_Field_CheckboxList extends Form_Field_ValueList
{
    /**
     * This field will create 2 column of checkbox+labels from defived value
     * list. You may check as many as you like and when you save their ID
     * values will be stored coma-separated in varchar type field.
     *
     * $f->addField('CheckboxList','producers','Producers')->setValueList(array(
     * 1=>'Mr John',
     * 2=>'Piter ',
     * 3=>'Michail Gershwin',
     * 4=>'Bread and butter',
     * 5=>'Alex',
     * 6=>'Benjamin',
     * 7=>'Rhino',
     * ));
     */
    var $columns=2;
    
    function getInput($attr=array())
    {
        $output='<table class="atk-checkboxlist" border="0" id="'.$this->name.'"><tbody>';
        $current_values=explode(',',$this->value);
        $column=0;
        $i=0;
        foreach($this->getValueList() as $value=>$descr){
            if($column==$this->columns) $column=0;
            if($column==0) $output.='<tr>';
            $output .=
                '<td align="left">' .
                $this->getTag('input',array_merge(array(
                        'id'=>$this->name.'_'.$value,
                        'name'=>$this->name.'['.$i.']',
                        'data-shortname'=>$this->short_name,
                        'type'=>'checkbox',
                        'value'=>$value,
                        'checked'=>in_array($value,$current_values)
                    ),$this->attr,$attr)) .
                '<label for="'.$this->name.'_'.$value.'">'.htmlspecialchars($descr).'</label>' .
                '</td>';
            $i++;
            $column++;
            if($column==$this->columns) $output.='</tr>';
        }
        // fill empty cells in last row if needed
        if($column>0 && $column<$this->columns) {
            for($i=$column;$i<$this->columns;$i++)
                $output.='<td></td>';
            $output.='</tr>';
        }
        $output.='</tbody></table>';
        
        return $output;
    }
}
