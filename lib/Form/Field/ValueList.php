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
    public $empty_text=null;
    protected $empty_value=''; // don't change this value

    function validate(){
        if(!$this->value)return parent::validate();
        $this->getValueList(); //otherwise not preloaded?

        $values = explode(',',$this->value);
        foreach($values as $v){
            if(!isset($this->value_list[$v])){
                $this->displayFieldError("Value $v is not one of the offered values");
                return parent::validate();
            }
        }
        return parent::validate();
    }
    function setModel($m){
        $ret=parent::setModel($m);
        $this->setValueList(array('foo','bar'));
        return $ret;
    }
    /** Default text which is displayed on a null-value option. Set to "Select.." or "Pick one.." */
    function setEmptyText($empty_text){
        $this->empty_text = $empty_text;
        return $this;
    }

    function getValueList(){
        if($this->model){
            $title=$this->model->getTitleField();
            $id=$this->model->id_field;
            if ($this->empty_text){
                $res=array($this->empty_value => $this->empty_text);
            } else {
                $res = array();
            }
            foreach($this->model as $row){
                $res[$row[$id]]=$row[$title];
            }
            return $this->value_list=$res;
        }

        if($this->empty_text && !isset($this->value_list[$this->empty_value])){
            $this->value_list[$this->empty_value]=$this->empty_text;
        }
        return $this->value_list;
    }
    function setValueList($list){
        $this->value_list = $list;
        return $this;
    }
    function loadPOST(){
        if(isset($_POST[$this->name])){
            $data=$_POST[$this->name];
            if(is_array($data))$data=implode(',',$data);
            
            if (get_magic_quotes_gpc()){
                $this->set(stripslashes($data));
            } else {
                $this->set($data);
            }
        }
    }
}
