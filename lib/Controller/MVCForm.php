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
/**
 * Connects regular form with a model and imports some fields. It also
 * binds action on form->update(), which will now force model to be updated.
 *
 * In most cases the following use is sufficient
 * $form->setModel('SomeModel');
 *
 * If you want to import fields from multiple models, you can use this:
 *  $ctl = $form->importFields($model,array('name','surname');
 *
 * and if you want to use your own class based on this one, syntax is:
 *  $ctl = $form->add('Controller_MVCForm_Derived')->importFields($model,array('name','surname'));
 * 
 *
 * You can subsequently call importField() to add additional fields such as:
 *
 *  $form_field = $ctl->importField('age');
 *
 * which will return newly added form field.
 */
class Controller_MVCForm extends AbstractController {

    public $model=null;
    public $form=null;

    public $field_associations=array();

    public $type_associations=array(
        'string'=>'line',
        'text'=>'text',
        'int'=>'number',
        'numeric'=>'number',
        'money'=>'money',
        'real'=>'number',
        'date'=>'DatePicker',
        'datetime'=>'DatePicker',
        'daytime'=>'timepickr',
        'boolean'=>'checkbox',
        'reference'=>'readonly',
        'reference_id'=>'dropdown',
        'password'=>'password',
        'list'=>'dropdown',
        'radio'=>'Radio',
        'readonly'=>'readonly',
        'image'=>'image',
        'file'=>'upload',
    );
    function setActualFields($fields){
        $this->importFields($this->owner->model,$fields);
    }

    private $_hook_set=false;
    function importFields($model,$fields=undefined){

        $this->model=$model;
        $this->form=$this->owner;

        if($fields===false)return;

        if(!$fields || $fields===undefined)$fields='editable';
        if(!is_array($fields))$fields=$model->getActualFields($fields);
        foreach($fields as $field){
            $this->importField($field);
        }

        if(!$this->_hook_set){
            $this->owner->addHook('update',array($this,'update'));
            $model->addHook('afterLoad',array($this,'setFields'));
            $this->_hook_set=true;
        }

        return $this;
    }
    function importField($field, $field_name=null){

        $field=$this->model->hasElement($field);
        if(!$field)return;
        if(!$field->editable())return;

        if (!$field_name){
            $field_name=$this->_unique($this->owner->elements,$field->short_name);
        }
        $field_type=$this->getFieldType($field);
        $field_caption=$field->caption();

        $this->field_associations[$field_name]=$field;

        if($field_type=='checkbox'){
            if(!$field->listData)$field->enum(array(1,0));
        }elseif($field->listData() || $field instanceof Field_Reference){
            if($field_type=='line')$field_type='dropdown';
        }

        $form_field = $this->owner->addField($field_type,$field_name,$field_caption);
        $form_field->set($field->get());

        if($field_type=='checkbox'){
            reset($field->listData);
            list($form_field->true_value,$junk)=each($field->listData);
            list($form_field->false_value,$junk)=each($field->listData);
        }elseif($field->listData()){
            $a=$field->listData();
            $form_field->setValueList($a);
        }
        if ($msg=$field->mandatory()){
            $form_field->validateNotNULL($msg);
        }

        if($field instanceof Field_Reference)$form_field->setModel($field->getModel());
        if($field->theModel){
            $form_field->setModel($field->theModel);
        }
        if($form_field instanceof Form_Field_ValueList)$form_field->setEmptyText($field->emptyText());

        return $form_field;
    }
    /** Copies model field values into form */
    function setFields(){
        foreach($this->field_associations as $form_field=>$model_field){
            $this->form->set($form_field,$model_field->get());
        }
    }
    function getFields(){
        $models=array();
        foreach($this->field_associations as $form_field=>$model_field){
            $model_field->set($v=$this->form->get($form_field));
            if(!isset($models[$model_field->owner->name])){
                $models[$model_field->owner->name]=$model_field->owner;
            }
        }
        return $models;
    }
    /** Redefine this to add special handling of your own fields */
    function getFieldType($field){
        $type='line';

        if(isset($this->type_associations[$field->type()]))$type=$this->type_associations[$field->type()];
        if($field instanceof Model_Field_Reference)$type='dropdown';

        if($field->display()){
            $tmp=$field->display();
            if(is_array($tmp))$tmp=$tmp['form'];
            if($tmp)$type=$tmp;
        }

        return $type;
    }
    function update($form){
        $models=$this->getFields();
        foreach($models as $model)$model->save();
    }
}
