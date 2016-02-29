<?php
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
    /** @var Model */
    public $model = null;

    /** @var Form */
    public $form = null;

    /**
     * Field associations form_field => model_field
     *
     * @var array
     */
    public $field_associations = array();

    /**
     * Field type associations model_field_type => form_field_type
     *
     * @var array
     */
    public $type_associations = array(
        'string' => 'Line',
        'text' => 'Text',
        'int' => 'Number',
        'numeric' => 'Number',
        'money' => 'Money',
        'real' => 'Number',
        'date' => 'DatePicker',
        'datetime' => 'DatePicker',
        'daytime' => 'Time',
        'boolean' => 'Checkbox',
        'reference' => 'Readonly',
        'reference_id' => 'DropDown',
        'password' => 'Password',
        'list' => 'DropDown',
        'radio' => 'Radio',
        'readonly' => 'Readonly',
        'image' => 'Image',
        'file' => 'Upload',
    );

    /**
     * Is update hook already set?
     *
     * @var bool
     */
    private $_hook_set = false;

    /** @var Form */
    public $owner;



    /**
     * Import model fields in form.
     *
     * @param array|string|bool $fields
     */
    public function setActualFields($fields)
    {
        $this->importFields($this->owner->model, $fields);
    }


    /**
     * Import model fields in form.
     *
     * Use $fields === false if you want to associate form with model, but don't create form fields.
     *
     * @param Model $model
     * @param array|string|bool $fields
     *
     * @return void|$this
     */
    public function importFields($model, $fields = undefined)
    {
        $this->model = $model;
        $this->form = $this->owner;

        if ($fields === false) {
            return;
        }

        if (!$fields || $fields === undefined) {
            $fields = 'editable';
        }
        if (!is_array($fields)) {
            // note: $fields parameter is only useful if model is SQL_Model
            $fields = $model->getActualFields($fields);
        }

        // import fields one by one
        foreach ($fields as $field) {
            $this->importField($field);
        }

        // set update hook
        if (!$this->_hook_set) {
            $this->owner->addHook('update', array($this, 'update'));
            $model->addHook('afterLoad', array($this, 'setFields'));
            $this->_hook_set = true;
        }

        return $this;
    }

    /**
     * Import one field from model into form.
     *
     * @param string $field
     * @param string $field_name
     *
     * @return void|Form_Field
     */
    public function importField($field, $field_name = null)
    {
        $field = $this->model->hasElement($field);
        if (!$field) {
            return;
        }
        /** @var Field $field */
        if (!$field->editable()) {
            return;
        }

        if ($field_name === null) {
            $field_name = $this->_unique($this->owner->elements, $field->short_name);
        }
        $field_type = $this->getFieldType($field);
        $field_caption = $field->caption();

        $this->field_associations[$field_name] = $field;

        if ($field->listData() || $field instanceof Field_Reference) {
            if ($field_type == 'Line') {
                $field_type = 'DropDown';
            }
        }

        $form_field = $this->owner->addField($field_type, $field_name, $field_caption);
        $form_field->set($field->get());

        $field_placeholder = $field->placeholder() ?: $field->emptyText() ?: null;
        if ($field_placeholder) {
            $form_field->setAttr('placeholder', $field_placeholder);
        }

        if ($field->hint()) {
            $form_field->setFieldHint($field->hint());
        }

        if ($field->listData()) {
            $a = $field->listData();
            $form_field->setValueList($a);
        }
        if ($msg = $field->mandatory()) {
            $form_field->validateNotNULL($msg);
        }

        if ($field instanceof Field_Reference || $field_type == 'reference') {
            $form_field->setModel($field->getModel());
        }
        if ($field->theModel) {
            $form_field->setModel($field->theModel);
        }
        if ($form_field instanceof Form_Field_ValueList && !$field->mandatory()) {
            /** @var string $text */
            $text = $field->emptyText();
            $form_field->setEmptyText($text);
        }

        if ($field->onField()) {
            call_user_func($field->onField(), $form_field);
        }

        return $form_field;
    }


    /**
     * Copies model field values into form.
     */
    public function setFields()
    {
        foreach ($this->field_associations as $form_field => $model_field) {
            $this->form->set($form_field, $model_field->get());
        }
    }

    /**
     * Returns array of models model_name => Model used in this form.
     *
     * @return array
     */
    public function getFields()
    {
        $models = array();

        /**
         * @var Form_Field $form_field
         * @var Field $model_field
         */
        foreach ($this->field_associations as $form_field => $model_field) {
            $v = $this->form->get($form_field);
            $model_field->set($v);
            if (!isset($models[$model_field->owner->name])) {
                $models[$model_field->owner->name] = $model_field->owner;
            }
        }

        return $models;
    }

    /**
     * Returns form field type associated with model field.
     *
     * Redefine this method to add special handling of your own fields.
     *
     * @param Field $field
     *
     * @return string
     */
    public function getFieldType($field)
    {
        // default form field type
        $type = 'Line';

        // try to find associated form field type
        if (isset($this->type_associations[$field->type()])) {
            $type = $this->type_associations[$field->type()];
        }
        if ($field instanceof Field_Reference) {
            $type = 'DropDown';
        }

        // if form field type explicitly set in model
        if ($field->display()) {
            $tmp = $field->display();
            if (is_array($tmp)) {
                $tmp = $tmp['form'];
            }
            if ($tmp) {
                $type = $tmp;
            }
        }

        return $type;
    }

    /**
     * Update form model
     *
     * @param Form $form
     */
    public function update($form)
    {
        $models = $this->getFields();
        foreach ($models as $model) {
            $model->save();
        }
    }
}
