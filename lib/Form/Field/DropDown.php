<?php
class Form_Field_DropDown extends Form_Field_ValueList {
    public $empty_value='';

    function emptyValue($v){
        $this->empty_value=$v;
        return $this;
    }
	function validate(){
		if(!$this->value)return parent::validate();
        $this->getValueList(); //otherwise not preloaded?
		if(!isset($this->value_list[$this->value])){
			/*
			   if($this->api->isAjaxOutput()){
			   $this->ajax()->displayAlert($this->short_name.": This is not one of the offered values")
			   ->execute();
			   }
			 */
			$this->form->errors[$this->short_name]="This is not one of the offered values";
		}
		return parent::validate();
	}
	function getInput($attr=array()){
		$output=$this->getTag('select',array_merge(array(
						'name'=>$this->name,
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
		return $this->getTag('option',array(
					'value'=>$value,
					'selected'=>$value == $this->value
					));
	}
}
