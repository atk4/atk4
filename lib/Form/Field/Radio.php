<?php
class Form_Field_Radio extends Form_Field_ValueList {
	function validate(){
		if(!isset($this->value_list[$this->value])){
			/*
			   if($this->api->isAjaxOutput()){
			   echo $this->ajax()->displayAlert($this->short_name.":"."This is not one of offered values")->execute();
			   }
			 */
			$this->displayFieldError("This is not one of offered values");
		}
		return parent::validate();
	}
	function getInput($attr=array()){
		$output = '<div id="'.$this->name.'" class="atk-form-options">';
		foreach($this->getValueList() as $value=>$descr){
			$output.=
				"<div>".$this->getTag('input',
						array_merge(
							array(
								'id'=>$this->name.'_'.$value,
								'name'=>$this->name,
								'type'=>'radio',
								'value'=>$value,
								'checked'=>$value == $this->value
							     ),
							$this->attr,
							$attr
							))
				."<label for='".$this->name.'_'.$value."'>".htmlspecialchars($descr)."</label></div>";
		}
		$output .= '</div>';
		return $output;
	}
}
