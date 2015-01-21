<?php

abstract class Field_Calculated extends Field_Base {
    protected $expression = null;

    function setExpression($expression) {
        $this->expression = $expression;
        return $this;
    }

    abstract function getValue($model, $data);
}