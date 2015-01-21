<?php

class Field_SQL_Expression extends Field_Calculated {
    function getValue($model, $data) {
        return $model->data[$this->short_name];
    }

    function getExpr() {
        return $this->getExpression();
    }

    function getExpression() {
        return $this->expression;
    }

    function setForeignFieldName($name) {
        $this->name = $name;
        return $this;
    }
}