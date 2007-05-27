<?
// 
// TODO: why this class is here and how can it be used?
//
class Form_Field_Category extends Form_Field_ValueList {
    function validate(){
        if(!isset($this->value_list[$this->value])){
            $this->owner->errors[$this->short_name]="This is not one of offered values";
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
            $output.=
                $this->getTag('option',array(
                        'value'=>$value,
                        'selected'=>$value == $this->value
                    ))
                .htmlspecialchars($descr)
                .$this->getTag('/option');
        }
        $output.=$this->getTag('/select');
		$output.=$this->getTag('img', array_merge(
											array(
												'src' => 'http://www.myadminimages.com/images/icons/trash_b.gif',
												'alt' => 'drop',
												'style' => 'width: 17px',
												'onclick' => 'reload_campaign_field_drop_' . $this->owner->name . '("' . $this->name . '")'
											)
										)
				 );

		$output.=$this->getTag('input', array_merge(
											array(
												'id' => $this->name . '_new'
											)
										)
				 );
		$output.=$this->getTag('img', array_merge(
											array(
												'src' => 'http://www.myadminimages.com/images/icons/add_icon.gif',
												'alt' => 'add',
												'style' => 'width: 17px',
												'onclick' => 'reload_campaign_field_add_' . $this->owner->name . '("' . $this->name . '")'
											)
										)
				 );
        return $output;
    }
}
