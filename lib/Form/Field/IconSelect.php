<?php
class Form_Field_IconSelect extends Form_Field_Dropdown {
    function getInput($attr=array()){
        $output='<table class="lister" cellspacing=0 cellpadding=0 width=100%><tbody><tr>'."\n";
        foreach($this->getValueList() as $icon){
            if($icon==$this->value){
                $output.='<td id="'.$this->name.'_'.$icon.'" class="expanded_this" style="cursor: hand" onclick="IconSelect_click(\''.$this->name.'\',\''.$icon.'\')">';
            }else{
                $output.='<td id="'.$this->name.'_'.$icon.'" class="expanded_other" style="cursor: hand" onclick="IconSelect_click(\''.$this->name.'\',\''.$icon.'\')">';
            }
            $output.='<img src="img/mark_'.$icon.'.gif"/></td>'."\n";
        }
        $output.='<td width=100% class="expanded_other">&nbsp;</td></tr><tr><td colspan="'.(count($this->getValueList())+1).'" style="border: 1px solid black; border-top: 0px" id="'.$this->name.'_nfo">Selected: '.$this->value.'</tr></tbody></table>'."\n";
        $output.='<input type=hidden id="'.$this->name.'" name="'.$this->name.'" value="'.$this->value.'">';
        return $output;
    }
    function validate(){
        if(!in_array($this->value,$this->value_list)){
            $this->owner->errors[$this->name]="This is not one of offered values";
        }
        return Form_Field::validate();
    }
}
