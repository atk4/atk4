<?php
class Form_Field_CheckboxList extends Form_Field_ValueList {
	/*
	 * This field will create 2 column of checkbox+labels from defived value
	 * list. You may check as many as you like and when you save their ID
	 * values will be stored coma-separated in varchar type field.
	 *
	 $f->addField('CheckboxList','producers','Producers')->setValueList(array(
	 1=>'Mr John',
	 2=>'Piter ',
	 3=>'Michail Gershwin',
	 4=>'Bread and butter',
	 5=>'Alex',
	 6=>'Benjamin',
	 7=>'Rhino',
	 ));

	 */

	var $columns=2;
	function validate(){
		return true;
	}
	function getInput($attr=array()){
		$output='<table class="atk-checkboxlist" border=0 id="'.$this->name.'"><tbody>';
		$column=0;
		$current_values=explode(',',$this->value);
		$i=0;//Skai
		foreach($this->getValueList() as $value=>$descr){
			if($column==0){
				$output.="<tr><td align=\"left\">";
			}else{
				$output.="</td><td align=\"left\">";
			}

			$output.=
				$this->getTag('input',array(
							'type'=>'checkbox',
							'value'=>$value,
							'name'=>$this->name.'['.$i++.']',//Skai
							'checked'=>in_array($value,$current_values)
							)).htmlspecialchars($descr);
			$column++;
			if($column==$this->columns){
				$output.="</td></tr>";
				$column=0;
			}
		}
		$output.="</tbody></table>";
		return $output;
	}

	function loadPOST(){
		$data=$_POST[$this->name];
		if(is_array($data))
			$data=join(',',$data);
		else
			$data='';

		$gpc = get_magic_quotes_gpc();
		if ($gpc){
			$this->set(stripslashes($data));
		} else {
			$this->set($data);
		}
	}
}

