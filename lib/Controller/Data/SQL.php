<?php
/**
 * Undocumented
 */
class Controller_Data_SQL extends Controller_Data
{
    public $supportConditions = true;
    public $supportLimit = true;
    public $supportOrder = true;
    public $supportRef = true;
    public $supportOperators = array('=' => true, '>' => true, '>=' => true, '<=' => true, '<' => true, '!=' => true, 'like' => true);
    public $supportExpressions = true;

    public $auto_track_element = true;

    public function setSource($model, $data)
    {
        // do nothing
        return $this;
    }

    public function loadById($model, $id)
    {
        $dsql = $this->dsql($model);

        $p = '';
        if ($model->_references) {
            $p = ($model->table_alias ?: $model->table).'.';
        }
        if (is_null($id)) {
            throw $this->exception('ID is specified as null');
        }

        $dsql->where($p.$model->id_field, $id);

        return $this->load($model, $dsql);
    }

    public function loadByConditions($model)
    {
        $dsql = $this->dsql($model);

        return $this->load($model, $dsql);
    }

    public function load($model, $dsql)
    {
        $dsql = $this->getDsqlFromModel($model, $dsql);

        $data = $dsql->limit(1)->getHash();
        if (!empty($data)) {
            $model->data = $data;  // avoid using set() for speed and to avoid field checks
            $model->id = $model->data[$model->id_field];
        } else {
            $model->id = null;
            $model->data = array();
        }

        return $this;
    }

    public function save($model, $id, $data)
    {
        $dsql = $this->getDsqlForSave($model);
        foreach ($data as $key => $value) {
            $field = $model->getElement($key);
            $fieldTable = $field->table();
            // I set the value only for the current model table.
            // The other values'll be save by field sql relation
            if ($model->isDirty($key) && $field && (is_null($fieldTable) || $fieldTable === $model->table_alias)) {
                $dsql->set($field->actual() ?: $key, $field->sanitize($value));
            }
        }
        if (empty($dsql->args['set'])) {
            return $id; // is it correct?
        }
        $this->app->db->beginTransaction();
        if (is_null($id)) {
            $id = $dsql->insert();
        } else {
            $dsql->where($model->id_field, $id);
            $dsql->update();

            // uniform atk's bug
            $id = ''.($data[$model->id_field] ?: $id);
            if (isset($data[$model->id_field])) {
                $model->data[$model->id_field] = $id;
            }
        }
        //$id = '' . $id;

        $dsql->stmt = null;
        $model->tryLoad($id);
        if ($model->loaded()) {
            $this->app->db->commit();
        } else {
            $this->app->db->rollback();
            throw $model->exception('Record with specified id was not found');
        }

        return $id;
    }

    public function delete($model, $id)
    {
        $dsql = $this->getDsqlFromModel($model);
        $dsql->where($model->id_field, $id);
        $dsql->delete();
    }

    public function deleteAll($model)
    {
        $dsql = $this->getDsqlFromModel($model);
        $dsql->delete();
    }

    public function prefetchAll($model)
    {
        $dsql = $this->getDsqlFromModel($model);

        $dsql->rewind();
        $model->_table[$this->short_name] = $dsql;
    }

    /** Provided that rewind was called before, load next data entry */
    public function loadCurrent($model)
    {
        $model->_table[$this->short_name]->next();
        $model->data = $model->_table[$this->short_name]->current();
        $model->id = $model->data[$model->id_field];
    }

    public function updateQuery($model, $field, $select)
    {
        $p = null;
        if ($model->_references) {
            $p = $model->table_alias ?: $model->table;
        }

        $fieldName = $field->actual() ?: $field->short_name;
        if ($field instanceof Field_SQL_Relation) {
            $select->join($field->rightTable.'.'.$field->rightField, $field->leftField, $field->joinKind, $field->joinAlias);
        } elseif ($field instanceof Field_SQL_Expression) {
            return $select->field($field->getExpression($model), $field->short_name);
        } else {
            $select->field($fieldName, $field->table() ?: $p, $field->short_name);
        }

        return $field;
    }

    public function updateConditions($model, $dsql)
    {
        foreach ($model->conditions as $cond) {
            if (($cond[0] instanceof DB_dsql)) {
                $dsql->where($cond[0]);
                continue;
            }

            $field = $model->getElement($cond[0]);
            if (!($field instanceof Field_Base)) {
                throw $this->exception('Field not found')
                    ->addMoreInfo('field', $field);
            }

            if ($field->type() === 'boolean') {
                $cond[2] = $field->getBooleanValue($cond[2]);
            }

            if ($cond[1] === '=') {
                $field->defaultValue($cond[2])
                    ->system(true) // ???
                    ->editable(false); // ???
            }

            $fieldName = $field->short_name;
            if ($field instanceof Field_SQL_Expression) {
                // TODO: should we use expression in where?
                $dsql->where($field->getExpression(), $cond[1], $cond[2]);
            } else {
                $dsql->where(($field->table() ?: ($model->table_alias ?: $model->table)).'.'.$fieldName, $cond[1], $cond[2]);
            }
        }
    }

    public function dsql($model)
    {
        if (!$model->table) {
            throw $this->exception('$table property must be defined');
        }

        $model->dsql = $model->app->db->dsql();
        $model->dsql->debug = &$model->debug;
        $model->dsql->table($model->table, $model->table_alias);
        $model->dsql->default_field = $model->dsql->expr(
            '*,'.
            $model->dsql->bt($model->table_alias ?: $model->table).'.'.
            $model->dsql->bt($model->id_field)
        );
        $model->dsql->id_field = $model->id_field;

        return clone $model->dsql;
    }

    /**
     * Returns dynamic query selecting number of entries in the database.
     *
     * @param string $alias Optional alias of count expression
     *
     * @return DSQL
     */
    public function count($model, $alias = null)
    {
        // prepare new query
        $q = $this->getDsqlForSelect($model)->del('fields')->del('order')->del('limit');

        // add expression field to query
        return $q->field($q->count(), $alias);
    }

    protected function getDsqlForSelect($model, $dsql = null)
    {
        if (is_null($dsql)) {
            $dsql = $this->dsql($model);
        }
        $actualFields = $model->getActualFields();

        foreach ($model->elements as $el) {
            if (!($el instanceof Field_Base)) {
                continue;
            }

            if ($el->system() &&
                !in_array($el->short_name, $actualFields)) {
                $actualFields[] = $el->short_name;
                $this->updateQuery($model, $el, $dsql);
            } elseif (in_array($el->short_name, $actualFields)) {
                $this->updateQuery($model, $el, $dsql);
            }
        }

        if ($model->limit && $model->limit[0]) {
            $dsql->limit($model->limit[0], $model->limit[1]);
        }

        if ($model->order) {
            foreach ($model->order as $o) {
                $dsql->order($o[0], $o[1]);
            }
        }

        $this->updateConditions($model, $dsql);

        return $dsql;
    }

    private function getDsqlForSave($model, $dsql = null)
    {
        if (is_null($dsql)) {
            $dsql = $this->dsql($model);
        }

        return $dsql;
    }

    private function getDsqlFromModel($model, $dsql = null)
    {
        $dsql = $this->getDsqlForSelect($model, $dsql);

        return $dsql;
    }
}
