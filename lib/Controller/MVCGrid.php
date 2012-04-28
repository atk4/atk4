<?php
class Controller_MVCGrid extends AbstractController {
    /**
     * Connects regular grid with a model and imports fields as columns.
     *
     * In most cases the following use is sufficient
     * $grid->setModel('SomeModel');
     *
     * You can use Grid only with a single Model to simplify select.
     */

    public $model=null;
    public $grid=null;

    public $type_associations=array(
        'string'=>'text',
        'int'=>'number',
        'numeric'=>'number',
        'real'=>'real',
        'money'=>'money',
        'text'=>'shorttext',
        'reference'=>'text',
        'datetime'=>'timestamp',
        'date'=>'date',
        'daytime'=>'daytime',
        'daytime_total'=>'daytime_total',
        'boolean'=>'boolean',
        'list'=>'text',
        'readonly'=>'text',
        'image'=>'text',
        'file'=>'referenece',
    );

    function setActualFields($fields){
        if($this->owner->model->hasMethod('getActualFields'))
            $this->importFields($this->owner->model,$fields);
    }

    function importFields($model,$fields=undefined){

        $this->model=$model;
        $this->grid=$this->owner;

        if($fields===false)return;

        if(!$fields)$fields='visible';
        if(!is_array($fields))$fields=$model->getActualFields($fields);
        foreach($fields as $field){
            $this->importField($field);
        }
        $model->setActualFields($fields);

        return $this;
    }
    function importField($field){

        $field=$this->model->hasElement($field);
        if(!$field)return;

        $field_name=$field->short_name;

        if($field instanceof Model_Field_Reference){
            $field_name=$field->getDereferenced();
        }

        $field_type=$this->getFieldType($field);
        $field_caption=$field->caption();

        $this->field_associations[$field_name]=$field;

        $column = $this->owner->addColumn($field_type,$field_name,$field_caption);

        if($field->sortable())$column->makeSortable();

        return $column;
    }
    /** Redefine this to add special handling of your own fields */
    function getFieldType($field){
        $type=$field->type();
        //$type='text';

        if(isset($this->type_associations[$type]))$type=$this->type_associations[$type];

        if($type=='text' && $field->allowHtml())$type='html';

        if($field->display() && false){
            // this is wrong and obsolete, as hasOne uses display for way different purpose

            $tmp=$field->display();
            if(is_array($tmp))$tmp=$tmp['grid'];
            if($tmp)$type=$tmp;
        }

        return $type;
    }
    function update($form){
        $this->getFields();
        $htis->model->update();
        echo "update in $form";
    }
}
