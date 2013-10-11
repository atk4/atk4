<?php

class Field_SQL_HasOne extends Field_SQL_Expression {
    function getExpression($model) {
        $refModel = $this->getModel();
        if (is_string($refModel)) {
            $refModel = $this->api->normalizeClassName($refModel, 'Model');
        }
        $refModel = $this->add($refModel);

        return $refModel->dsql()
            ->field('name')
            ->where($refModel->id_field, $model->dsql()->getField($this->getForeignFieldName()));
    }

    function getValue($model, $data) {
        return $model->data[$this->short_name];
    }
    
    function getForeignFieldName() {
        return $this->foreignName;
    }

    function setForeignFieldName($name) {
        $this->foreignName = $name;
        return $this;
    }
}