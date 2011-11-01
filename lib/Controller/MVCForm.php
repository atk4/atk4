<?php
class Controller_MVCForm extends AbstractController {
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

    public $model=null;
    public $form=null;

    public $field_associations=array();

    public $type_associations=array(
        'string'=>'line',
        'text'=>'text',
        'int'=>'line',
        'numeric'=>'line',
        'money'=>'line',
        'real'=>'line',
        'date'=>'DatePicker',
        'datetime'=>'DatePicker',
        'daytime'=>'timepickr',
        'boolean'=>'checkbox',
        'reference'=>'readonly',
        'reference_id'=>'reference',
        'password'=>'password',
        'list'=>'reference',
        'radio'=>'Radio',
        'readonly'=>'readonly',
        'image'=>'image',
        'file'=>'upload',
    );

    function importFields($model,$fields=undefined){

        $this->model=$model;
        $this->form=$this->owner;

        if(!$fields)$fields='editable';
        if(!is_array($fields))$fields=$model->getActualFields($fields);
        foreach($fields as $field){
            $this->importField($field);
        }

        $this->owner->addHook('update',array($this,'update'));
        $model->addHook('afterLoad',array($this,'setFields'));

        return $this;
    }
    function importField($field){

        $field=$this->model->hasElement($field);
        if(!$field)return;
        if(!$field->editable())return;

        $field_name=$this->_unique($this->owner->elements,$field->short_name);
        $field_type=$this->getFieldType($field);
        $field_caption=$field->caption();

        $this->field_associations[$field_name]=$field;

        $form_field = $this->owner->addField($field_type,$field_name,$field_caption);
        $form_field->set($field->get());

        if($field instanceof Model_Field_Reference)$form_field->setModel($field->model);

        return $form_field;
    }
    /** Copies model field values into form */
    function setFields(){
        foreach($this->field_associations as $form_field=>$model_field){
            $this->form->set($form_field,$model_field->get());
        }
    }
    function getFields(){
        foreach($this->field_associations as $form_field=>$model_field){
            $model_field->set($this->form->get($form_field));
        }
    }
    /** Redefine this to add special handling of your own fields */
    function getFieldType($field){
        $type='line';

        if(isset($this->type_associations[$type]))$type=$this->type_associations[$type];
        if($field instanceof Model_Field_Reference)$type='dropdown';

        if($field->display())$type=$field->display();

        return $type;
    }
    function update($form){
        $this->getFields();
        $this->model->update();
    }
}
