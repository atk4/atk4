<?php
class Form_Field_Checkbox extends Form_Field {
    public $true_value=1;
    public $false_value=0;
    function init(){
        parent::init();
        $this->default_value='';
    }
    function getInput($attr=array()){
        $this->template->trySet('field_caption','');
        $this->template->tryDel('label_container');
        if(strpos('<',$this->caption)!==false){
            // HTML in label
            $label=$this->caption;
        }else{
            $label='<label for="'.$this->name.'">'.$this->caption.'</label>';
        }
        return parent::getInput(array_merge(
                    array(
                        'type'=>'checkbox',
                        'value'=>$this->true_value,
                        'checked'=>(boolean)($this->true_value==$this->value)
                         ),$attr
                    )).$label;
    }
    function loadPOST(){
        if(isset($_POST[$this->name])){
            $this->set($this->true_value);
        }else{
            $this->set($this->false_value);
        }
    }
}
