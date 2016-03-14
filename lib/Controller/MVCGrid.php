<?php
/**
 * Connects regular grid with a model and imports fields as columns.
 *
 * In most cases the following use is sufficient
 * $grid->setModel('SomeModel');
 *
 * You can use Grid only with a single Model to simplify select.
 */
class Controller_MVCGrid extends AbstractController
{
    /** @var Model */
    public $model = null;

    /** @var Grid */
    public $grid = null;

    /**
     * Field associations grid_column => model_field
     *
     * @var array
     */
    public $field_associations = array();

    /**
     * Field type associations model_field_type => grid_column_type/formatter
     *
     * @var array
     */
    public $type_associations = array(
        'string' => 'text',
        'int' => 'number',
        'numeric' => 'number',
        'real' => 'real',
        'money' => 'money',
        'text' => 'shorttext',
        'reference' => 'text',
        'reference_id' => 'text',
        'datetime' => 'timestamp',
        'date' => 'date',
        'daytime' => 'time',
        'boolean' => 'boolean',
        'list' => 'text',
        'radio' => 'text',
        'readonly' => 'text',
        'image' => 'text',
        'file' => 'reference',
        'password' => 'password',
    );

    /** @var Grid */
    public $owner;



    /**
     * Adds additional type association.
     *
     * @param string $k model field type
     * @param string $v grid columnt type
     */
    public function addTypeAssociation($k, $v)
    {
        $this->type_associations[$k] = $v;
    }

    /**
     * Import model fields in grid.
     *
     * @param array|string|bool $fields
     */
    public function setActualFields($fields)
    {
        if ($this->owner->model->hasMethod('getActualFields')) {
            $this->importFields($this->owner->model, $fields);
        }
    }

    /**
     * Import model fields in form.
     *
     * @param Model $model
     * @param array|string|bool $fields
     *
     * @return void|$this
     */
    public function importFields($model, $fields = UNDEFINED)
    {
        $this->model = $model;
        $this->grid = $this->owner;

        if ($fields === false) {
            return;
        }

        if (!$fields || $fields === UNDEFINED) {
            $fields = 'visible';
        }
        if (!is_array($fields)) {
            // note: $fields parameter only useful if model is SQL_Model
            $fields = $model->getActualFields($fields);
        }

        // import fields one by one
        foreach ($fields as $field) {
            $this->importField($field);
        }
        $model->setActualFields($fields);

        return $this;
    }

    /**
     * Import one field from model into grid.
     *
     * @param string $field
     *
     * @return void|Grid|Controller_Grid_Format
     */
    public function importField($field)
    {
        $field = $this->model->hasElement($field);
        if (!$field) {
            return;
        }
        /** @type Field $field */

        $field_name = $field->short_name;

        if ($field instanceof Field_Reference) {
            $field_name = $field->getDereferenced();
        }

        $field_type = $this->getFieldType($field);

        /** @type string $field_caption */
        $field_caption = $field->caption();

        $this->field_associations[$field_name] = $field;

        $column = $this->owner->addColumn($field_type, $field_name, $field_caption);

        if ($field->sortable() && $column->hasMethod('makeSortable')) {
            $column->makeSortable();
        }

        return $column;
    }

    /**
     * Returns grid column type associated with model field.
     *
     * Redefine this method to add special handling of your own fields.
     *
     * @param Field $field
     *
     * @return string
     */
    public function getFieldType($field)
    {
        // default column type
        $type = $field->type();

        // try to find associated form field type
        if (isset($this->type_associations[$type])) {
            $type = $this->type_associations[$type];
        }

        if ($type == 'text' && $field->allowHtml()) {
            $type = 'html';
        }

        // if grid column type/formatter explicitly set in model
        if ($field->display()) {
            // @todo this is wrong and obsolete, as hasOne uses display for way different purpose

            $tmp = $field->display();
            if (is_array($tmp)) {
                $tmp = $tmp['grid'];
            }
            if ($tmp) {
                $type = $tmp;
            }
        }

        return $type;
    }
}
