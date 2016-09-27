<?php
/**
 * Connects regular grid with a Agile/Data model and imports fields as columns.
 *
 * In most cases the following use is sufficient
 * $grid = $page->add('Grid', ['default_controller' => 'ADGrid']);
 * $grid->setModel('SomeModel');
 *
 * You can use Grid only with a single Model.
 */
class Controller_ADGrid extends AbstractController
{
    /** @var \atk4\data\Model */
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
        'integer' => 'number',
        'numeric' => 'number',
        'real' => 'real',
        'float' => 'real',
        'money' => 'money',
        'text' => 'shorttext',
        'reference' => 'text',
        'reference_id' => 'text',
        'datetime' => 'timestamp',
        'date' => 'date',
        'daytime' => 'time',
        'time' => 'time',
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
     * Initialization.
     */
    public function init()
    {
        parent::init();

        if (! $this->owner->model instanceof \atk4\data\Model) {
            throw $this->exception('Controller_ADGrid can only be used with Agile Data \atk4\data\Model models');
        }
    }

    /**
     * Adds additional type association.
     *
     * @param string $k model field type
     * @param string $v grid column type
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
        /** @type \atk4\data\Model $this->owner->model */
        $this->importFields($this->owner->model, $fields);
    }

    /**
     * Import model fields in grid.
     *
     * @param \atk4\data\Model $model
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
            if ($model->only_fields) {
                $fields = $model->only_fields;
            } else {
                $fields = [];
                // get all field-elements
                foreach ($model->elements as $field => $f_object) {
                    if ($f_object instanceof \atk4\data\Field
                        && $f_object->isVisible()
                        && !$f_object->isHidden()
                    ) {
                        $fields[] = $field;
                    }
                }
            }
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        // import fields one by one
        foreach ($fields as $field) {
            $this->importField($field);
        }
        $model->onlyFields($fields);

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
        /** @type \atk4\data\Field $field */

        if (!$field || !$field->isVisible() || $field->isHidden()) {
            return;
        }

        $field_name = $field->short_name;
        $field_type = $this->getFieldType($field);
        $field_caption = isset($field->caption) ? $field->caption : null;

        $this->field_associations[$field_name] = $field;

        $column = $this->owner->addColumn($field_type, $field_name, $field_caption);

        if (isset($field->ui['sortable']) && $field->ui['sortable'] && $column->hasMethod('makeSortable')) {
            $column->makeSortable();
        }

        return $column;
    }

    /**
     * Returns grid column type associated with model field.
     *
     * Redefine this method to add special handling of your own fields.
     *
     * @param \atk4\data\Field $field
     *
     * @return string
     */
    public function getFieldType($field)
    {
        // default column type
        $type = $field->type;

        // try to find associated field type
        if (isset($this->type_associations[$type])) {
            $type = $this->type_associations[$type];
        }

        if ($type == 'text' && isset($field->allowHtml) && $field->allowHtml) {
            $type = 'html';
        }

        // if grid column type/formatter explicitly set in model
        if (isset($field->ui['display'])) {
            // @todo this is wrong and obsolete, as hasOne uses display for way different purpose

            $tmp = $field->ui['display'];
            if (isset($tmp['grid'])) {
                $tmp = $tmp['grid'];
            }
            if (is_string($tmp) && $tmp) {
                $type = $tmp;
            }
        }

        return $type;
    }
}
