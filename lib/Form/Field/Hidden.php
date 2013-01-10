<?php
class Form_Field_Hidden extends Form_Field {
    function getInput($attr=array()){
        return parent::getInput(array_merge(
                    array(
                        'type'=>'hidden',
                         ),$attr
                    ));
    }
    function render(){
        if($this->owner == $this->form){
            $this->form->template_chunks['form']->appendHTML('Content',$this->getInput());
        }else $this->output($this->getInput());
    }
}
