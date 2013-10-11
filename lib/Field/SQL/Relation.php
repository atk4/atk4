<?php

class Field_SQL_Relation extends Field_Base {
    public $referenceType=null;

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

    function leftJoin($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null) {
        if (strpos($master_field, '.') === false) {
            $master_field =  $this->joinAlias . '.' . $master_field;
        }
        return $this->model->leftJoin($foreign_table, $master_field, $join_kind, $_foreign_alias, $this->joinAlias);
    }

    function setBehaviour($behaviour) {
        $this->model->addHook('beforeInsert', array($this, 'insertInForeingTable'));
        $this->model->addHook('beforeUpdate', array($this, 'updateInForeingTable'));

        switch($behaviour) {
            case 'ignore':
                // DO NOTHING
                break;
            case 'cascade':
                $this->model->addHook('afterDelete', array($this, 'deleteInForeignTable'));
                break;
            default:
                throw $this->exception('Unknonw join behaviour')
                    ->addMoreInfo('behaviour', $behaviour)
                    ->addMoreInfo('supported', array('ignore', 'cascade'));
                break;
        }
    }

    function insertInForeingTable($model) {
        $dsql = $this->api->db->dsql();
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
        $dsql = $this->api->db->dsql();
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
        $dsql = $this->api->db->dsql();
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