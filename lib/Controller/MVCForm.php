<?php // vim:ts=4:sw=4:et:fdm=marker
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
class Controller_MVCForm extends AbstractController
{
    public $model=null;
    public $form=null;

    public $field_associations=array();

    public $type_associations=array(
        'string'=>'Line',
        'text'=>'Text',
        'int'=>'Number',
        'numeric'=>'Number',
        'money'=>'Money',
        'real'=>'Number',
        'date'=>'DatePicker',
        'datetime'=>'DatePicker',
        'daytime'=>'Time',
        'boolean'=>'Checkbox',
        'reference'=>'Readonly',
        'reference_id'=>'DropDown',
        'password'=>'Password',
        'list'=>'DropDown',
        'radio'=>'Radio',
        'readonly'=>'Readonly',
        'image'=>'Image',
        'file'=>'Upload',
    );
    public function setActualFields($fields)
    {
        $this->importFields($this->owner->model,$fields);
    }

    private $_hook_set=false;

    /**
     * importFields description blah
     *
     * @param  [type] $model  [description]
     * @param  [type] $fields [description]
     * @return [type]         [description]
     */
    public function importFields($model,$fields=undefined)
    {
        $this->model=$model;
        $this->form=$this->owner;

        if($fields===false)return;

        if(!$fields || $fields===undefined)$fields='editable';
        if(!is_array($fields))$fields=$model->getActualFields($fields);
        foreach ($fields as $field) {
            $this->importField($field);
        }

        if (!$this->_hook_set) {
            $this->owner->addHook('update',array($this,'update'));
            $model->addHook('afterLoad',array($this,'setFields'));
            $this->_hook_set=true;
        }

        return $this;
    }
    public function importField($field, $field_name=null)
    {
        $field=$this->model->hasElement($field);
        if(!$field)return;
        if(!$field->editable())return;

        if (!$field_name) {
            $field_name=$this->_unique($this->owner->elements,$field->short_name);
        }
        $field_type=$this->getFieldType($field);
        $field_caption=$field->caption();

        $this->field_associations[$field_name]=$field;

        if ($field->listData() || $field instanceof Field_Reference) {
            if($field_type=='Line')$field_type='DropDown';
        }

        $form_field = $this->owner->addField($field_type,$field_name,$field_caption);
        $form_field->set($field->get());

        $field_placeholder = $field->placeholder() ?: $field->emptyText() ?: null;
        if ($field_placeholder) {
            $form_field->setAttr('placeholder', $field_placeholder);
        }

        if ($field->hint()) {
            $form_field->setFieldHint($field->hint());
        }

        if ($field->listData()) {
            $a=$field->listData();
            $form_field->setValueList($a);
        }
        if ($msg=$field->mandatory()) {
            $form_field->validateNotNULL($msg);
        }

        if ($field instanceof Field_Reference || $field_type=='reference') {
            $form_field->setModel($field->getModel());
        }
        if ($field->theModel) {
            $form_field->setModel($field->theModel);
        }
        if ($form_field instanceof Form_Field_ValueList && !$field->mandatory()) {
            $form_field->setEmptyText($field->emptyText());
        }

        if($field->onField()){
            call_user_func($field->onField(), $form_field);
        }

        return $form_field;
    }
    /** Copies model field values into form */
    public function setFields()
    {
        foreach ($this->field_associations as $form_field=>$model_field) {
            $this->form->set($form_field,$model_field->get());
        }
    }
    public function getFields()
    {
        $models=array();
        foreach ($this->field_associations as $form_field=>$model_field) {
            $model_field->set($v=$this->form->get($form_field));
            if (!isset($models[$model_field->owner->name])) {
                $models[$model_field->owner->name]=$model_field->owner;
            }
        }

        return $models;
    }
    /** Redefine this to add special handling of your own fields */
    public function getFieldType($field)
    {
        $type='Line';

        if(isset($this->type_associations[$field->type()]))$type=$this->type_associations[$field->type()];
        if($field instanceof Model_Field_Reference)$type='DropDown';

        if ($field->display()) {
            $tmp=$field->display();
            if(is_array($tmp))$tmp=$tmp['form'];
            if($tmp)$type=$tmp;
        }

        return $type;
    }
    public function update($form)
    {
        $models=$this->getFields();
        foreach($models as $model)$model->save();
    }
}
