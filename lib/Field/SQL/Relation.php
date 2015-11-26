<?php

class Field_SQL_Relation extends Field_Base {
    public $referenceType=null;
    protected $behaviour;

    function setModel($model) {
        $this->model = $model;
        return $this;
    }

    function addField($name, $alias=null) {
        $field = $this->model->addField($name, $alias);
        $field->table($this->joinAlias);
        return $field;
    }

    function join($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null) {
        if (strpos($master_field, '.') === false) {
            $master_field =  $this->joinAlias . '.' . $master_field;
        }
        return $this->model->join($foreign_table, $master_field, $join_kind, $_foreign_alias, $this->joinAlias);
    }

    function hasOne($model, $our_field=UNDEFINED, $field_class=UNDEFINED) {
        $field = $this->model->hasOne($model, $our_field, $field_class);
        $field->table($this->joinAlias);

        $this->model->getElement($field->getForeignFieldName())->table($this->joinAlias);

        return $field;
    }
    function hasMany($model, $their_field=UNDEFINED, $our_field=UNDEFINED, $reference_name=null) {
        $field = $this->model->hasMany($model, $their_field, $our_field, $reference_name);
        return $field;
    }

    function setBehaviour($behaviour) {
        if (!in_array($behaviour, array('ignore', 'cascade'))) {
            throw $this->exception('Unknonw join behaviour')
                ->addMoreInfo('behaviour', $behaviour)
                ->addMoreInfo('supported', array('ignore', 'cascade'));
        }
        $this->model->addHook('beforeInsert', array($this, 'insertInForeingTable'));
        $this->model->addHook('beforeUpdate', array($this, 'updateInForeingTable'));
        $this->model->addHook('afterDelete', array($this, 'deleteInForeignTable'));

        $this->behaviour = $behaviour;
        return $this;
    }

    function insertInForeingTable($model) {
        if ($this->behaviour === 'ignore') {
            return;
        }
        $dsql = $this->app->db->dsql();
        $dsql->table($this->rightTable, $this->joinAlias);

        foreach ($model->dirty as $key => $value) {
            $field = $model->getElement($key);
            if ($field->table() !== $this->joinAlias) {
                continue;
            }
            $dsql->set($field->actual() ? : $key, $field->sanitize($model->get($key)));
        }

        $id = $dsql->insert();
        if ($this->referenceType === 'hasOne') {
            $model->set($this->leftField, $id);
        }
    }

    function updateInForeingTable($model) {
        if ($this->behaviour === 'ignore') {
            return;
        }
        $dsql = $this->app->db->dsql();
        $dsql->table($this->rightTable, $this->joinAlias);

        foreach ($model->dirty as $key => $value) {
            $field = $model->getElement($key);
            if ($field->table() !== $this->joinAlias) {
                continue;
            }
            $dsql->set($field->actual() ? : $key, $field->sanitize($model->get($key)));
        }

        $dsql->where($this->rightField, $model->get($this->leftField))->update();
        if (($this->referenceType === 'hasOne') && $model->isDirty($this->leftField)) {
            $model->set($this->leftField, $model->get($this->leftField));
        }
    }

    function deleteInForeignTable($model) {
        if ($this->behaviour === 'ignore') {
            return;
        }
        $dsql = $this->app->db->dsql();
        $dsql->table($this->rightTable, $this->joinAlias);
        $dsql->where($this->rightField, $model->get($this->leftField))->delete();
    }


    /**
     * [leftTable] [joinKind] [rightTable] as [joinAlias]
     *      on [leftTable].[leftField] = [rightTable].[rightField]
     *
     */
    function setLeftTable($table) {
        $this->leftTable = $table;
        return $this;
    }

    function setLeftField($leftField) {
        $this->leftField = $leftField;
        return $this;
    }

    function setRightTable($rightTable) {
        $this->rightTable = $rightTable;
        return $this;
    }

    function setRightField($rightField) {
        $this->rightField = $rightField;
        return $this;
    }

    function setJoinKind($joinKind) {
        $this->joinKind = $joinKind;
        return $this;
    }

    function setJoinAlias($joinAlias) {
        $this->joinAlias = $joinAlias;
        return $this;
    }
}