<?php
/**
 * Connects regular view with a Agile Data model and imports it field values and captions.
 *
 * In opposite of AbstractView->modelRender() method, this one will properly treat field
 * values depending on their type. It will also set field captions if such tags are found
 * in views template. Caption tags should be named exactly the same as model field names
 * with post-fix "-caption".
 *
 * To use this controller you only have to set $default_controller = 'ADView' in your views
 * properties.
 */
class Controller_ADView extends AbstractController
{
    /** @var \atk4\data\Model */
    public $model = null;

    /** @var View */
    public $owner;



    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();

        if (! $this->owner->model instanceof \atk4\data\Model) {
            throw $this->exception('Controller_ADView can only be used with Agile Data \atk4\data\Model models');
        }
    }

    /**
     * Import model fields in view.
     *
     * @param array|string|bool $fields
     */
    public function setActualFields($fields)
    {
        /** @type \atk4\data\Model $this->owner->model */
        $this->importFields($this->owner->model, $fields);
    }


    /**
     * Import model fields in view.
     *
     * Use $fields === false if you want to associate view with model, but don't fill view fields.
     *
     * @param \atk4\data\Model $model
     * @param array|string|bool $fields
     *
     * @return void|$this
     */
    public function importFields($model, $fields = null)
    {
        $this->model = $model;

        if ($fields === false) {
            return;
        }

        if (!$fields) {
            if ($model->only_fields) {
                $fields = $model->only_fields;
            } else {
                $fields = [];
                // get all field-elements
                foreach ($model->elements as $field => $f_object) {
                    if ($f_object instanceof \atk4\data\Field) {
                        $fields[] = $f_object;
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

        return $this;
    }

    /**
     * Import one field from model into form.
     *
     * @param string|\atk4\data\Field $field
     *
     * @return void
     */
    public function importField($field)
    {
        if (is_string($field)) {
            $field = $this->model->hasElement($field);
        }
        /** @type \atk4\data\Field $field */

        if (!$field || !$field instanceof \atk4\data\Field) {
            return;
        }

        // prepare name
        $name = $field->short_name;

        // prepare caption
        $caption = isset($field->ui['caption'])
            ? $field->ui['caption']
            : ucwords(str_replace('_', ' ', $name));

        // prepare value
        $value = $field->get();

        // take care of some special data types
        switch ($field->type) {
            case 'boolean':
                if ($value === true || $value === 1 || $value === 'Y') {
                    $value = '<i class="icon-check">&nbsp;'.$this->app->_('yes').'</i>';
                } else {
                    $value = '<i class="icon-check-empty">&nbsp;'.$this->app->_('no').'</i>';
                }
                break;
            case 'date':
                $value = $value->format($this->app->getConfig('locale/date', 'Y-m-d'));
                break;
            case 'datetime':
                $value = $value->format($this->app->getConfig('locale/datetime', 'Y-m-d H:i:s'));
                break;
            case 'time':
                $value = $value->format($this->app->getConfig('locale/time', 'H:i:s'));
                break;
            default:
                $value = $this->model->persistence->typecastsaveField($field, $value);
        }

        // support valueList
        if (isset($field->ui['valueList'][$value])) {
            $value = $field->ui['valueList'][$value];
        }

        // support hasOne
        if ($ref_field = $this->model->hasRef($name)) {
            $m = $ref_field->ref();
            $value = $m->get($m->title_field);
        }

        // fill template
        $data = array($name => $value, $name.'-caption' => $caption);
        $this->owner->template->setHTML($data);
    }
}
