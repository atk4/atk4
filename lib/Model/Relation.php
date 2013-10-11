<?php

class Model_Relation extends Model {
    protected $defaultHasOneFieldClass = 'Field_SQL_HasOne';
    protected $defaultExpressionFieldClass = 'Field_SQL_Expression';
    protected $defaultSQLRelationFieldClass = 'Field_SQL_Relation';
    protected $relations = array();

    function dsql() {
        return $this->controller->dsql($this);
    }

    /**
     * See Field_SQL_Relation
     */
    function join($foreignTable, $leftField=null, $joinKind=null, $joinAlias=null, $relation=null, $behaviour='cascade') {
        list($rightTable, $rightField) = explode('.', $foreignTable, 2);
        if (is_null($rightField)) {
            $rightField = 'id';
        }
        $referenceType = ($rightField === 'id') ? 'hasOne' : 'hasMany';
        if ($referenceType === 'hasMany') {
            throw $this->exception('has many join isn\'t supported');
        }
        $leftTable = $this->table;

        $joinAlias = $this->_unique($this->relations, $joinAlias);
        $field = $this->add($this->defaultSQLRelationFieldClass);
        $field->setLeftTable($relation ? : $leftTable)
            ->setLeftField($leftField)
            ->setRightTable($rightTable)
            ->setRightField($rightField)
            ->setJoinKind($joinKind)
            ->setJoinAlias($joinAlias)
            ->setModel($this);

        $field->referenceType = $referenceType;
        $field->setBehaviour($behaviour);

        $this->relations[$joinAlias] = $field;
        return $field;
    }

    function leftJoin($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null, $relation=null) {
        if(is_null($join_kind)) {
            $join_kind = 'left';
        }
        $res = $this->join($foreign_table, $master_field, $join_kind, $_foreign_alias, $relation, 'ignore');
        return $res;
    }
}