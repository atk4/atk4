<?php

abstract class Field_Calculated extends Field {
    protected $expression = null;

    function setExpression($expression) {
        $this->expression = $expression;
        return $this;
    }

    abstract function getValue($model, $data);
}