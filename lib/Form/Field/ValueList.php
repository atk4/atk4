<?php
/*
 * This is abstract class. Use this as a base for all the controls
 * which operate with predefined values such as dropdowns, checklists
 * etc
 */
class Form_Field_ValueList extends Form_Field {
	public $value_list=array(
			0=>'No available options #1',
			1=>'No available options #2',
			2=>'No available options #3'
			);
    public $empty_text='';
    function setModel($m){
        $ret=parent::setModel($m);

        $this->setValueList(array('foo','bar'));
        return $ret;
    }
    /** Default value which is displayed on a null-value option. Set to "Select.." or "Pick one.." */
    function setEmptyText($empty_text){
        $this->empty_text=$empty_text;
        return $this;
    }
	function getValueList(){

        if($this->model){
            $title=$this->model->getTitleField();
            $id=$this->model->id_field;
            if ($this->empty_text){
                $res=array(''=>$this->empty_text);
            } else {
                $res = array();
            }
			foreach($this->model as $row){
				$res[$row[$id]]=$row[$title];
			}
			return $this->value_list=$res;
		}

        if($this->empty_text && isset($this->value_list[''])){
            $this->value_list['']=$this->empty_text;
        }
		return $this->value_list;
	}
	function setValueList($list){
		$this->value_list = $list;
		return $this;
	}
	function loadPOST(){
		$data=$_POST[$this->name];
		if(is_array($data))$data=join(',',$data);
		$gpc = get_magic_quotes_gpc();
		if ($gpc){
			if(isset($_POST[$this->name]))$this->set(stripslashes($data));
		} else {
			if(isset($_POST[$this->name]))$this->set($data);
		}
	}
}
