<?php
/**
 * Undocumented
 */
class SQL_Many extends AbstractModel
{
    public $model_name = null;
    public $their_field = null;
    public $orig_conditions = null;
    public $our_field = null;
    public $auto_track_element = true;
    public $relation = null;
    public $table_alias = null;

    // {{{ Inherited properties

    /** @var SQL_Model */
    public $owner;

    /** @var SQL_Model */
    public $model;

    // }}}

    public function set($model, $their_field = null, $our_field = null, $relation = null)
    {
        $this->model_name = is_string($model) ? $model : get_class($model);
        $this->model_name = $this->app->normalizeClassName($this->model_name, 'Model');

        if ($relation) {
            $this->relation = $relation;
            $this->our_field = $our_field;
            $this->their_field = $their_field;

            return $this;
        }

        $this->their_field = $their_field ?: $this->owner->table.'_id';

        $this->our_field = $our_field ?: $this->owner->id_field;

        return $this;
    }
    public function from($m)
    {
        if ($m === UNDEFINED) {
            return $this->relation;
        }
        $this->relation = $m;

        return $this;
    }
    public function saveConditions()
    {
        $this->orig_conditions = $this->model->_dsql()->args['where'];

        return $this;
    }
    public function restoreConditions()
    {
        if (!$this->model) {
            // adding new model
            if ($this->table_alias) {
                $this->model = $this->add($this->model_name, array('table_alias' => $this->table_alias));
            } else {
                $this->model = $this->add($this->model_name);
            }
            $this->saveConditions();
        }
        /** @type SQL_Model $this->model */
        $this->model->_dsql()->args['where'] = $this->orig_conditions;

        return $this;
    }
    public function refSQL($mode = null)
    {
        if ($mode == 'model') {
            /** @type SQL_Model $m */
            $m = $this->add($this->model_name);

            return $m->addCondition($this->their_field, $this->owner->getElement($this->our_field));
        }

        $this->restoreConditions();

        return $this->model->addCondition($this->their_field, $this->owner->getElement($this->our_field));
    }
    public function ref($mode = null)
    {
        if (!$this->owner->loaded()) {
            throw $this->exception('Model must be loaded before traversing reference');
        }

        if ($mode == 'model') {
            /** @type SQL_Model $m */
            $m = $this->add($this->model_name);

            return $m->addCondition($this->their_field, $this->owner->get($this->our_field));
        }

        $this->restoreConditions();

        $this->model->unload();

        return $this->model->addCondition($this->their_field, $this->owner->get($this->our_field));
    }
}
